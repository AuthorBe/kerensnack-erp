<?php
use App\Core\Router;
use App\Helpers\Flash;

$cssV = file_exists(ROOT_PATH . '/public/assets/css/app.css') ? filemtime(ROOT_PATH . '/public/assets/css/app.css') : '1';
$jsV  = file_exists(ROOT_PATH . '/public/assets/js/app.js')  ? filemtime(ROOT_PATH . '/public/assets/js/app.js')  : '1';
$flash = Flash::get();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login — KEREN SNACK ERP</title>

    <!-- PWA & Mobile Web App Meta Tags -->
    <meta name="theme-color" content="#e11d48">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Keren Snack">
    <meta name="application-name" content="Keren Snack ERP">

    <!-- Favicon & PWA Icons -->
    <link rel="icon" type="image/x-icon" href="<?= Router::asset('/favicon/favicon.ico') ?>">
    <link rel="icon" type="image/svg+xml" href="<?= Router::asset('/favicon/favicon.svg') ?>">
    <link rel="icon" type="image/png" sizes="96x96" href="<?= Router::asset('/favicon/favicon-96x96.png') ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?= Router::asset('/favicon/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= Router::asset('/favicon/site.webmanifest') ?>">

    <!-- Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Lucide Icons (Local) -->
    <script src="<?= Router::asset('/js/lucide.min.js') ?>"></script>

    <!-- Alpine.js (Local) -->
    <script defer src="<?= Router::asset('/js/alpine.min.js') ?>"></script>

    <style>
        [x-cloak] { display: none !important; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        @keyframes gradientBg {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #fee2e2, #f8fafc, #f1f5f9, #ffe4e6);
            background-size: 400% 400%;
            animation: gradientBg 15s ease infinite;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem 1.25rem;
            margin: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Clean White Card matching reference */
        .login-card-wrapper {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 40px 36px;
            width: 100%;
            max-width: 410px;
            box-shadow: 0 16px 40px -10px rgba(0, 0, 0, 0.07), 0 0 1px 1px rgba(0, 0, 0, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.8);
            z-index: 10;
            position: relative;
            animation: fadeInUp 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            margin: 0 auto;
        }

        .login-header {
            text-align: center;
            margin-bottom: 1.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        /* Clean Squircle Logo without red glow */
        .login-logo {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            overflow: hidden;
            margin-bottom: 1.25rem;
            box-shadow: 0 8px 20px -4px rgba(0, 0, 0, 0.08), 0 2px 6px -1px rgba(0, 0, 0, 0.04);
            border: 1px solid rgba(0, 0, 0, 0.04);
            display: flex;
            align-items: center;
            justify-content: center;
            background: #ffffff;
        }

        .login-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .login-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #111827;
            margin-bottom: 3px;
            letter-spacing: -0.02em;
            text-align: center;
        }

        .login-subtitle {
            font-size: 0.85rem;
            font-weight: 400;
            color: #6b7280;
            text-align: center;
        }

        /* Modern Clean Inputs */
        .form-group-modern {
            margin-bottom: 1.25rem;
            text-align: left;
        }

        .form-label-modern {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .input-group-modern {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group-modern > .input-icon {
            position: absolute;
            left: 1.1rem;
            color: #9ca3af;
            width: 18px;
            height: 18px;
            pointer-events: none;
            transition: color 0.15s ease;
            z-index: 5;
        }

        .input-modern {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 3.15rem;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            color: #111827;
            outline: none;
            transition: all 0.15s ease;
        }

        .input-modern::placeholder {
            color: #9ca3af;
            font-size: 0.9rem;
        }

        .input-modern:focus {
            background-color: #ffffff;
            border-color: #e11d48;
            box-shadow: 0 0 0 3.5px rgba(225, 29, 72, 0.12);
        }

        .input-group-modern:focus-within > .input-icon {
            color: #e11d48;
        }

        .password-toggle {
            position: absolute;
            right: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #9ca3af;
            cursor: pointer;
            padding: 6px;
            display: flex;
            align-items: center;
            border-radius: 6px;
        }

        .password-toggle:hover {
            color: #4b5563;
        }

        .password-toggle svg {
            width: 18px;
            height: 18px;
        }

        /* Alert Pill */
        .alert-modern {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            line-height: 1.45;
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .alert-modern .alert-sub {
            font-size: 0.8rem;
            margin-top: 3px;
            opacity: 0.88;
            font-weight: 400;
            line-height: 1.35;
        }

        .alert-modern .alert-sub strong {
            font-weight: 700;
        }

        .alert-info-modern {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
        }

        /* Submit Button */
        .btn-login {
            background-color: #e11d48;
            color: #ffffff;
            border: none;
            border-radius: 12px;
            padding: 0.875rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.975rem;
            width: 100%;
            margin-top: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 8px 20px -4px rgba(225, 29, 72, 0.45);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-login:hover {
            background-color: #be123c;
            transform: translateY(-1px);
            box-shadow: 0 10px 24px -4px rgba(225, 29, 72, 0.55);
            color: #ffffff;
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 4px 10px rgba(225, 29, 72, 0.3);
            background-color: #9f1239;
        }

        .btn-login svg {
            width: 18px;
            height: 18px;
        }

        /* App Footer Watermark */
        .login-page-footer {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.85rem;
            color: #64748b;
            position: relative;
            z-index: 10;
        }

        .login-page-footer a {
            color: #2563eb !important;
            font-weight: 700 !important;
            text-decoration: none !important;
        }

        .login-page-footer a:hover {
            text-decoration: underline !important;
        }
    </style>
</head>
<body class="login-body" x-data="{ showPass: false }">

    <div class="login-card-wrapper">

        <!-- Login Header -->
        <div class="login-header">
            <div class="login-logo">
                <img src="<?= Router::asset('/favicon/apple-touch-icon.png') ?>" alt="Logo Keren Snack">
            </div>
            <h4 class="login-title">KEREN SNACK</h4>
            <p class="login-subtitle">Sistem ERP &amp; Manajemen Kasir</p>
        </div>

        <!-- Flash Error / Session Error -->
        <?php if (!empty($error) || ($flash && $flash['type'] === 'error')): ?>
        <div class="alert-modern">
            <i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"></i>
            <div style="flex:1;">
                <?= $error ?? htmlspecialchars($flash['message'] ?? '') ?>
            </div>
        </div>
        <?php elseif (!empty($info) || ($flash && $flash['type'] === 'info')): ?>
        <div class="alert-modern alert-info-modern">
            <i data-lucide="info" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"></i>
            <div style="flex:1;">
                <?= htmlspecialchars($info ?? $flash['message'] ?? '') ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="<?= Router::url('/login') ?>" id="login-form" novalidate>
            <?= \App\Helpers\CSRF::field() ?>
            <div class="form-group-modern">
                <label for="username" class="form-label-modern">Username</label>
                <div class="input-group-modern">
                    <i data-lucide="user" class="input-icon"></i>
                    <input type="text"
                           class="input-modern"
                           id="username"
                           name="username"
                           required
                           placeholder="Masukkan username"
                           autocomplete="username"
                           autofocus
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
            </div>

            <div class="form-group-modern" style="margin-bottom: 1.5rem;">
                <label for="password" class="form-label-modern">Password</label>
                <div class="input-group-modern">
                    <i data-lucide="lock" class="input-icon"></i>
                    <input :type="showPass ? 'text' : 'password'"
                           class="input-modern"
                           id="password"
                           name="password"
                           required
                           placeholder="••••••••"
                           autocomplete="current-password"
                           style="padding-right: 2.85rem;">
                    <button type="button" class="password-toggle" @click="showPass = !showPass" :title="showPass ? 'Sembunyikan' : 'Tampilkan'">
                        <span x-show="!showPass" style="display:flex;align-items:center;">
                            <i data-lucide="eye"></i>
                        </span>
                        <span x-show="showPass" style="display:flex;align-items:center;" x-cloak>
                            <i data-lucide="eye-off"></i>
                        </span>
                    </button>
                </div>
            </div>

            <button type="submit" id="btn-submit" class="btn-login">
                <i data-lucide="log-in"></i>
                Masuk Sistem
            </button>
        </form>

    </div>

    <!-- Login Page Footer -->
    <footer class="login-page-footer">
        &copy; 2026 Built by <a href="https://ajsk.vercel.app/" target="_blank" rel="noopener noreferrer">AJSK.</a>
    </footer>

    <!-- Init Lucide Icons, Countdown & Smart Keyboard UX -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }

            const loginForm = document.getElementById('login-form');
            const userInput = document.getElementById('username');
            const passInput = document.getElementById('password');
            const btnSubmit = document.getElementById('btn-submit');
            // 1. Real-time Countdown Timer if Locked Out
            let remainingTime = <?= (int)($remainingTime ?? 0) ?>;
            if (remainingTime > 0) {
                const timerElement = document.getElementById('countdown-timer');
                const updateTimerDisplay = () => {
                    if (remainingTime <= 0) {
                        window.location.reload();
                        return;
                    }
                    const m = Math.floor(remainingTime / 60);
                    const s = remainingTime % 60;
                    if (timerElement) {
                        timerElement.textContent = `${m}:${s.toString().padStart(2, '0')}`;
                    }
                    remainingTime--;
                };
                updateTimerDisplay();
                setInterval(updateTimerDisplay, 1000);
            }

            // 2. Auto-focus username on Space or Enter when no input is focused
            document.addEventListener('keydown', function(e) {
                if (['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON', 'A'].includes(document.activeElement?.tagName)) {
                    return;
                }
                if (e.key === ' ' || e.key === 'Enter') {
                    e.preventDefault();
                    if (userInput && !userInput.disabled) {
                        userInput.focus();
                        userInput.select();
                    }
                }
            });

            // 3. Smart UX Enter navigation across fields
            if (userInput && passInput && loginForm) {
                userInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        if (userInput.value.trim() === '') {
                            e.preventDefault();
                            userInput.focus();
                        } else if (passInput.value.trim() === '') {
                            e.preventDefault();
                            passInput.focus();
                        }
                    }
                });

                passInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter') {
                        if (userInput.value.trim() === '') {
                            e.preventDefault();
                            userInput.focus();
                        } else if (passInput.value.trim() === '') {
                            e.preventDefault();
                            passInput.focus();
                        }
                    }
                });

                loginForm.addEventListener('submit', function(e) {
                    if (userInput.value.trim() === '') {
                        e.preventDefault();
                        userInput.focus();
                        return false;
                    }
                    if (passInput.value.trim() === '') {
                        e.preventDefault();
                        passInput.focus();
                        return false;
                    }
                });
            }
        });
    </script>
</body>
</html>


<?php
use App\Core\Router;
use App\Helpers\Flash;

$cssV = file_exists(ROOT_PATH . '/public/assets/css/app.css') ? filemtime(ROOT_PATH . '/public/assets/css/app.css') : '1';
$jsV  = file_exists(ROOT_PATH . '/public/assets/js/app.js')  ? filemtime(ROOT_PATH . '/public/assets/js/app.js')  : '1';
$flash = Flash::get();
?>
<!DOCTYPE html>
<html lang="id" class="<?= (($_COOKIE['ksnack_theme'] ?? 'dark') === 'light') ? '' : 'dark' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Login — KEREN SNACK ERP</title>

    <!-- Zero-flash theme -->
    <script>(function(){var t=localStorage.getItem('ksnack_theme')||'dark';if(t==='light')document.documentElement.classList.remove('dark');else document.documentElement.classList.add('dark');})();</script>

    <!-- Fonts: Inter & Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Poppins', sans-serif;
            -webkit-font-smoothing: antialiased;
        }

        /* Animated Mesh Gradient Background like rekap-mukholif */
        @keyframes gradientBg {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-body {
            background: linear-gradient(-45deg, #e0e7ff, #f3f4f6, #dcfce7, #d1fae5);
            background-size: 400% 400%;
            animation: gradientBg 15s ease infinite;
            min-height: 100vh;
            min-height: 100dvh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1.25rem;
            position: relative;
        }

        .dark .login-body {
            background: linear-gradient(-45deg, #090d16, #0f172a, #062b1e, #0c1524);
            background-size: 400% 400%;
            animation: gradientBg 15s ease infinite;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes float {
            0%   { transform: translateY(0px); }
            50%  { transform: translateY(-8px); }
            100% { transform: translateY(0px); }
        }

        /* Frosted Glass Card Wrapper */
        .login-card-wrapper {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border-radius: 1.5rem;
            padding: 2.75rem 2.25rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.1), inset 0 1px 0 rgba(255, 255, 255, 1);
            border: 1px solid rgba(255, 255, 255, 0.7);
            z-index: 10;
            position: relative;
            animation: fadeInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            margin: 0 auto;
        }

        .dark .login-card-wrapper {
            background: rgba(17, 24, 39, 0.85);
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 45px -10px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.05);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .login-logo {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            background: linear-gradient(135deg, #15803d, #166534);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            margin-bottom: 1rem;
            box-shadow: 0 8px 20px rgba(21, 128, 61, 0.3);
            animation: float 6s ease-in-out infinite;
        }

        .login-logo svg {
            width: 32px;
            height: 32px;
        }

        .login-title {
            font-size: 1.35rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 3px;
            letter-spacing: -0.01em;
        }

        .dark .login-title {
            color: #f8fafc;
        }

        .login-subtitle {
            font-size: 0.875rem;
            color: #64748b;
        }

        .dark .login-subtitle {
            color: #94a3b8;
        }

        /* Modern Inputs */
        .form-group-modern {
            margin-bottom: 1.25rem;
        }

        .form-label-modern {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.35rem;
        }

        .dark .form-label-modern {
            color: #cbd5e1;
        }

        .input-group-modern {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-group-modern > .input-icon {
            position: absolute;
            left: 1.15rem;
            color: #9ca3af;
            width: 18px;
            height: 18px;
            pointer-events: none;
            transition: color 0.2s ease;
            z-index: 5;
        }

        .input-modern {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 3.15rem;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            background-color: #f9fafb;
            font-family: 'Poppins', sans-serif;
            font-size: 0.95rem;
            color: #1f2937;
            outline: none;
            transition: all 0.2s ease;
        }

        .dark .input-modern {
            background-color: #1f2937;
            border-color: #374151;
            color: #f8fafc;
        }

        .input-modern::placeholder {
            color: #9ca3af;
        }

        .input-modern:focus {
            background-color: #ffffff;
            border-color: #15803d;
            box-shadow: 0 0 0 4px rgba(21, 128, 61, 0.12);
        }

        .dark .input-modern:focus {
            background-color: #111827;
            border-color: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.15);
        }

        .input-group-modern:focus-within > .input-icon {
            color: #15803d;
        }

        .dark .input-group-modern:focus-within > .input-icon {
            color: #4ade80;
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

        .dark .password-toggle:hover {
            color: #e2e8f0;
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
            padding: 0.75rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.85rem;
            font-weight: 500;
            margin-bottom: 1.25rem;
            line-height: 1.45;
        }

        .alert-danger-modern {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .dark .alert-danger-modern {
            background: rgba(239, 68, 68, 0.15);
            color: #f87171;
            border-color: rgba(239, 68, 68, 0.3);
        }

        /* Submit Button */
        .btn-login {
            background-color: #2da36b;
            color: #ffffff;
            border: none;
            border-radius: 0.75rem;
            padding: 0.85rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1rem;
            width: 100%;
            margin-top: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(45, 163, 107, 0.25);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .btn-login:hover {
            background-color: #1f7a4e;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(45, 163, 107, 0.35);
            color: #ffffff;
        }

        .btn-login:active {
            transform: translateY(0);
            box-shadow: 0 2px 6px rgba(45, 163, 107, 0.2);
        }

        .btn-login svg {
            width: 18px;
            height: 18px;
        }

        /* App Footer Watermark */
        .login-page-footer {
            text-align: center;
            margin-top: 1.25rem;
            font-size: 0.875rem;
            color: #64748b;
            position: relative;
            z-index: 10;
        }

        .dark .login-page-footer {
            color: #94a3b8;
        }

        .login-page-footer a {
            color: #2563eb !important;
            font-weight: 700 !important;
            text-decoration: none !important;
            transition: opacity 0.15s ease, color 0.15s ease;
        }

        .login-page-footer a:hover {
            color: #1d4ed8 !important;
            text-decoration: none !important;
            opacity: 0.85;
        }

        .dark .login-page-footer a {
            color: #3b82f6 !important;
            text-decoration: none !important;
        }
    </style>
</head>
<body class="login-body" x-data="{ showPass: false }">

    <div class="login-card-wrapper">

        <!-- Login Header -->
        <div class="login-header">
            <div class="login-logo">
                <i data-lucide="zap"></i>
            </div>
            <h4 class="login-title">KEREN SNACK</h4>
            <p class="login-subtitle">Sistem ERP &amp; Manajemen Kasir</p>
        </div>

        <!-- Flash Error / Session Error -->
        <?php if (!empty($error) || ($flash && $flash['type'] === 'error')): ?>
        <div class="alert-modern alert-danger-modern">
            <i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;margin-top:2px;"></i>
            <div><?= htmlspecialchars($error ?? $flash['message']) ?></div>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="<?= Router::url('/login') ?>">
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

    <!-- Init Lucide Icons -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        });
    </script>
</body>
</html>

<?php
use App\Core\Router;
use App\Core\Auth;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Source &amp; Lifecycle Console &bull; Keren Snack ERP</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="<?= Router::asset('/js/alpine.min.js') ?>" defer></script>
    <style>
        :root {
            --bg: #090c15;
            --surface: #0f1422;
            --surface-hover: #161e31;
            --border: #1a2337;
            --border-highlight: #28354f;
            --text: #f1f5f9;
            --text-muted: #62728d;
            --text-dim: #94a3b8;
            --accent-cyan: #38bdf8;
            --accent-green: #10b981;
            --accent-yellow: #f59e0b;
            --accent-red: #ef4444;
            --accent-purple: #c084fc;
            --font-mono: 'JetBrains Mono', 'Fira Code', 'Cascadia Code', Consolas, monospace;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font-mono);
            background-color: var(--bg);
            color: var(--text);
            min-height: 100vh;
            padding: 24px 16px;
            line-height: 1.5;
            background-image: 
                radial-gradient(ellipse at 50% 0%, rgba(192, 132, 252, 0.08) 0%, transparent 65%),
                linear-gradient(to right, rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(to bottom, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 100% 100%, 28px 28px, 28px 28px;
        }

        /* Top Slim Progress Bar */
        .top-loader {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            width: 0%;
            background: linear-gradient(90deg, #c084fc, #38bdf8, #10b981);
            box-shadow: 0 0 12px rgba(192, 132, 252, 0.8);
            z-index: 99999;
            transition: width 0.25s ease-out;
        }

        .container {
            max-width: 1040px;
            margin: 0 auto;
        }

        /* Top Command Bar */
        .cmd-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 10px 16px;
            margin-bottom: 18px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .cmd-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .window-dots {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .dot {
            width: 9px;
            height: 9px;
            border-radius: 50%;
        }
        .dot-red { background: var(--accent-red); }
        .dot-yellow { background: var(--accent-yellow); }
        .dot-green { background: var(--accent-green); }

        .cmd-prompt {
            font-size: 12px;
            color: var(--text-dim);
        }
        .cmd-prompt .path { color: var(--accent-purple); font-weight: 600; }
        .cmd-prompt .branch { color: var(--accent-cyan); }
        .cmd-prompt .cmd { color: #e2e8f0; }

        .btn-group {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
            font-size: 11px;
            font-weight: 500;
            padding: 6px 12px;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid var(--border-highlight);
            background: rgba(255, 255, 255, 0.03);
            color: var(--text);
            transition: all 0.15s ease;
            user-select: none;
        }
        .btn:hover {
            background: var(--surface-hover);
            border-color: var(--accent-cyan);
            color: #fff;
        }
        .btn-primary {
            background: #8b5cf6;
            border-color: #8b5cf6;
            color: #fff;
            font-weight: 600;
        }
        .btn-primary:hover {
            background: #7c3aed;
            border-color: #7c3aed;
        }
        .btn-danger {
            background: #ef4444;
            border-color: #ef4444;
            color: #fff;
        }
        .btn-danger:hover {
            background: #dc2626;
            border-color: #dc2626;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            pointer-events: none;
        }

        .icon-spin {
            display: inline-block;
            animation: spinForever 0.8s linear infinite;
        }

        @keyframes spinForever {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Status Hero Banner */
        .status-hero {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            transition: border-color 0.25s ease;
        }

        .status-hero.is-pass { border-color: rgba(16, 185, 129, 0.35); }
        .status-hero.is-fail { border-color: rgba(239, 68, 68, 0.4); }
        .status-hero.is-running { border-color: rgba(56, 189, 248, 0.4); }

        .status-info {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--text-muted);
            box-shadow: 0 0 6px rgba(148, 163, 184, 0.4);
            transition: all 0.2s ease;
        }
        .status-indicator.pass {
            background: var(--accent-green);
            box-shadow: 0 0 12px rgba(16, 185, 129, 0.7);
        }
        .status-indicator.fail {
            background: var(--accent-red);
            box-shadow: 0 0 12px rgba(239, 68, 68, 0.7);
        }
        .status-indicator.running {
            background: var(--accent-cyan);
            box-shadow: 0 0 14px rgba(56, 189, 248, 0.9);
            animation: pulseGlow 0.9s infinite ease-in-out;
        }

        @keyframes pulseGlow {
            0% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.3); opacity: 1; box-shadow: 0 0 16px rgba(56, 189, 248, 0.9); }
            100% { transform: scale(1); opacity: 0.8; }
        }

        .status-headline {
            font-size: 14px;
            font-weight: 700;
            letter-spacing: 0.5px;
            color: #fff;
        }

        .status-sub {
            font-size: 11px;
            color: var(--text-muted);
            margin-top: 3px;
        }

        .status-tag {
            font-size: 10px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 5px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-dim);
            border: 1px solid var(--border);
        }
        .status-tag.pass {
            background: rgba(16, 185, 129, 0.12);
            color: var(--accent-green);
            border-color: rgba(16, 185, 129, 0.3);
        }
        .status-tag.fail {
            background: rgba(239, 68, 68, 0.12);
            color: var(--accent-red);
            border-color: rgba(239, 68, 68, 0.3);
        }
        .status-tag.running {
            background: rgba(56, 189, 248, 0.15);
            color: var(--accent-cyan);
            border-color: rgba(56, 189, 248, 0.4);
        }

        /* Metrics Telemetry Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 16px;
        }

        @media (max-width: 820px) {
            .metrics-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .metrics-grid { grid-template-columns: 1fr; }
        }

        .metric-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 14px;
            transition: border-color 0.15s ease;
        }
        .metric-card:hover { border-color: var(--border-highlight); }

        .metric-label {
            font-size: 10px;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .metric-value {
            font-size: 18px;
            font-weight: 700;
            color: #fff;
            margin-top: 6px;
            letter-spacing: -0.5px;
        }

        .metric-meta {
            font-size: 10px;
            color: var(--text-dim);
            margin-top: 3px;
        }

        /* In-Page Progress Bar */
        .progress-container {
            margin-bottom: 16px;
        }
        .progress-track {
            height: 6px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 9999px;
            overflow: hidden;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #c084fc, #38bdf8, #10b981);
            border-radius: 9999px;
            transition: width 0.25s ease-out;
        }
        .progress-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10.5px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        /* Filter & Search Bar */
        .filter-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 14px;
            flex-wrap: wrap;
        }

        .pills-group {
            display: flex;
            align-items: center;
            gap: 6px;
            overflow-x: auto;
            max-width: 100%;
            padding-bottom: 4px;
            scrollbar-width: none;
        }
        .pill {
            font-family: inherit;
            font-size: 10.5px;
            padding: 4px 10px;
            border-radius: 5px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-dim);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
        }
        .pill:hover { border-color: var(--border-highlight); color: #fff; }
        .pill.active {
            background: rgba(192, 132, 252, 0.12);
            border-color: var(--accent-purple);
            color: var(--accent-purple);
            font-weight: 600;
        }

        .search-box {
            position: relative;
            min-width: 220px;
        }
        .search-input {
            width: 100%;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 6px 10px 6px 28px;
            font-family: inherit;
            font-size: 11px;
            color: var(--text);
            outline: none;
            transition: border-color 0.15s;
        }
        .search-input:focus { border-color: var(--accent-cyan); }
        .search-icon {
            position: absolute;
            left: 9px;
            top: 7px;
            color: var(--text-muted);
            font-size: 11px;
        }

        /* Checklist Panel */
        .panel {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 18px;
        }

        .panel-header {
            background: rgba(0, 0, 0, 0.25);
            padding: 10px 16px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            font-weight: 600;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .checklist {
            list-style: none;
        }

        .check-item {
            padding: 12px 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.025);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transition: background 0.1s ease;
        }
        .check-item:last-child {
            border-bottom: none;
        }
        .check-item:hover {
            background: rgba(255, 255, 255, 0.015);
        }

        .badge-status {
            font-size: 10px;
            font-weight: 700;
            padding: 2px 7px;
            border-radius: 4px;
            letter-spacing: 0.5px;
            min-width: 52px;
            text-align: center;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .badge-ready {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            border: 1px solid var(--border);
        }
        .badge-run {
            background: rgba(56, 189, 248, 0.12);
            color: var(--accent-cyan);
            border: 1px solid rgba(56, 189, 248, 0.3);
            animation: pulseGlow 0.9s infinite ease-in-out;
        }
        .badge-pass {
            background: rgba(16, 185, 129, 0.12);
            color: var(--accent-green);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        .badge-fail {
            background: rgba(239, 68, 68, 0.12);
            color: var(--accent-red);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .check-content {
            flex: 1;
            min-width: 0;
        }
        .check-header {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .check-index {
            color: var(--accent-purple);
            font-weight: 700;
            font-size: 11px;
        }
        .check-title {
            font-size: 12px;
            font-weight: 600;
            color: #fff;
        }
        .check-cat {
            font-size: 9.5px;
            padding: 1px 6px;
            border-radius: 3px;
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid var(--border);
            color: var(--text-dim);
        }
        .check-duration {
            font-size: 10.5px;
            color: var(--accent-green);
            font-weight: 600;
        }
        .check-detail {
            font-size: 11px;
            color: var(--text-dim);
            margin-top: 3px;
            line-height: 1.4;
        }
        .check-file {
            font-size: 10px;
            color: #475569;
            margin-top: 2px;
        }

        .check-actions {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .btn-sm {
            padding: 3px 8px;
            font-size: 10px;
            border-radius: 4px;
        }

        /* Monospace Terminal Log Box */
        .log-box {
            margin-top: 10px;
            background: #06080e;
            border: 1px solid #1e293b;
            border-radius: 6px;
            padding: 10px 12px;
            font-size: 10.5px;
            line-height: 1.5;
            color: #cbd5e1;
            position: relative;
        }
        .log-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 6px;
            margin-bottom: 6px;
            border-bottom: 1px solid #1e293b;
            font-size: 10px;
            color: var(--text-muted);
        }
        .log-pre {
            white-space: pre-wrap;
            word-break: break-word;
            max-height: 240px;
            overflow-y: auto;
            color: #e2e8f0;
            font-family: inherit;
        }

        /* Footer */
        .footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10px;
            color: var(--text-muted);
            padding: 12px 4px 20px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .shortcut-hint { color: #475569; }
        .shortcut-hint kbd {
            background: rgba(255,255,255,0.06);
            border: 1px solid #334155;
            padding: 1px 4px;
            border-radius: 3px;
            color: #94a3b8;
        }

        [x-cloak] { display: none !important; }

        /* ── Cooldown & Lock Banners ── */
        .alert-banner {
            border-radius: 10px;
            padding: 14px 18px;
            margin-bottom: 16px;
            display: flex;
            align-items: flex-start;
            gap: 14px;
            font-size: 12px;
            line-height: 1.5;
            animation: fadeInDown 0.3s ease-out;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        .alert-cooldown {
            background: rgba(245, 158, 11, 0.08);
            border: 1px solid rgba(245, 158, 11, 0.35);
        }
        .alert-lock {
            background: rgba(239, 68, 68, 0.08);
            border: 1px solid rgba(239, 68, 68, 0.35);
        }

        .alert-icon {
            font-size: 20px;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .alert-body { flex: 1; }
        .alert-title {
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.3px;
            margin-bottom: 4px;
        }
        .alert-cooldown .alert-title { color: var(--accent-yellow); }
        .alert-lock .alert-title     { color: var(--accent-red); }

        .alert-meta {
            color: var(--text-dim);
            font-size: 11px;
        }
        .alert-meta strong { color: var(--text); }

        /* Cooldown progress bar inside banner */
        .cooldown-progress-wrap {
            margin-top: 10px;
            background: rgba(255, 255, 255, 0.06);
            border-radius: 9999px;
            height: 5px;
            overflow: hidden;
        }
        .cooldown-progress-bar {
            height: 100%;
            border-radius: 9999px;
            background: linear-gradient(90deg, var(--accent-yellow), var(--accent-red));
            transition: width 1s linear;
        }
        .cooldown-timer-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 6px;
            font-size: 10.5px;
            color: var(--text-muted);
        }
        .cooldown-seconds {
            font-weight: 700;
            color: var(--accent-yellow);
            font-size: 13px;
            letter-spacing: -0.5px;
        }
    </style>
</head>
<body x-data="devTestConsoleApp()" x-init="init()">

    <!-- Top Slim Animated Progress Bar -->
    <div class="top-loader" :style="'width: ' + progressPercentage + '%; opacity: ' + (isRunning ? 1 : 0) + ';'"></div>

    <!-- ─── Cooldown Banner ─── -->
    <div class="container" x-show="isInCooldown" x-cloak style="padding-top:12px;">
        <div class="alert-banner alert-cooldown">
            <div class="alert-icon">⏱</div>
            <div class="alert-body">
                <div class="alert-title">COOLDOWN AKTIF — Harap Tunggu Sebelum Menjalankan Test Berikutnya</div>
                <div class="alert-meta" x-show="cooldownInfo.suite_title">
                    Test terakhir: <strong x-text="cooldownInfo.suite_title"></strong>
                    oleh <strong x-text="cooldownInfo.user || '—'"></strong>
                    — <strong :style="cooldownInfo.result === 'PASS' ? 'color:var(--accent-green)' : 'color:var(--accent-red)'" x-text="cooldownInfo.result || ''"></strong>
                </div>
                <div class="alert-meta" x-show="!cooldownInfo.suite_title" style="color:var(--text-muted);">
                    Data cooldown tidak lengkap (stale file). Klik "Bersihkan" untuk reset.
                </div>
                <div x-show="cooldownInfo.suite_title">
                    <div class="cooldown-progress-wrap">
                        <div class="cooldown-progress-bar" :style="'width: ' + cooldownProgressPct + '%;'"></div>
                    </div>
                    <div class="cooldown-timer-label">
                        <span>Sistem akan kembali siap setelah countdown selesai</span>
                        <span><span class="cooldown-seconds" x-text="cooldownRemaining"></span> detik</span>
                    </div>
                </div>
            </div>
            <div style="flex-shrink:0;margin-left:8px;">
                <button @click="clearLocks()" :disabled="clearingLocks" class="btn btn-sm" style="border-color:var(--accent-yellow);color:var(--accent-yellow);white-space:nowrap;font-size:11px;" title="Hapus stale lock & cooldown files dari server">
                    <span x-text="clearingLocks ? '...' : '🗑 Bersihkan'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- ─── Lock Banner (user lain sedang test) ─── -->
    <div class="container" x-show="isGloballyLocked" x-cloak style="padding-top:12px;">
        <div class="alert-banner alert-lock">
            <div class="alert-icon">🔒</div>
            <div class="alert-body">
                <div class="alert-title">TEST SEDANG BERJALAN OLEH PENGGUNA LAIN</div>
                <div class="alert-meta" x-show="lockInfo.suite_title || lockInfo.user">
                    Suite: <strong x-text="lockInfo.suite_title || '—'"></strong><br>
                    Dijalankan oleh: <strong x-text="lockInfo.user || '—'"></strong>
                    • <span x-text="lockInfo.running_since_label || 'baru saja'"></span>
                </div>
                <div class="alert-meta" x-show="!lockInfo.suite_title && !lockInfo.user" style="color:var(--text-muted);">
                    Data lock tidak lengkap (stale file). Klik "Bersihkan" untuk reset.
                </div>
                <div class="alert-meta" style="margin-top:6px;" x-show="lockInfo.user">
                    Harap tunggu hingga proses selesai. Lock akan otomatis dilepas maksimal 3 menit setelah dimulai.
                </div>
            </div>
            <div style="flex-shrink:0;margin-left:8px;">
                <button @click="clearLocks()" :disabled="clearingLocks" class="btn btn-sm" style="border-color:var(--accent-red);color:var(--accent-red);white-space:nowrap;font-size:11px;" title="Hapus stale lock & cooldown files dari server">
                    <span x-text="clearingLocks ? '...' : '🗑 Bersihkan'"></span>
                </button>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Top Command Bar -->
        <div class="cmd-bar">
            <div class="cmd-left">
                <div class="window-dots">
                    <span class="dot dot-red"></span>
                    <span class="dot dot-yellow"></span>
                    <span class="dot dot-green"></span>
                </div>
                <div class="cmd-prompt">
                    <span class="path">kerensnack-erp</span><span class="branch">:(dev)</span> <span class="cmd">$ php tests/run_all.php --ajax-stream</span>
                </div>
            </div>

            <div class="btn-group">
                <template x-if="!isRunning && !isInCooldown && !isGloballyLocked">
                    <button type="button" @click="runAll()" class="btn btn-primary" id="btn-run-all">
                        <span>&#x25b6; Run All (<?= count($suites) ?>)</span>
                    </button>
                </template>

                <template x-if="isInCooldown && !isRunning">
                    <button type="button" disabled class="btn btn-primary" style="opacity:0.45;cursor:not-allowed;" title="Tunggu cooldown selesai">
                        <span>⏱ Cooldown (<span x-text="cooldownRemaining"></span>s)</span>
                    </button>
                </template>

                <template x-if="isGloballyLocked && !isRunning && !isInCooldown">
                    <button type="button" disabled class="btn btn-primary" style="opacity:0.45;cursor:not-allowed;" title="User lain sedang menjalankan test">
                        <span>🔒 Terkunci</span>
                    </button>
                </template>

                <template x-if="isRunning">
                    <button type="button" @click="stop()" class="btn btn-danger">
                        <span>&#x25a0; Stop Process</span>
                    </button>
                </template>

                <button type="button" @click="resetAll()" :disabled="isRunning" class="btn">
                    <span>&#x21bb; Reset</span>
                </button>

                <a href="<?= Router::url('/developer/test-db') ?>" class="btn">
                    <span style="color:var(--accent-green);">&#x25cf;</span> Test DB
                </a>

                <a href="<?= Router::url('/developer/architecture') ?>" class="btn">
                    <span style="color:var(--accent-cyan);">&#x25cf;</span> Blueprint
                </a>

                <a href="<?= Router::url('/developer') ?>" class="btn">
                    &rarr; Developer Home
                </a>
            </div>
        </div>

        <!-- Status Hero -->
        <div class="status-hero" :class="heroClass">
            <div class="status-info">
                <div class="status-indicator" :class="indicatorClass"></div>
                <div>
                    <div class="status-headline" x-text="headlineText"></div>
                    <div class="status-sub">
                        PostgreSQL SSL Gateway &bull; Zero-Pollution Transaction Rollbacks &bull; Real-time Async AJAX
                    </div>
                </div>
            </div>
            <div>
                <span class="status-tag" :class="tagClass" x-text="tagText"></span>
            </div>
        </div>

        <!-- Metrics Grid -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-label">
                    <span>Test Suite Registry</span>
                    <span style="color:var(--accent-purple);">&#x25cf;</span>
                </div>
                <div class="metric-value"><?= count($suites) ?><span style="font-size:11px;font-weight:400;color:var(--text-muted);margin-left:4px;">Suites</span></div>
                <div class="metric-meta">100% Modul ERP Aktif</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">
                    <span>Passed Suites</span>
                    <span style="color:var(--accent-green);">&#x25cf;</span>
                </div>
                <div class="metric-value" style="color:var(--accent-green);">
                    <span x-text="passedCount">0</span><span style="font-size:11px;font-weight:400;color:var(--text-muted);margin-left:4px;" x-text="'/ ' + totalSuites">/ <?= count($suites) ?></span>
                </div>
                <div class="metric-meta" x-text="passPercentage + '% dari total suite'">0% dari total suite</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">
                    <span>Failed Suites</span>
                    <span style="color:var(--accent-red);">&#x25cf;</span>
                </div>
                <div class="metric-value" :style="failedCount > 0 ? 'color:var(--accent-red);' : 'color:#fff;'">
                    <span x-text="failedCount">0</span>
                </div>
                <div class="metric-meta" x-text="failedCount > 0 ? 'Perlu investigasi log' : 'Nol kegagalan sistem'">Nol kegagalan sistem</div>
            </div>

            <div class="metric-card">
                <div class="metric-label">
                    <span>Execution Runtime</span>
                    <span style="color:var(--accent-cyan);">&#x25cf;</span>
                </div>
                <div class="metric-value">
                    <span x-text="totalDuration.toFixed(2)">0.00</span><span style="font-size:11px;font-weight:400;color:var(--text-muted);margin-left:4px;">s</span>
                </div>
                <div class="metric-meta">Timer Eksekusi Live</div>
            </div>
        </div>

        <!-- In-Page Progress Bar -->
        <div class="progress-container">
            <div class="progress-track">
                <div class="progress-bar" :style="'width: ' + progressPercentage + '%;'"></div>
            </div>
            <div class="progress-info">
                <span x-text="progressStatusText">Siap Dijalankan</span>
                <span style="font-weight:600;" x-text="Math.round(progressPercentage) + '%'">0%</span>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div class="filter-bar">
            <div class="pills-group">
                <button type="button" @click="activeCategory = 'all'" class="pill" :class="activeCategory === 'all' ? 'active' : ''">
                    All Categories (<?= count($suites) ?>)
                </button>
                <template x-for="cat in categories" :key="cat">
                    <button type="button" @click="activeCategory = cat" class="pill" :class="activeCategory === cat ? 'active' : ''" x-text="cat">
                    </button>
                </template>
            </div>

            <div class="search-box">
                <span class="search-icon">&#x1f50d;</span>
                <input type="text" x-model="searchQuery" placeholder="Filter suites / file..." class="search-input">
            </div>
        </div>

        <!-- Checklist Panel -->
        <div class="panel">
            <div class="panel-header">
                <span>Automated Test Suites &bull; <?= count($suites) ?> Registered</span>
                <span>Rollback Safe &bull; Zero DB Pollution</span>
            </div>

            <ul class="checklist">
                <template x-for="suite in filteredSuites" :key="suite.key">
                    <li class="check-item">
                        <!-- Status Badge -->
                        <div>
                            <template x-if="suite.status === 'READY'">
                                <span class="badge-status badge-ready">WAIT</span>
                            </template>
                            <template x-if="suite.status === 'RUNNING'">
                                <span class="badge-status badge-run">RUN</span>
                            </template>
                            <template x-if="suite.status === 'PASS'">
                                <span class="badge-status badge-pass">PASS</span>
                            </template>
                            <template x-if="suite.status === 'FAIL'">
                                <span class="badge-status badge-fail">FAIL</span>
                            </template>
                        </div>

                        <!-- Content Details -->
                        <div class="check-content">
                            <div class="check-header">
                                <span class="check-index" x-text="'#' + String(suite.index).padStart(2, '0')"></span>
                                <span class="check-title" x-text="suite.title"></span>
                                <span class="check-cat" x-text="suite.category"></span>
                                <template x-if="suite.status === 'PASS' || suite.status === 'FAIL'">
                                    <span class="check-duration" :style="suite.status === 'FAIL' ? 'color:var(--accent-red);' : ''" x-text="suite.duration + 's'"></span>
                                </template>
                            </div>
                            <div class="check-detail" x-text="suite.description"></div>
                            <div class="check-file">
                                <code>tests/<span x-text="suite.file"></span></code>
                            </div>

                            <!-- Terminal Log Accordion -->
                            <template x-if="isLogExpanded(suite.key) && suite.output">
                                <div class="log-box">
                                    <div class="log-header">
                                        <span>TERMINAL LOG OUTPUT &bull; <span x-text="suite.file"></span></span>
                                        <button type="button" @click="copyLog(suite.output)" class="btn btn-sm" style="background:#1e293b;color:#cbd5e1;border:none;">
                                            Copy Log
                                        </button>
                                    </div>
                                    <pre class="log-pre" x-text="suite.output"></pre>
                                </div>
                            </template>
                        </div>

                        <!-- Actions -->
                        <div class="check-actions">
                            <template x-if="suite.output">
                                <button type="button" @click="toggleLog(suite.key)" class="btn btn-sm">
                                    <span x-text="isLogExpanded(suite.key) ? 'Hide Log' : 'View Log'"></span>
                                </button>
                            </template>

                            <button type="button" @click="runSingle(suite.key)" :disabled="!canRun || suite.status === 'RUNNING'" class="btn btn-sm" style="border-color:var(--accent-purple);color:var(--accent-purple);">
                                <span x-text="suite.status === 'RUNNING' ? 'Running...' : (!canRun && !isRunning ? '⏳' : 'Run')"></span>
                            </button>
                        </div>
                    </li>
                </template>
            </ul>
        </div>

        <!-- Terminal CLI Cheatsheet Box -->
        <div class="panel">
            <div class="panel-header">
                <span>CLI Terminal Shortcuts</span>
                <span>Zero Code Duplication</span>
            </div>
            <div style="padding:14px 16px;font-size:11px;color:var(--text-dim);line-height:1.6;">
                <div style="margin-bottom:8px;">
                    <span style="color:var(--accent-cyan);font-weight:600;">1. Eksekusi Seluruh Suite via CLI Terminal:</span><br>
                    <code style="color:#10b981;background:rgba(16,185,129,0.08);padding:2px 6px;border-radius:4px;">php tests/run_all.php</code> atau <code style="color:#10b981;background:rgba(16,185,129,0.08);padding:2px 6px;border-radius:4px;">php developer/run_all.php</code>
                </div>
                <div>
                    <span style="color:var(--accent-cyan);font-weight:600;">2. Eksekusi Healthcheck Database via CLI:</span><br>
                    <code style="color:#38bdf8;background:rgba(56,189,248,0.08);padding:2px 6px;border-radius:4px;">php developer/test_db.php</code>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div>KEREN SNACK ERP &bull; DEVELOPER TEST RUNNER CONSOLE</div>
            <div class="shortcut-hint">Hotkeys: Press <kbd>R</kbd> to Run All &bull; Press <kbd>Esc</kbd> to Stop</div>
            <div>STATUS 200 OK &bull; PHP <?= PHP_VERSION ?> (pdo_pgsql)</div>
        </div>
    </div>

    <!-- Alpine.js Application Engine -->
    <script>
    function devTestConsoleApp() {
        return {
            suites: <?= json_encode(array_values($suites)) ?>,
            csrfToken: <?= json_encode($csrfToken) ?>,
            isRunning: false,
            shouldStop: false,
            activeCategory: 'all',
            searchQuery: '',
            expandedLogs: [],
            totalDuration: 0,

            // ── Cooldown state (diinisiasi dari PHP saat halaman dimuat) ──
            cooldownRemaining: <?= isset($cooldownState['remaining_seconds']) ? (int)$cooldownState['remaining_seconds'] : 0 ?>,
            cooldownTotal: 60, // durasi cooldown max (detik)
            cooldownInfo: <?= json_encode($cooldownState ?: ['suite_title' => '', 'user' => '', 'result' => '']) ?>,
            _cooldownInterval: null,

            // ── Lock state (diinisiasi dari PHP saat halaman dimuat) ──
            lockInfo: <?= json_encode($lockState ?: ['suite_title' => '', 'user' => '', 'running_since_label' => '']) ?>,
            _lockPollInterval: null,
            clearingLocks: false,

            init() {
                this.suites.forEach(s => {
                    s.status = 'READY';
                    s.duration = 0.0;
                    s.output = '';
                });

                // Jika halaman dibuka saat cooldown masih aktif, langsung mulai countdown
                if (this.cooldownRemaining > 0) {
                    this._startCooldownTimer();
                }

                // Jika halaman dibuka saat locked, mulai polling status lock setiap 5 detik
                if (this.lockInfo && this.lockInfo.user) {
                    this._startLockPolling();
                }

                // Global hotkeys: 'R' to run all, 'Esc' to stop
                document.addEventListener('keydown', (e) => {
                    if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
                    if (e.key === 'r' || e.key === 'R') {
                        if (this.canRun) this.runAll();
                    } else if (e.key === 'Escape') {
                        if (this.isRunning) this.stop();
                    }
                });
            },

            // ── Cooldown computed ──
            get isInCooldown() {
                return this.cooldownRemaining > 0;
            },
            get cooldownProgressPct() {
                if (this.cooldownTotal <= 0) return 0;
                return Math.round((this.cooldownRemaining / this.cooldownTotal) * 100);
            },

            // ── Lock computed — hanya true jika ada data lock yang sesungguhnya valid ──
            get isGloballyLocked() {
                if (!this.lockInfo) return false;
                const hasUser  = typeof this.lockInfo.user === 'string' && this.lockInfo.user.trim() !== '';
                const hasSuite = typeof this.lockInfo.suite_title === 'string' && this.lockInfo.suite_title.trim() !== '';
                // Tampilkan jika salah satu ada (termasuk stale dengan data partial)
                return hasUser || hasSuite;
            },

            // ── Can run? ──
            get canRun() {
                return !this.isRunning && !this.isInCooldown && !this.isGloballyLocked;
            },

            // ── Timer & Polling methods ──
            _startCooldownTimer() {
                if (this._cooldownInterval) clearInterval(this._cooldownInterval);
                this._cooldownInterval = setInterval(() => {
                    if (this.cooldownRemaining > 0) {
                        this.cooldownRemaining--;
                    } else {
                        clearInterval(this._cooldownInterval);
                        this._cooldownInterval = null;
                        // Reset cooldownInfo agar banner hilang
                        this.cooldownInfo = { suite_title: '', user: '', result: '' };
                    }
                }, 1000);
            },

            _applyCooldown(cooldownUntil, suiteTitle, user, result) {
                const remaining = Math.max(0, cooldownUntil - Math.floor(Date.now() / 1000));
                this.cooldownRemaining = remaining;
                this.cooldownInfo = { suite_title: suiteTitle || '', user: user || '', result: result || '' };
                if (remaining > 0) {
                    this._startCooldownTimer();
                }
            },

            _startLockPolling() {
                // Hentikan polling lama jika ada
                if (this._lockPollInterval) clearInterval(this._lockPollInterval);
                // Poll setiap 5 detik untuk cek apakah lock sudah dilepas
                this._lockPollInterval = setInterval(async () => {
                    try {
                        const resp = await fetch(window.location.href, {
                            method: 'GET',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            cache: 'no-store',
                        });
                        if (resp.ok) {
                            clearInterval(this._lockPollInterval);
                            this._lockPollInterval = null;
                            this.lockInfo = { suite_title: '', user: '', running_since_label: '' };
                        }
                    } catch (e) { /* ignore */ }
                }, 5000);
            },

            // ── Clear stale lock/cooldown files via backend endpoint ──
            async clearLocks() {
                if (this.clearingLocks) return;
                this.clearingLocks = true;

                const formData = new FormData();
                formData.append('csrf_token', this.csrfToken);

                try {
                    const resp = await fetch('<?= Router::url("/developer/tests/clear-locks") ?>', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    const data = await resp.json();

                    if (data.success) {
                        // Reset semua state lock & cooldown
                        this.cooldownRemaining = 0;
                        this.cooldownInfo = { suite_title: '', user: '', result: '' };
                        this.lockInfo = { suite_title: '', user: '', running_since_label: '' };

                        if (this._cooldownInterval) {
                            clearInterval(this._cooldownInterval);
                            this._cooldownInterval = null;
                        }
                        if (this._lockPollInterval) {
                            clearInterval(this._lockPollInterval);
                            this._lockPollInterval = null;
                        }
                    }
                } catch (e) {
                    console.error('clearLocks error:', e);
                } finally {
                    this.clearingLocks = false;
                }
            },

            // ── Suite counts ──
            get totalSuites() {
                return this.suites.length;
            },
            get passedCount() {
                return this.suites.filter(s => s.status === 'PASS').length;
            },
            get failedCount() {
                return this.suites.filter(s => s.status === 'FAIL').length;
            },
            get completedCount() {
                return this.passedCount + this.failedCount;
            },

            // ── Progress ──
            get progressPercentage() {
                if (this.totalSuites === 0) return 0;
                return (this.completedCount / this.totalSuites) * 100;
            },
            get passPercentage() {
                if (this.totalSuites === 0) return 0;
                return Math.round((this.passedCount / this.totalSuites) * 100);
            },

            // ── Status Hero ──
            get heroClass() {
                if (this.isRunning) return 'is-running';
                if (this.failedCount > 0) return 'is-fail';
                if (this.passedCount === this.totalSuites && this.totalSuites > 0) return 'is-pass';
                return '';
            },
            get indicatorClass() {
                if (this.isRunning) return 'running';
                if (this.failedCount > 0) return 'fail';
                if (this.passedCount === this.totalSuites && this.totalSuites > 0) return 'pass';
                return '';
            },
            get headlineText() {
                if (this.isRunning)          return 'EXECUTING SUITES // REAL-TIME RUNNER ACTIVE';
                if (this.isInCooldown)       return 'COOLDOWN AKTIF // TUNGGU ' + this.cooldownRemaining + ' DETIK';
                if (this.isGloballyLocked)   return 'LOCKED // TEST BERJALAN OLEH USER LAIN';
                if (this.completedCount === 0) return 'SYSTEM TEST CONSOLE // READY TO RUN (' + this.totalSuites + ' SUITES)';
                if (this.failedCount > 0)    return 'EXECUTION COMPLETED // ' + this.failedCount + ' SUITES FAILED';
                if (this.passedCount === this.totalSuites) return 'ALL ' + this.totalSuites + ' TEST SUITES PASSED // SYSTEM 100% HEALTHY';
                return 'IDLE';
            },
            get tagClass() {
                if (this.isRunning)        return 'running';
                if (this.isInCooldown)     return 'running';
                if (this.failedCount > 0)  return 'fail';
                if (this.passedCount === this.totalSuites && this.totalSuites > 0) return 'pass';
                return '';
            },
            get tagText() {
                if (this.isRunning)        return 'RUNNING (' + this.completedCount + '/' + this.totalSuites + ')';
                if (this.isInCooldown)     return 'COOLDOWN ' + this.cooldownRemaining + 's';
                if (this.isGloballyLocked) return 'LOCKED';
                if (this.completedCount === 0) return 'READY';
                if (this.failedCount > 0)  return this.failedCount + ' FAILED';
                if (this.passedCount === this.totalSuites) return '100% PASS';
                return 'PAUSED';
            },
            get progressStatusText() {
                if (this.isRunning) {
                    return 'Executing suite ' + (this.completedCount + 1) + ' of ' + this.totalSuites + '...';
                }
                if (this.isInCooldown) {
                    return '⏱ Cooldown aktif — tunggu ' + this.cooldownRemaining + ' detik sebelum run berikutnya';
                }
                if (this.completedCount === this.totalSuites && this.totalSuites > 0) {
                    return this.failedCount === 0
                        ? '🎉 Completed: All ' + this.totalSuites + ' test suites passed successfully (100%)'
                        : '⚠️ Completed: ' + this.failedCount + ' test suite(s) failed';
                }
                if (this.completedCount > 0) {
                    return 'Paused (' + this.completedCount + ' suites finished)';
                }
                return 'Ready to execute';
            },

            // ── Filters ──
            get categories() {
                const cats = new Set();
                this.suites.forEach(s => { if (s.category) cats.add(s.category); });
                return Array.from(cats);
            },
            get filteredSuites() {
                return this.suites.filter(s => {
                    const matchCat = (this.activeCategory === 'all' || s.category === this.activeCategory);
                    const query = this.searchQuery.toLowerCase().trim();
                    const matchQuery = !query ||
                        s.title.toLowerCase().includes(query) ||
                        s.file.toLowerCase().includes(query) ||
                        (s.description && s.description.toLowerCase().includes(query));
                    return matchCat && matchQuery;
                });
            },

            // ── Log Accordion ──
            toggleLog(key) {
                if (this.expandedLogs.includes(key)) {
                    this.expandedLogs = this.expandedLogs.filter(k => k !== key);
                } else {
                    this.expandedLogs.push(key);
                }
            },
            isLogExpanded(key) {
                return this.expandedLogs.includes(key);
            },
            copyLog(text) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        alert('Terminal log copied to clipboard!');
                    });
                }
            },

            // ── Controls ──
            resetAll() {
                this.totalDuration = 0;
                this.expandedLogs  = [];
                this.suites.forEach(s => {
                    s.status   = 'READY';
                    s.duration = 0.0;
                    s.output   = '';
                });
            },
            stop() {
                this.shouldStop = true;
            },

            // ── Core: Run Single Suite ──
            async runSingle(key) {
                if (!this.canRun && !this.isRunning) return; // Blokir jika cooldown/lock aktif

                const suite = this.suites.find(s => s.key === key);
                if (!suite) return;

                suite.status = 'RUNNING';

                const formData = new FormData();
                formData.append('suite_key', key);
                formData.append('csrf_token', this.csrfToken);

                try {
                    const resp = await fetch('<?= Router::url("/developer/tests/run-single") ?>', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    const data = await resp.json();

                    // ── Handle Cooldown Response (HTTP 429 - blocked: cooldown) ──
                    if (data.blocked === 'cooldown') {
                        suite.status = 'READY';
                        suite.output = data.error || 'Cooldown aktif.';
                        this._applyCooldown(
                            data.cooldown_until || (Math.floor(Date.now() / 1000) + 60),
                            data.last_suite_title,
                            data.last_run_user,
                            data.last_suite_result
                        );
                        this.isRunning = false;
                        this.shouldStop = true;
                        return;
                    }

                    // ── Handle Lock Response (HTTP 429 - blocked: lock) ──
                    if (data.blocked === 'lock') {
                        suite.status = 'READY';
                        suite.output = data.error || 'Test dikunci oleh user lain.';
                        this.lockInfo = {
                            suite_title: data.running_suite_title || '',
                            user: data.running_user || '',
                            running_since_label: data.running_since || '',
                        };
                        this._startLockPolling();
                        this.isRunning = false;
                        this.shouldStop = true;
                        return;
                    }

                    // ── Handle Normal Result ──
                    if (data.status === 'PASS' || data.success) {
                        suite.status   = 'PASS';
                        suite.duration = data.duration || 0.0;
                        suite.output   = data.output || '';
                    } else {
                        suite.status   = 'FAIL';
                        suite.duration = data.duration || 0.0;
                        suite.output   = data.output || data.error || 'Test failed';
                        if (!this.expandedLogs.includes(key)) {
                            this.expandedLogs.push(key);
                        }
                    }

                    // ── Aktifkan cooldown setelah suite selesai (PASS atau FAIL) ──
                    if (data.cooldown_until) {
                        this._applyCooldown(
                            data.cooldown_until,
                            suite.title,
                            <?= json_encode(Auth::name() ?? '') ?>,
                            data.status || 'FAIL'
                        );
                    }

                } catch (err) {
                    suite.status   = 'FAIL';
                    suite.duration = 0.0;
                    suite.output   = 'Execution error: ' + err.message;
                }

                this.calculateTotalDuration();
            },

            // ── Core: Run All Suites ──
            async runAll() {
                if (!this.canRun) return;

                this.isRunning  = true;
                this.shouldStop = false;
                this.resetAll();

                for (let i = 0; i < this.suites.length; i++) {
                    if (this.shouldStop || this.isInCooldown) {
                        break;
                    }
                    const suite = this.suites[i];
                    await this.runSingle(suite.key);

                    // Setelah setiap suite selesai, tunggu cooldown habis sebelum lanjut
                    if (this.isInCooldown && i < this.suites.length - 1) {
                        await new Promise(resolve => {
                            const check = setInterval(() => {
                                if (!this.isInCooldown || this.shouldStop) {
                                    clearInterval(check);
                                    resolve();
                                }
                            }, 500);
                        });
                    }
                }

                this.isRunning = false;
            },

            calculateTotalDuration() {
                this.totalDuration = this.suites.reduce((sum, s) => sum + (parseFloat(s.duration) || 0), 0);
            }
        };
    }
    </script>
</body>
</html>

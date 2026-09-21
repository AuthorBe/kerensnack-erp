<?php
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div class="space-y-6 pb-16">

    <!-- ========================================================================= -->
    <!-- 1. DEVELOPER HERO & TELEMETRY BANNER                                      -->
    <!-- ========================================================================= -->
    <div class="card p-5 sm:p-6" style="border-radius:18px;border:1px solid var(--color-hairline-strong);background:var(--color-canvas);box-shadow:var(--shadow-1);">
        <!-- Top Section: Header Info & User Profile Pill -->
        <div class="flex flex-col lg:flex-row items-start justify-between gap-4">
            <!-- Left: Terminal Icon + Hub Badges + Title -->
            <div class="flex items-start gap-3.5 sm:gap-4 flex-1 min-w-0">
                <div style="width:48px;height:48px;border-radius:14px;background:rgba(59,130,246,0.12);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(59,130,246,0.25);">
                    <i data-lucide="terminal" style="width:26px;height:26px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-1.5 sm:gap-2 flex-wrap mb-1.5">
                        <span class="badge badge-primary" style="font-size:10px;font-weight:800;letter-spacing:0.04em;padding:2px 8px;">DEVELOPER PORTAL HUB</span>
                        <span style="font-size:11px;font-weight:700;color:var(--color-success);display:inline-flex;align-items:center;gap:4px;">
                            <span style="width:6px;height:6px;border-radius:9999px;background:var(--color-success);display:inline-block;"></span>
                            Core Environment Online
                        </span>
                        <span class="badge badge-mono" style="font-size:10px;">PHP <?= htmlspecialchars($telemetry['php_version'] ?? PHP_VERSION) ?></span>
                        <span class="badge badge-mono" style="font-size:10px;"><?= $telemetry['total_tables'] ?? 45 ?> Tables</span>
                        <span class="badge badge-mono" style="font-size:10px;"><?= $telemetry['total_suites'] ?? count(\App\Services\TestRunnerService::SUITES) ?> Test Suites</span>
                    </div>
                    <h1 style="font-size:19px;font-weight:900;color:var(--color-ink);line-height:1.25;">
                        Pusat Kendali Pengembang &amp; Pengujian Sistem
                    </h1>
                    <p style="font-size:12.5px;color:var(--color-ink-mute);margin-top:4px;line-height:1.5;">
                        Portal eksklusif untuk peran Developer: Visual Blueprint Arsitektur, Diagnostik Koneksi Database, dan Test Source Runner otomatis.
                    </p>
                </div>
            </div>

            <!-- Right: Compact User Identity Profile Chip (Generous spacing, crisp typography) -->
            <div class="inline-flex items-center gap-3 flex-shrink-0" style="padding:10px 14px;border-radius:12px;border:1px solid var(--color-hairline);background:var(--color-canvas-soft);align-self:flex-start;">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(59,130,246,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;border:1px solid rgba(59,130,246,0.2);flex-shrink:0;">
                    <i data-lucide="user-check" style="width:18px;height:18px;"></i>
                </div>
                <div class="flex flex-col justify-center">
                    <span style="font-size:9.5px;color:var(--color-ink-mute);font-weight:800;letter-spacing:0.05em;text-transform:uppercase;margin-bottom:2px;display:block;">PENGGUNA AKTIF</span>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span style="font-size:13.5px;font-weight:900;color:var(--color-ink);letter-spacing:-0.01em;"><?= htmlspecialchars($telemetry['active_user'] ?? 'Developer') ?></span>
                        <span class="badge" style="font-size:10px;font-weight:700;padding:2px 8px;border-radius:6px;background:rgba(59,130,246,0.12);color:var(--color-primary);border:1px solid rgba(59,130,246,0.25);">Role: <?= htmlspecialchars($telemetry['active_role'] ?? 'developer') ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom Action Bar: Spacious & Clean Separator -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 sm:gap-4" style="margin-top:20px;padding-top:16px;border-top:1px solid var(--color-hairline);">
            <div class="flex items-center gap-2 text-xs font-bold" style="color:var(--color-ink-mute);">
                <i data-lucide="shield-check" style="width:16px;height:16px;color:var(--color-primary);flex-shrink:0;"></i>
                <span>Pratinjau Halaman Proteksi &amp; Error:</span>
            </div>
            
            <div class="grid grid-cols-2 gap-2.5 sm:flex sm:items-center sm:gap-3">
                <a href="<?= Router::url('/developer/preview-403') ?>" class="btn btn-secondary justify-center text-center" style="font-size:12px;font-weight:700;padding:8px 14px;border-radius:10px;display:inline-flex;align-items:center;gap:6px;border:1px solid rgba(244,63,94,0.3);background:rgba(244,63,94,0.08);color:#f43f5e;white-space:nowrap;transition:all 0.15s ease;">
                    <i data-lucide="gift" style="width:14px;height:14px;flex-shrink:0;"></i>
                    <span>Preview 403 Kado</span>
                </a>
                <a href="<?= Router::url('/developer/preview-restricted') ?>" class="btn btn-secondary justify-center text-center" style="font-size:12px;font-weight:700;padding:8px 14px;border-radius:10px;display:inline-flex;align-items:center;gap:6px;border:1px solid rgba(56,189,248,0.3);background:rgba(56,189,248,0.08);color:#0284c7;white-space:nowrap;transition:all 0.15s ease;">
                    <i data-lucide="shield-alert" style="width:14px;height:14px;flex-shrink:0;"></i>
                    <span>Preview Restricted</span>
                </a>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. TIGA KARTU PORTAL UTAMA (ARSITEKTUR, TEST DB, TEST SOURCE)             -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        <!-- KARTU 1: ARSITEKTUR SISTEM & BLUEPRINT AI -->
        <div class="card flex flex-col justify-between p-6 transition-all duration-200 hover:shadow-lg" style="border-radius:18px;border:1px solid var(--color-hairline-strong);background:var(--color-canvas);position:relative;overflow:hidden;">
            <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg, #3b82f6, #60a5fa);"></div>
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div style="width:44px;height:44px;border-radius:12px;background:rgba(59,130,246,0.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;border:1px solid rgba(59,130,246,0.2);">
                        <i data-lucide="network" style="width:22px;height:22px;"></i>
                    </div>
                    <span class="badge badge-primary" style="font-size:10px;font-weight:700;">MENU 1</span>
                </div>
                <h3 style="font-size:17px;font-weight:800;color:var(--color-ink);margin-bottom:6px;">
                    1. Arsitektur Sistem &amp; AI Studio
                </h3>
                <p style="font-size:12.5px;color:var(--color-ink-mute);line-height:1.5;margin-bottom:16px;">
                    Peta relasi 45 tabel database PostgreSQL Supabase, kamus skema data, daftar stored procedures/triggers, dan generator prompt terpadu untuk AI coding assistant.
                </p>

                <div class="flex flex-wrap gap-1.5 mb-6">
                    <span class="badge badge-mono" style="font-size:10px;"><?= $telemetry['total_tables'] ?? 45 ?> Tabel Publik</span>
                    <span class="badge badge-mono" style="font-size:10px;"><?= $telemetry['total_procedures'] ?? 30 ?> Prosedur RPC</span>
                    <span class="badge badge-mono" style="font-size:10px;">AI Prompt Studio</span>
                </div>
            </div>

            <a href="<?= Router::url('/developer/architecture') ?>" class="btn btn-primary w-full justify-center" style="font-size:13px;font-weight:700;padding:10px;border-radius:10px;">
                <span>Buka Arsitektur Sistem</span>
                <i data-lucide="arrow-right" style="width:16px;height:16px;margin-left:6px;"></i>
            </a>
        </div>

        <!-- KARTU 2: DIAGNOSTIK DATABASE (TEST DB) -->
        <div class="card flex flex-col justify-between p-6 transition-all duration-200 hover:shadow-lg" style="border-radius:18px;border:1px solid var(--color-hairline-strong);background:var(--color-canvas);position:relative;overflow:hidden;">
            <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg, #10b981, #34d399);"></div>
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div style="width:44px;height:44px;border-radius:12px;background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;border:1px solid rgba(16,185,129,0.2);">
                        <i data-lucide="database" style="width:22px;height:22px;"></i>
                    </div>
                    <span class="badge badge-success" style="font-size:10px;font-weight:700;">MENU 2</span>
                </div>
                <h3 style="font-size:17px;font-weight:800;color:var(--color-ink);margin-bottom:6px;">
                    2. Diagnostik Database (Test DB)
                </h3>
                <p style="font-size:12.5px;color:var(--color-ink-mute);line-height:1.5;margin-bottom:16px;">
                    Konsol healthcheck koneksi SSL pooler Supabase, pengukuran roundtrip latency ping, validasi RPC barcode universal, perhitungan harga bertingkat, dan status seeding master.
                </p>

                <div class="flex flex-wrap gap-1.5 mb-6">
                    <span class="badge badge-mono" style="font-size:10px;">PostgreSQL 17</span>
                    <span class="badge badge-mono" style="font-size:10px;">SSL Handshake</span>
                    <span class="badge badge-mono" style="font-size:10px;">Ping &bull; JSON API</span>
                    <span class="badge badge-mono" style="font-size:10px;">CLI Support</span>
                </div>
            </div>

            <a href="<?= Router::url('/developer/test-db') ?>" class="btn w-full justify-center" style="font-size:13px;font-weight:700;padding:10px;border-radius:10px;background:#10b981;color:#fff;border:none;">
                <span>Buka Konsol Test DB</span>
                <i data-lucide="activity" style="width:16px;height:16px;margin-left:6px;"></i>
            </a>
        </div>

        <!-- KARTU 3: TEST SOURCE / RUN-ALL CONSOLE -->
        <div class="card flex flex-col justify-between p-6 transition-all duration-200 hover:shadow-lg" style="border-radius:18px;border:1px solid var(--color-hairline-strong);background:var(--color-canvas);position:relative;overflow:hidden;">
            <div style="position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg, #8b5cf6, #a78bfa);"></div>
            <div>
                <div class="flex items-center justify-between mb-4">
                    <div style="width:44px;height:44px;border-radius:12px;background:rgba(139,92,246,0.1);color:#8b5cf6;display:flex;align-items:center;justify-content:center;border:1px solid rgba(139,92,246,0.2);">
                        <i data-lucide="check-circle-2" style="width:22px;height:22px;"></i>
                    </div>
                    <span class="badge badge-purple" style="font-size:10px;font-weight:700;">MENU 3</span>
                </div>
                <h3 style="font-size:17px;font-weight:800;color:var(--color-ink);margin-bottom:6px;">
                    3. Test Source / Run-All Console
                </h3>
                <p style="font-size:12.5px;color:var(--color-ink-mute);line-height:1.5;margin-bottom:16px;">
                    Pengujian otomatis seluruh <?= $telemetry['total_suites'] ?? 27 ?> test suites lifecycle ERP (POS Kasir, Order B2B, Hutang Supplier, Surat Jalan POD, Buku Kas, Komisi &amp; RBAC). Menjamin 100% Zero Data Pollution.
                </p>

                <div class="flex flex-wrap gap-1.5 mb-6">
                    <span class="badge badge-mono" style="font-size:10px;"><?= $telemetry['total_suites'] ?? 27 ?> Test Suites</span>
                    <span class="badge badge-mono" style="font-size:10px;">Real-time AJAX</span>
                    <span class="badge badge-mono" style="font-size:10px;">Auto Rollback</span>
                    <span class="badge badge-mono" style="font-size:10px;">Anti-Timeout</span>
                </div>
            </div>

            <a href="<?= Router::url('/developer/tests') ?>" class="btn w-full justify-center" style="font-size:13px;font-weight:700;padding:10px;border-radius:10px;background:#8b5cf6;color:#fff;border:none;">
                <span>Buka Test Runner Console</span>
                <i data-lucide="play" style="width:16px;height:16px;margin-left:6px;"></i>
            </a>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. SIMULATOR & PENGUJIAN SESI INAKTIF (TRIAL CONSOLE)                      -->
    <!-- ========================================================================= -->
    <div class="card p-6" style="border-radius:18px;border:1px solid var(--color-hairline-strong);background:var(--color-canvas);box-shadow:var(--shadow-1);">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-5 pb-4" style="border-bottom:1px solid var(--color-hairline);">
            <div class="flex items-start sm:items-center gap-3">
                <div style="width:44px;height:44px;border-radius:12px;background:rgba(225,29,72,0.1);color:#e11d48;display:flex;align-items:center;justify-content:center;border:1px solid rgba(225,29,72,0.25);flex-shrink:0;">
                    <i data-lucide="timer" style="width:22px;height:22px;"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="badge badge-error" style="font-size:10px;font-weight:700;background:rgba(225,29,72,0.12);color:#e11d48;border:1px solid rgba(225,29,72,0.25);">DEVELOPER TRIAL CONSOLE</span>
                        <span class="badge badge-mono" style="font-size:10px;">Sliding Idle 1 Jam</span>
                        <span class="badge badge-mono" style="font-size:10px;color:var(--color-success);" id="sim-status-badge">Engine Online</span>
                    </div>
                    <h3 style="font-size:17px;font-weight:800;color:var(--color-ink);margin-top:2px;">
                        Simulator Masa Aktif Sesi &amp; Inactivity Timeout
                    </h3>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs">
                <span class="text-muted" style="font-size:11.5px;color:var(--color-ink-mute);">Aktivitas Terakhir:</span>
                <span class="badge badge-mono font-bold" id="sim-last-act-time" style="font-size:11px;color:var(--color-primary);"><?= $telemetry['last_activity'] ?? date('d M Y H:i:s') ?></span>
            </div>
        </div>

        <!-- Telemetry Status Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="p-3 rounded-xl" style="background:var(--color-surface-2, rgba(0,0,0,0.03));border:1px solid var(--color-hairline);">
                <div class="text-xs text-muted font-medium mb-1" style="color:var(--color-ink-mute);">Durasi Timeout</div>
                <div class="text-sm font-bold" id="sim-timeout-val" style="color:var(--color-ink);font-variant-numeric:tabular-nums;">3600s (1 Jam)</div>
            </div>
            <div class="p-3 rounded-xl" style="background:var(--color-surface-2, rgba(0,0,0,0.03));border:1px solid var(--color-hairline);">
                <div class="text-xs text-muted font-medium mb-1" style="color:var(--color-ink-mute);">Peringatan (Warning)</div>
                <div class="text-sm font-bold" id="sim-warning-val" style="color:#d97706;font-variant-numeric:tabular-nums;">300s (5 Menit)</div>
            </div>
            <div class="p-3 rounded-xl" style="background:var(--color-surface-2, rgba(0,0,0,0.03));border:1px solid var(--color-hairline);">
                <div class="text-xs text-muted font-medium mb-1" style="color:var(--color-ink-mute);">Heartbeat Ping</div>
                <div class="text-sm font-bold" style="color:#10b981;font-variant-numeric:tabular-nums;">Otomatis (5 Menit)</div>
            </div>
            <div class="p-3 rounded-xl" style="background:var(--color-surface-2, rgba(0,0,0,0.03));border:1px solid var(--color-hairline);">
                <div class="text-xs text-muted font-medium mb-1" style="color:var(--color-ink-mute);">Status Sesi</div>
                <div class="text-sm font-bold flex items-center gap-1.5" style="color:#10b981;">
                    <span style="width:6px;height:6px;border-radius:9999px;background:#10b981;display:inline-block;"></span>
                    <span>Aktif &amp; Terverifikasi</span>
                </div>
            </div>
        </div>

        <!-- Action Control Buttons -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mb-6">
            <button id="sim-btn-1" type="button" onclick="simTriggerWarning(this)" class="btn btn-secondary justify-center text-xs font-bold" style="padding:10px 14px;border-radius:12px;border:1px solid rgba(245,158,11,0.3);background:rgba(245,158,11,0.08);color:#d97706;">
                <i data-lucide="bell-ring" style="width:15px;height:15px;margin-right:6px;"></i>
                <span>1. Test Modal Warning (10s)</span>
            </button>
            <button id="sim-btn-2" type="button" onclick="simTriggerFastTimeout(this)" class="btn btn-secondary justify-center text-xs font-bold" style="padding:10px 14px;border-radius:12px;border:1px solid rgba(225,29,72,0.3);background:rgba(225,29,72,0.08);color:#e11d48;">
                <i data-lucide="zap" style="width:15px;height:15px;margin-right:6px;"></i>
                <span>2. Test Fast Timeout (15s)</span>
            </button>
            <button id="sim-btn-3" type="button" onclick="simSendHeartbeat(this)" class="btn btn-secondary justify-center text-xs font-bold" style="padding:10px 14px;border-radius:12px;border:1px solid rgba(16,185,129,0.3);background:rgba(16,185,129,0.08);color:#059669;">
                <i data-lucide="activity" style="width:15px;height:15px;margin-right:6px;"></i>
                <span>3. Ping Heartbeat API</span>
            </button>
            <button id="sim-btn-4" type="button" onclick="simResetSession(this)" class="btn btn-secondary justify-center text-xs font-bold" style="padding:10px 14px;border-radius:12px;border:1px solid var(--color-hairline);">
                <i data-lucide="rotate-ccw" style="width:15px;height:15px;margin-right:6px;"></i>
                <span>4. Reset Standar (1 Jam)</span>
            </button>
        </div>

        <!-- Live Simulation Console Log Header & Box -->
        <div class="p-5 sm:p-6" style="border-radius:18px;border:1px solid #1e293b;background:#070a12;color:#f8fafc;box-shadow:0 10px 25px -5px rgba(0,0,0,0.3);margin-top:6px;">
            <!-- Window Controls & Header Bar -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-4 pb-3.5" style="border-bottom:1px solid #1e293b;">
                <div class="flex items-center gap-3">
                    <!-- Traffic Lights -->
                    <div class="flex items-center gap-1.5 flex-shrink-0">
                        <div style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></div>
                        <div style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></div>
                        <div style="width:10px;height:10px;border-radius:50%;background:#10b981;"></div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span style="font-family:'JetBrains Mono', monospace;font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:0.06em;">TERMINAL SIMULATOR OUTPUT</span>
                        <span style="background:rgba(59,130,246,0.15);border:1px solid rgba(59,130,246,0.3);color:#60a5fa;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;font-family:'JetBrains Mono', monospace;">LIVE TELEMETRY STREAM</span>
                    </div>
                </div>
                <div class="flex items-center gap-2.5">
                    <button type="button" onclick="clearSimLog()" style="background:#1e293b;border:1px solid #334155;color:#94a3b8;padding:4px 10px;border-radius:6px;font-size:11px;font-family:'JetBrains Mono', monospace;cursor:pointer;display:inline-flex;align-items:center;gap:5px;transition:all 0.15s ease;" onmouseover="this.style.color='#f8fafc';this.style.background='#334155';" onmouseout="this.style.color='#94a3b8';this.style.background='#1e293b';">
                        <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                        <span>Bersihkan</span>
                    </button>
                </div>
            </div>

            <!-- Console Body Output -->
            <div class="p-3.5 sm:p-4" style="background:#040711;border:1px solid #1e293b;border-radius:10px;font-family:'JetBrains Mono', monospace;font-size:12px;line-height:1.8;color:#94a3b8;min-height:85px;max-height:180px;overflow-y:auto;" id="sim-log-console">
                <span style="color: #64748b;">[Ready]</span> Simulator sesi siap digunakan. Silakan klik salah satu tombol uji di atas untuk menguji alur secara instan.
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. TERMINAL CLI INTEGRATION & QUICK CHEATSHEET                             -->
    <!-- ========================================================================= -->
    <div class="card p-5 sm:p-6" style="border-radius:18px;border:1px solid var(--color-hairline-strong);background:#070a12;color:#f8fafc;box-shadow:0 10px 25px -5px rgba(0,0,0,0.3);">
        <!-- Window Controls & Header Bar -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5 pb-3.5" style="border-bottom:1px solid #1e293b;">
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5">
                    <div style="width:10px;height:10px;border-radius:50%;background:#ef4444;"></div>
                    <div style="width:10px;height:10px;border-radius:50%;background:#f59e0b;"></div>
                    <div style="width:10px;height:10px;border-radius:50%;background:#10b981;"></div>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    <span style="font-family:'JetBrains Mono', monospace;font-size:11px;font-weight:700;color:#94a3b8;letter-spacing:0.06em;">TERMINAL CLI SHORTCUTS</span>
                    <span style="background:rgba(59,130,246,0.15);border:1px solid rgba(59,130,246,0.3);color:#60a5fa;font-size:10px;font-weight:700;padding:2px 7px;border-radius:4px;font-family:'JetBrains Mono', monospace;">ZERO DUPLICATION ARCHITECTURE</span>
                </div>
            </div>
            <div class="flex items-center gap-2 text-xs" style="color:#64748b;font-family:'JetBrains Mono', monospace;">
                <span style="display:inline-block;width:6px;height:6px;border-radius:50%;background:#10b981;"></span>
                <span>PowerShell / Bash Ready</span>
            </div>
        </div>

        <!-- 2 Symmetrical CLI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Card 1: Test DB -->
            <div class="p-4 flex flex-col justify-between" style="background:#0d121f;border:1px solid #1e293b;border-radius:12px;min-height:140px;">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <div style="width:26px;height:26px;border-radius:6px;background:rgba(56,189,248,0.12);border:1px solid rgba(56,189,248,0.25);display:flex;align-items:center;justify-content:center;color:#38bdf8;flex-shrink:0;">
                            <i data-lucide="terminal" style="width:14px;height:14px;"></i>
                        </div>
                        <span style="font-size:13px;font-weight:800;color:#f8fafc;letter-spacing:0.01em;">1. Jalankan Test DB via Terminal CLI</span>
                    </div>
                    <p style="font-size:12px;color:#94a3b8;line-height:1.5;margin-bottom:12px;">
                        Mengecek konektivitas database Supabase, SSL pooler, 45 tabel relasi, dan integritas RPC secara instan:
                    </p>
                </div>
                <div class="flex items-center justify-between gap-3 p-2.5 mt-auto" style="background:#040711;border:1px solid #1e293b;border-radius:8px;">
                    <div class="flex items-center gap-2 overflow-x-auto py-0.5">
                        <span style="color:#64748b;font-family:monospace;font-weight:700;user-select:none;">$</span>
                        <code style="font-family:'JetBrains Mono', monospace;font-size:12px;color:#38bdf8;font-weight:600;white-space:nowrap;">php developer/test_db.php</code>
                    </div>
                    <button type="button" onclick="copyCliCommand('php developer/test_db.php', this)" title="Salin perintah ke clipboard" style="background:#1e293b;border:1px solid #334155;color:#94a3b8;padding:4px 9px;border-radius:6px;font-size:11px;font-family:'JetBrains Mono', monospace;cursor:pointer;display:inline-flex;align-items:center;gap:4px;flex-shrink:0;transition:all 0.15s ease;" onmouseover="this.style.color='#f8fafc';this.style.background='#334155';" onmouseout="this.style.color='#94a3b8';this.style.background='#1e293b';">
                        <i data-lucide="copy" style="width:12px;height:12px;"></i>
                        <span>Copy</span>
                    </button>
                </div>
            </div>

            <!-- Card 2: Run All Tests -->
            <div class="p-4 flex flex-col justify-between" style="background:#0d121f;border:1px solid #1e293b;border-radius:12px;min-height:140px;">
                <div>
                    <div class="flex items-center gap-2 mb-2">
                        <div style="width:26px;height:26px;border-radius:6px;background:rgba(167,139,250,0.12);border:1px solid rgba(167,139,250,0.25);display:flex;align-items:center;justify-content:center;color:#a78bfa;flex-shrink:0;">
                            <i data-lucide="play" style="width:14px;height:14px;"></i>
                        </div>
                        <span style="font-size:13px;font-weight:800;color:#f8fafc;letter-spacing:0.01em;">2. Jalankan Seluruh <?= $telemetry['total_suites'] ?? 27 ?> Test Suites via CLI</span>
                    </div>
                    <p style="font-size:12px;color:#94a3b8;line-height:1.5;margin-bottom:12px;">
                        Mengeksekusi <?= $telemetry['total_suites'] ?? 27 ?> test suites terpadu secara batch lengkap dengan tabel kalkulasi waktu dan status:
                    </p>
                </div>
                <div class="flex items-center justify-between gap-3 p-2.5 mt-auto" style="background:#040711;border:1px solid #1e293b;border-radius:8px;">
                    <div class="flex items-center gap-2 overflow-x-auto py-0.5">
                        <span style="color:#64748b;font-family:monospace;font-weight:700;user-select:none;">$</span>
                        <code style="font-family:'JetBrains Mono', monospace;font-size:12px;color:#a78bfa;font-weight:600;white-space:nowrap;">php tests/run_all.php</code>
                    </div>
                    <button type="button" onclick="copyCliCommand('php tests/run_all.php', this)" title="Salin perintah ke clipboard" style="background:#1e293b;border:1px solid #334155;color:#94a3b8;padding:4px 9px;border-radius:6px;font-size:11px;font-family:'JetBrains Mono', monospace;cursor:pointer;display:inline-flex;align-items:center;gap:4px;flex-shrink:0;transition:all 0.15s ease;" onmouseover="this.style.color='#f8fafc';this.style.background='#334155';" onmouseout="this.style.color='#94a3b8';this.style.background='#1e293b';">
                        <i data-lucide="copy" style="width:12px;height:12px;"></i>
                        <span>Copy</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- Terminal Footer Notes -->
        <div class="mt-5 pt-3.5 flex flex-col sm:flex-row items-center justify-between gap-3" style="border-top:1px solid #1e293b;font-size:11.5px;color:#94a3b8;">
            <div class="flex items-center gap-2">
                <i data-lucide="shield-check" style="width:16px;height:16px;color:#10b981;flex-shrink:0;"></i>
                <span><strong style="color:#f1f5f9;">Keamanan Terjamin:</strong> Seluruh fungsi pengujian menggunakan transaksi rollback otomatis &mdash; database operasional 100% steril dan aman.</span>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0" style="font-family:'JetBrains Mono', monospace;font-size:11px;">
                <span style="color:#64748b;">Runtime:</span>
                <span style="background:#1e293b;color:#e2e8f0;padding:2px 8px;border-radius:5px;border:1px solid #334155;">PHP <?= htmlspecialchars(PHP_VERSION) ?></span>
            </div>
        </div>
    </div>

</div>

<script>
function copyCliCommand(cmd, btn) {
    var showSuccess = function() {
        var originalHtml = btn.innerHTML;
        var originalBorder = btn.style.borderColor;
        var originalBg = btn.style.background;
        var originalColor = btn.style.color;
        btn.innerHTML = '<span style="color:#10b981;font-weight:700;display:inline-flex;align-items:center;gap:4px;">✓ Copied!</span>';
        btn.style.borderColor = '#10b981';
        btn.style.background = 'rgba(16,185,129,0.12)';
        btn.style.color = '#10b981';
        setTimeout(function() {
            btn.innerHTML = originalHtml;
            btn.style.borderColor = originalBorder || '#334155';
            btn.style.background = originalBg || '#1e293b';
            btn.style.color = originalColor || '#94a3b8';
            if (window.lucide) window.lucide.createIcons();
        }, 1800);
    };

    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(cmd).then(showSuccess).catch(function() {
            fallbackCopy(cmd, showSuccess);
        });
    } else {
        fallbackCopy(cmd, showSuccess);
    }
}

function fallbackCopy(text, callback) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.top = '-9999px';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.focus();
    ta.select();
    try {
        var ok = document.execCommand('copy');
        if (ok && callback) callback();
    } catch(e) {}
    document.body.removeChild(ta);
}

/* =========================================================================
   SESSION TIMEOUT SIMULATOR (TRIAL HELPERS WITH ANTI-SPAM COOLDOWN)
   ========================================================================= */
function appendSimLog(msg, type = 'info') {
    var consoleEl = document.getElementById('sim-log-console');
    if (!consoleEl) return;
    var now = new Date().toTimeString().split(' ')[0];
    var color = '#94a3b8';
    if (type === 'success') color = '#10b981';
    if (type === 'warn') color = '#f59e0b';
    if (type === 'error') color = '#ef4444';
    if (type === 'primary') color = '#38bdf8';
    
    var line = document.createElement('div');
    line.style.padding = '3px 0';
    line.style.lineHeight = '1.6';
    line.innerHTML = '<span style="color:#64748b;font-weight:600;">[' + now + ']</span> <span style="color:' + color + ';">' + msg + '</span>';
    consoleEl.appendChild(line);
    consoleEl.scrollTop = consoleEl.scrollHeight;
}

function clearSimLog() {
    var consoleEl = document.getElementById('sim-log-console');
    if (!consoleEl) return;
    consoleEl.innerHTML = '<span style="color:#64748b;">[Ready]</span> Log telah dibersihkan.';
}

function simTriggerWarning(btn) {
    if (btn && btn.disabled) return;
    if (btn) {
        btn.disabled = true;
        setTimeout(function() { btn.disabled = false; }, 1200);
    }

    appendSimLog('Memicu modal peringatan countdown trial (10 detik)...', 'warn');
    if (window.SessionTimeoutEngine) {
        window.SessionTimeoutEngine.timeoutSeconds = 10;
        window.SessionTimeoutEngine.warningSeconds = 10;
        window.SessionTimeoutEngine._lastActivityTime = Date.now();
        window.SessionTimeoutEngine._showWarning(10);
        var tVal = document.getElementById('sim-timeout-val');
        var wVal = document.getElementById('sim-warning-val');
        var sBadge = document.getElementById('sim-status-badge');
        if (tVal) tVal.textContent = '10s (Trial Mode)';
        if (wVal) wVal.textContent = '10s (Trial Mode)';
        if (sBadge) {
            sBadge.textContent = 'Modal Testing (10s)';
            sBadge.style.color = '#d97706';
        }
        appendSimLog('Modal peringatan muncul! Latar belakang di-freeze. Coba klik "Lanjutkan Sesi".', 'success');
    } else {
        appendSimLog('SessionTimeoutEngine tidak ditemukan.', 'error');
    }
}

function simTriggerFastTimeout(btn) {
    if (btn && btn.disabled) return;
    if (btn) {
        btn.disabled = true;
        setTimeout(function() { btn.disabled = false; }, 1500);
    }

    appendSimLog('Mengaktifkan mode Fast Timeout: 15 detik (Warning di 5s, Auto-Logout di 0s)...', 'warn');
    if (window.SessionTimeoutEngine) {
        window.SessionTimeoutEngine.timeoutSeconds = 15;
        window.SessionTimeoutEngine.warningSeconds = 5;
        window.SessionTimeoutEngine._lastActivityTime = Date.now();
        var tVal = document.getElementById('sim-timeout-val');
        var wVal = document.getElementById('sim-warning-val');
        var sBadge = document.getElementById('sim-status-badge');
        if (tVal) tVal.textContent = '15s (Trial Mode)';
        if (wVal) wVal.textContent = '5s (Trial Mode)';
        if (sBadge) {
            sBadge.textContent = 'Trial Running (15s)';
            sBadge.style.color = '#ef4444';
        }
        appendSimLog('Sistem akan idle 10 detik, lalu modal countdown muncul 5 detik, kemudian auto-logout.', 'primary');
    }
}

function simSendHeartbeat(btn) {
    if (btn && btn.disabled) return;
    var origText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.style.opacity = '0.65';
        btn.innerHTML = '<span>Pinging...</span>';
    }

    appendSimLog('Mengirim live request ke POST /api/auth/heartbeat...', 'primary');
    var start = performance.now();
    var heartbeatUrl = (window.APP_BASE_PATH || '') + '/api/auth/heartbeat';
    
    fetch(heartbeatUrl, {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var latency = Math.round(performance.now() - start);
        if (data && data.success) {
            appendSimLog('✓ Heartbeat Berhasil (' + latency + 'ms) &mdash; Sesi PHP di server ter-refresh!', 'success');
            var timeEl = document.getElementById('sim-last-act-time');
            if (timeEl) timeEl.textContent = new Date().toLocaleTimeString() + ' WIB';
        } else {
            appendSimLog('✗ Heartbeat Gagal: ' + JSON.stringify(data), 'error');
        }
    })
    .catch(function(err) {
        appendSimLog('✗ Network Error Heartbeat: ' + err.message, 'error');
    })
    .finally(function() {
        setTimeout(function() {
            if (btn) {
                btn.disabled = false;
                btn.style.opacity = '1';
                btn.innerHTML = origText;
                if (window.lucide) window.lucide.createIcons();
            }
        }, 600);
    });
}

function simResetSession(btn) {
    if (btn && btn.disabled) return;
    if (btn) {
        btn.disabled = true;
        setTimeout(function() { btn.disabled = false; }, 1000);
    }

    if (window.SessionTimeoutEngine) {
        var alreadyStandard = window.SessionTimeoutEngine.timeoutSeconds === 3600 && !window.SessionTimeoutEngine._warningShown;
        window.SessionTimeoutEngine.timeoutSeconds = 3600;
        window.SessionTimeoutEngine.warningSeconds = 300;
        window.SessionTimeoutEngine.extendSession();
        var tVal = document.getElementById('sim-timeout-val');
        var wVal = document.getElementById('sim-warning-val');
        var sBadge = document.getElementById('sim-status-badge');
        if (tVal) tVal.textContent = '3600s (1 Jam)';
        if (wVal) wVal.textContent = '300s (5 Menit)';
        if (sBadge) {
            sBadge.textContent = 'Engine Online';
            sBadge.style.color = '#10b981';
        }
        
        if (alreadyStandard) {
            appendSimLog('ℹ Sesi sudah dalam mode standar produksi (1 Jam / 3.600s).', 'info');
        } else {
            appendSimLog('✓ Sesi berhasil direset kembali ke mode produksi (1 Jam / 3.600s).', 'success');
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

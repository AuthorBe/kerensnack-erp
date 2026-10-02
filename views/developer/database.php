<?php
use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;
ob_start();
?>

<style>
/* =============================================================================
   DATABASE MANAGER & TERMINAL CONSOLE STYLES (FULLY RESPONSIVE & PIXEL PERFECT)
   ============================================================================= */
.dev-db-container {
    padding-bottom: 48px;
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: 1400px;
    margin: 0 auto;
    width: 100%;
}

.dev-db-card {
    background: var(--color-canvas, #ffffff);
    border: 1px solid var(--color-hairline-strong, #cbd5e1);
    border-radius: 18px;
    padding: 24px 28px !important;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04), 0 1px 2px rgba(0, 0, 0, 0.02);
    position: relative;
    transition: box-shadow 0.2s ease;
}

.dev-db-card-header {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    padding-bottom: 16px;
    margin-bottom: 18px;
    border-bottom: 1px solid var(--color-hairline, #e2e8f0);
}

/* --- Hero Banner Badges --- */
.dev-hero-badge-local {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 9999px;
    background: rgba(16, 185, 129, 0.12);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.35);
}
.dev-hero-badge-live {
    font-size: 11px;
    font-weight: 700;
    padding: 4px 12px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border-radius: 9999px;
    background: rgba(225, 29, 72, 0.12);
    color: #e11d48;
    border: 1px solid rgba(225, 29, 72, 0.35);
}

/* --- Top Icon Boxes --- */
.dev-card-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.dev-card-icon-local {
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.dev-card-icon-live {
    background: rgba(225, 29, 72, 0.1);
    color: #e11d48;
    border: 1px solid rgba(225, 29, 72, 0.25);
}
.dev-card-icon-blue {
    background: rgba(37, 99, 235, 0.08);
    color: var(--color-primary, #2563eb);
    border: 1px solid rgba(37, 99, 235, 0.2);
}

/* --- Sub-Cards (Mode Lokal vs Mode Live) --- */
.dev-subcard {
    border-radius: 14px;
    padding: 16px 18px !important;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    transition: all 0.25s ease;
}
.dev-subcard-local-active {
    background: rgba(16, 185, 129, 0.07);
    border: 1.5px solid rgba(16, 185, 129, 0.35);
    box-shadow: 0 2px 8px rgba(16, 185, 129, 0.08);
    opacity: 1;
}
.dev-subcard-local-inactive {
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    opacity: 0.6;
}
.dev-subcard-live-active {
    background: rgba(225, 29, 72, 0.07);
    border: 1.5px solid rgba(225, 29, 72, 0.35);
    box-shadow: 0 2px 8px rgba(225, 29, 72, 0.08);
    opacity: 1;
}
.dev-subcard-live-inactive {
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline, #e2e8f0);
    opacity: 0.6;
}

.dev-subcard-icon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.dev-subcard-icon-emerald {
    background: rgba(16, 185, 129, 0.15);
    color: #10b981;
    border: 1px solid rgba(16, 185, 129, 0.25);
}
.dev-subcard-icon-rose {
    background: rgba(225, 29, 72, 0.15);
    color: #e11d48;
    border: 1px solid rgba(225, 29, 72, 0.25);
}

/* --- Switcher Action Buttons --- */
.dev-switch-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 16px !important;
    font-size: 12.5px;
    font-weight: 700;
    border-radius: 12px;
    transition: all 0.2s ease;
    cursor: pointer;
    border: 1px solid transparent;
    text-align: center;
}
.dev-switch-btn-local-active {
    background: #10b981;
    color: #ffffff;
    border-color: #10b981;
    box-shadow: 0 2px 6px rgba(16, 185, 129, 0.3);
}
.dev-switch-btn-local-disabled {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
    border-color: rgba(16, 185, 129, 0.3);
    opacity: 0.65;
    cursor: not-allowed;
}
.dev-switch-btn-live-active {
    background: #e11d48;
    color: #ffffff;
    border-color: #e11d48;
    box-shadow: 0 2px 6px rgba(225, 29, 72, 0.3);
}
.dev-switch-btn-live-disabled {
    background: rgba(225, 29, 72, 0.1);
    color: #dc2626;
    border-color: rgba(225, 29, 72, 0.3);
    opacity: 0.65;
    cursor: not-allowed;
}

/* --- Terminal Console Styles --- */
.dev-terminal-wrapper {
    border-radius: 14px;
    border: 1px solid #1e293b;
    background: #0b0f19;
    box-shadow: inset 0 1px 0 rgba(255,255,255,0.06), 0 10px 30px rgba(0, 0, 0, 0.4);
    font-family: var(--font-mono, 'JetBrains Mono', monospace);
    overflow: hidden;
}
.dev-terminal-topbar {
    display: flex;
    flex-wrap: nowrap;
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 16px !important;
    background: #111827;
    border-bottom: 1px solid #1e293b;
    min-height: 44px;
}
.dev-terminal-dots {
    display: flex;
    align-items: center;
    gap: 6px;
    flex-shrink: 0;
}
.dev-terminal-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
}
.dev-terminal-dot.red { background: #ff5f56; box-shadow: 0 0 5px rgba(255,95,86,0.5); }
.dev-terminal-dot.yellow { background: #ffbd2e; box-shadow: 0 0 5px rgba(255,189,46,0.5); }
.dev-terminal-dot.green { background: #27c93f; box-shadow: 0 0 5px rgba(39,201,63,0.5); }

.dev-terminal-divider {
    width: 1px;
    height: 14px;
    background: #334155;
    margin: 0 6px;
    display: inline-block;
}
.dev-terminal-path {
    font-family: var(--font-mono, 'JetBrains Mono', monospace);
    font-size: 11px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    min-width: 0;
}
.dev-terminal-screen {
    padding: 18px 20px !important;
    background: #0b0f19;
    color: #f1f5f9;
    font-family: var(--font-mono, 'JetBrains Mono', monospace);
    font-size: 12px;
    line-height: 1.7;
    min-height: 180px;
    max-height: 380px;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
}
.dev-terminal-cmd-line {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 14px !important;
    font-size: 12px;
    flex-wrap: wrap;
}
.dev-terminal-info-box {
    margin: 12px 0 16px 0 !important;
    padding: 14px 16px !important;
    border-radius: 12px;
    background: rgba(15, 23, 42, 0.85);
    border: 1px solid rgba(56, 189, 248, 0.2);
    border-left: 3.5px solid #38bdf8;
    color: #94a3b8;
    font-size: 11.5px;
    line-height: 1.6;
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.dev-terminal-info-item {
    display: flex;
    flex-direction: column;
    gap: 3px;
}
.dev-terminal-info-label {
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.03em;
}
.dev-terminal-info-value {
    color: #f8fafc;
    font-size: 11.5px;
    font-weight: 600;
    word-break: break-word;
}
.dev-terminal-cursor {
    display: inline-block;
    width: 8px;
    height: 14px;
    background: #10b981;
    margin-left: 4px;
    vertical-align: middle;
    animation: devTermBlink 1s step-start infinite;
}
@keyframes devTermBlink {
    0%, 100% { opacity: 1; }
    50% { opacity: 0; }
}
.dev-log-row {
    padding: 4px 8px !important;
    margin: 2px 0 !important;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 11.5px;
    border-radius: 6px;
    line-height: 1.6;
}
.dev-log-row:hover {
    background: rgba(30, 41, 59, 0.5);
}
.dev-log-lineno {
    min-width: 24px;
    color: #475569;
    user-select: none;
    font-size: 10.5px;
    padding-top: 1px;
}

/* =============================================================================
   RESPONSIVE BREAKPOINTS (MOBILE, TABLET, DESKTOP)
   ============================================================================= */
.dev-cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(min(100%, 340px), 1fr));
    gap: 20px;
}

.dev-hero-layout {
    display: flex;
    flex-direction: row;
    align-items: flex-start;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.dev-hero-info-box {
    padding: 14px 18px;
    border-radius: 12px;
    border: 1px solid var(--color-hairline, #e2e8f0);
    background: var(--color-canvas-soft, #f1f5f9);
    min-width: 220px;
    flex-shrink: 0;
}

.dev-btn-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
}

@media (max-width: 768px) {
    .dev-db-card {
        padding: 18px 20px !important;
        border-radius: 16px;
    }
    .dev-hero-layout {
        flex-direction: column;
    }
    .dev-hero-info-box {
        width: 100%;
        min-width: 0;
    }
    .dev-cards-grid {
        grid-template-columns: 1fr;
        gap: 16px;
    }
    .dev-terminal-screen {
        padding: 16px 18px !important;
    }
}

@media (max-width: 480px) {
    .dev-db-container {
        gap: 16px;
    }
    .dev-db-card {
        padding: 15px 16px !important;
        border-radius: 14px;
    }
    .dev-card-header-title {
        font-size: 16px !important;
    }
    .dev-subcard {
        padding: 12px 14px !important;
        gap: 10px;
    }
    .dev-btn-grid {
        grid-template-columns: 1fr;
    }
    .dev-terminal-topbar {
        padding: 10px 14px !important;
    }
    .dev-terminal-screen {
        padding: 12px 14px !important;
        font-size: 11px;
    }
    .dev-terminal-info-label {
        min-width: 100%;
        margin-bottom: 2px;
    }
}
</style>

<div class="dev-db-container" x-data="databaseManager()" x-init="init()">

    <?php if (!empty($isProductionDomain)): ?>
    <!-- ========================================================================= -->
    <!-- ACTIVE DOMAIN SECURITY LOCK BANNER                                        -->
    <!-- ========================================================================= -->
    <div style="padding: 14px 18px; border-radius: 14px; background: rgba(225, 29, 72, 0.08); border: 1.5px solid rgba(225, 29, 72, 0.35); display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
        <div style="display: flex; align-items: flex-start; gap: 12px; min-width: 240px; flex: 1;">
            <div style="width: 36px; height: 36px; border-radius: 10px; background: rgba(225, 29, 72, 0.15); color: #e11d48; display: flex; align-items: center; justify-content: center; flex-shrink: 0; border: 1px solid rgba(225, 29, 72, 0.3); margin-top: 2px;">
                <i data-lucide="lock" style="width: 18px; height: 18px;"></i>
            </div>
            <div style="min-width: 0; flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 3px;">
                    <span style="font-size: 12.5px; font-weight: 800; color: #9f1239; letter-spacing: -0.01em;">
                        AKSES TERKUNCI DI DOMAIN AKTIF <?= !empty($_SERVER['HTTP_HOST']) ? '(' . htmlspecialchars($_SERVER['HTTP_HOST']) . ')' : '' ?>
                    </span>
                    <span class="badge badge-error" style="font-size: 9px; font-weight: 800; padding: 2px 7px;">READ-ONLY</span>
                </div>
                <p style="font-size: 11.5px; color: #be123c; margin: 0; line-height: 1.5;">
                    Perpindahan koneksi dan replikasi database dikunci permanen pada domain ini. Aksi hanya dapat dieksekusi di server lokal developer (127.0.0.1 / localhost) guna menjamin 100% integritas data dan mencegah risiko crash data.
                </p>
            </div>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <span style="font-size: 11px; font-weight: 700; color: #9f1239; background: rgba(225, 29, 72, 0.1); padding: 5px 10px; border-radius: 8px; border: 1px solid rgba(225, 29, 72, 0.2); display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="shield-alert" style="width: 13px; height: 13px;"></i>
                <span>Strict Security Guard Active</span>
            </span>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- 1. DEVELOPER HERO & TELEMETRY BANNER (DNA KEREN ONE)                      -->
    <!-- ========================================================================= -->
    <div class="dev-db-card">
        <div style="display:flex; flex-direction:column; gap:18px;">
            <div class="dev-hero-layout">
                <!-- Left: Icon + Badges + Title + Description -->
                <div style="display:flex; align-items:flex-start; gap:14px; flex:1; min-width:min(100%, 280px);">
                    <div style="width:44px; height:44px; border-radius:12px; background:rgba(37,99,235,0.08); color:var(--color-primary, #2563eb); display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid rgba(37,99,235,0.18);">
                        <i data-lucide="database" style="width:22px; height:22px;"></i>
                    </div>
                    <div style="min-width:0; flex:1;">
                        <div style="display:flex; align-items:center; gap:6px 8px; flex-wrap:wrap; margin-bottom:8px;">
                            <span class="badge badge-primary" style="font-size:9.5px; font-weight:800; letter-spacing:0.04em; padding:3px 8px;">DATABASE ENGINE &amp; SYNC</span>
                            
                            <!-- Realtime Status Pill -->
                            <span :class="isLocal ? 'dev-hero-badge-local' : 'dev-hero-badge-live'">
                                <span class="w-2 h-2 rounded-full inline-block"
                                      :style="isLocal ? 'background:#10b981; box-shadow:0 0 6px #10b981;' : 'background:#e11d48; box-shadow:0 0 6px #e11d48;'"></span>
                                <span x-text="isLocal ? 'LOCAL DB (SANDBOX)' : 'LIVE SUPABASE (PRODUKSI)'"></span>
                            </span>

                            <span class="badge badge-mono" style="font-size:9.5px; padding:3px 8px;" x-text="'Ping: ' + pingMs + 'ms'"></span>
                            <span class="badge badge-mono" style="font-size:9.5px; padding:3px 8px;" x-text="tableCount + ' Tabel'"></span>
                            <span class="badge badge-mono" style="font-size:9.5px; padding:3px 8px;" x-text="FormatNumber(totalRows) + ' Rows'"></span>
                        </div>
                        
                        <h1 class="dev-card-header-title" style="font-size:18px; font-weight:900; color:var(--color-ink, #0f172a); line-height:1.3; letter-spacing:-0.01em; margin:0 0 6px 0;">
                            Pusat Kendali Replikasi Database &amp; Koneksi Sandbox
                        </h1>
                        
                        <p style="font-size:12.5px; color:var(--color-ink-mute, #64748b); line-height:1.6; margin:0;">
                            Kelola perpindahan koneksi instan antara database cloud Supabase dan database lokal sandbox 
                            <span style="font-family:var(--font-mono); color:var(--color-primary); font-weight:700; padding:2px 8px; border-radius:6px; background:var(--color-canvas-soft, #f1f5f9); border:1px solid var(--color-hairline, #e2e8f0); font-size:11px; display:inline-block; margin:0 2px;">kerensnack_erp_local</span>, serta lakukan kloning 100% data aktif kapan saja.
                        </p>
                    </div>
                </div>

                <!-- Right: Host & Database Info Box -->
                <div class="dev-hero-info-box">
                    <span style="font-size:9px; color:var(--color-ink-mute, #64748b); font-weight:800; letter-spacing:0.05em; text-transform:uppercase; margin-bottom:5px; display:block;">HOST AKTIF SAAT INI</span>
                    <div style="font-family:var(--font-mono); font-size:11.5px; font-weight:800; color:var(--color-ink, #0f172a); margin-bottom:6px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="dbHost + ':' + dbPort"></div>
                    <div style="display:flex; align-items:center; justify-content:space-between; font-size:11px; padding-top:6px; border-top:1px solid var(--color-hairline, #e2e8f0); color:var(--color-ink-mute, #64748b);">
                        <span>Database:</span>
                        <span style="font-family:var(--font-mono); font-weight:700; color:var(--color-primary, #2563eb); padding-left:6px; word-break:break-all;" x-text="dbName"></span>
                    </div>
                </div>
            </div>

            <!-- Bottom Action Bar: Separator & Security Guard -->
            <div style="display:flex; flex-wrap:wrap; align-items:center; justify-content:space-between; gap:12px; padding-top:14px; border-top:1px solid var(--color-hairline, #e2e8f0);">
                <div style="display:flex; align-items:center; gap:8px; font-size:11.5px; color:var(--color-ink-mute, #64748b);">
                    <i data-lucide="shield-check" style="width:15px; height:15px; color:var(--color-primary, #2563eb); flex-shrink:0;"></i>
                    <span>Proteksi Eksekutif: Akses terbatas hanya untuk pengembang berotoritas (<span style="font-family:var(--font-mono); font-weight:700; color:var(--color-ink); padding:2px 5px; border-radius:4px; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); font-size:10.5px;">developer</span>).</span>
                </div>
                <div>
                    <a href="<?= Router::url('/developer') ?>" class="btn btn-secondary" style="font-size:11.5px; font-weight:700; padding:7px 14px; border-radius:8px; display:inline-flex; align-items:center; gap:6px;">
                        <i data-lucide="arrow-left" style="width:13px; height:13px;"></i>
                        <span>Kembali ke Developer Hub</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. DUA KARTU KONTROL UTAMA: SWITCHER & 1-CLICK REPLIKASI                  -->
    <!-- ========================================================================= -->
    <div class="dev-cards-grid">

        <!-- KARTU 1: 1-CLICK CONNECTION SWITCHER -->
        <div class="dev-db-card" style="display:flex; flex-direction:column; justify-content:space-between; overflow:hidden;">
            <!-- Top Gradient Accent Bar -->
            <div style="position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg, #10b981, #34d399);"></div>
            
            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; gap:8px; flex-wrap:wrap;">
                    <div class="dev-card-icon" :class="isLocal ? 'dev-card-icon-local' : 'dev-card-icon-live'">
                        <i data-lucide="git-branch" style="width:20px; height:20px;"></i>
                    </div>
                    <span class="badge font-bold"
                          :class="isLocal ? 'badge-success' : 'badge-error'"
                          style="font-size:9.5px; font-weight:800; padding:4px 8px;">
                        KONEKSI: <span x-text="isLocal ? 'LOCAL (127.0.0.1)' : 'LIVE SUPABASE'"></span>
                    </span>
                </div>

                <h3 style="font-size:15px; font-weight:800; color:var(--color-ink, #0f172a); margin:0 0 6px 0;">
                    1. Saklar Koneksi Database (1-Click Switcher)
                </h3>
                <p style="font-size:12px; color:var(--color-ink-mute, #64748b); line-height:1.5; margin:0 0 16px 0;">
                    Beralih seketika antara Database Sandbox Lokal (<strong style="color:#059669; font-weight:700;">Aman untuk Uji Coba</strong>) dan Database Cloud Supabase (<strong style="color:#e11d48; font-weight:700;">Produksi Aktif</strong>).
                </p>

                <!-- Status Sub-Cards (Local vs Live) with Smooth 14px Curves -->
                <div style="display:flex; flex-direction:column; gap:10px; margin-bottom:16px;">
                    <!-- Local Card -->
                    <div class="dev-subcard" :class="isLocal ? 'dev-subcard-local-active' : 'dev-subcard-local-inactive'">
                        <div class="dev-subcard-icon dev-subcard-icon-emerald">
                            <i data-lucide="check-circle-2" style="width:16px; height:16px;"></i>
                        </div>
                        <div style="flex:1; min-width:0; font-size:11.5px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; flex-wrap:wrap; margin-bottom:3px;">
                                <strong style="color:var(--color-ink); font-weight:700; font-size:12px;">Mode Lokal (Sandbox):</strong>
                                <span class="badge badge-success font-mono" style="font-size:8.5px; padding:2px 5px;" x-show="isLocal">AKTIF SEKARANG</span>
                            </div>
                            <p style="color:var(--color-ink-mute); font-size:11px; line-height:1.5; margin:0;">
                                Seluruh pesanan baru, kasir POS, opname gudang, dan mutasi arus kas tersimpan di Laragon lokal. <strong>0% menyentuh cloud</strong>.
                            </p>
                        </div>
                    </div>

                    <!-- Live Card -->
                    <div class="dev-subcard" :class="!isLocal ? 'dev-subcard-live-active' : 'dev-subcard-live-inactive'">
                        <div class="dev-subcard-icon dev-subcard-icon-rose">
                            <i data-lucide="alert-triangle" style="width:16px; height:16px;"></i>
                        </div>
                        <div style="flex:1; min-width:0; font-size:11.5px;">
                            <div style="display:flex; align-items:center; justify-content:space-between; gap:6px; flex-wrap:wrap; margin-bottom:3px;">
                                <strong style="color:var(--color-ink); font-weight:700; font-size:12px;">Mode Live Supabase (Produksi):</strong>
                                <span class="badge badge-error font-mono" style="font-size:8.5px; padding:2px 5px;" x-show="!isLocal">AKTIF SEKARANG</span>
                            </div>
                            <p style="color:var(--color-ink-mute); font-size:11px; line-height:1.5; margin:0;">
                                Terhubung langsung ke database Supabase Cloud. Seluruh transaksi berdampak langsung ke bisnis riil.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Switcher Buttons Grid with Generous Spacing -->
            <div style="padding-top:14px; border-top:1px solid var(--color-hairline, #e2e8f0); margin-top:6px;">
                <?php if (!empty($isProductionDomain)): ?>
                <!-- Production Locked Button State -->
                <div class="dev-btn-grid">
                    <button type="button" 
                            disabled
                            class="dev-switch-btn dev-switch-btn-local-disabled"
                            style="opacity:0.6; cursor:not-allowed; pointer-events:none;">
                        <i data-lucide="lock" style="width:14px; height:14px; flex-shrink:0;"></i>
                        <span>Lokal (Terkunci)</span>
                    </button>

                    <button type="button" 
                            disabled
                            class="dev-switch-btn dev-switch-btn-live-disabled"
                            style="opacity:0.6; cursor:not-allowed; pointer-events:none;">
                        <i data-lucide="lock" style="width:14px; height:14px; flex-shrink:0;"></i>
                        <span>Live (Terkunci)</span>
                    </button>
                </div>
                <?php else: ?>
                <!-- Normal Switcher Buttons -->
                <div class="dev-btn-grid">
                    <button type="button" 
                            @click="switchDatabase('local')" 
                            :disabled="isLocal || isSwitching"
                            class="dev-switch-btn"
                            :class="isLocal ? 'dev-switch-btn-local-disabled' : 'dev-switch-btn-local-active'">
                        <i data-lucide="laptop" style="width:14px; height:14px; flex-shrink:0;"></i>
                        <span x-text="isSwitching && targetSwitch === 'local' ? 'Memproses...' : 'Beralih ke Lokal'"></span>
                    </button>

                    <button type="button" 
                            @click="confirmSwitchLive()" 
                            :disabled="!isLocal || isSwitching"
                            class="dev-switch-btn"
                            :class="!isLocal ? 'dev-switch-btn-live-disabled' : 'dev-switch-btn-live-active'">
                        <i data-lucide="cloud" style="width:14px; height:14px; flex-shrink:0;"></i>
                        <span x-text="isSwitching && targetSwitch === 'live' ? 'Memproses...' : 'Beralih ke Live'"></span>
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KARTU 2: 1-CLICK DATABASE REPLICATION ENGINE -->
        <div class="dev-db-card" style="display:flex; flex-direction:column; justify-content:space-between; overflow:hidden;">
            <!-- Top Gradient Accent Bar -->
            <div style="position:absolute; top:0; left:0; right:0; height:4px; background:linear-gradient(90deg, #3b82f6, #60a5fa);"></div>

            <div>
                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; gap:8px; flex-wrap:wrap;">
                    <div class="dev-card-icon dev-card-icon-blue">
                        <i data-lucide="refresh-cw" style="width:20px; height:20px;" :class="isSyncing ? 'animate-spin' : ''"></i>
                    </div>
                    <span class="badge badge-primary" style="font-size:9.5px; font-weight:800; padding:4px 8px;">REPLIKASI 100% IDENTIK</span>
                </div>

                <h3 style="font-size:15px; font-weight:800; color:var(--color-ink, #0f172a); margin:0 0 6px 0;">
                    2. Replikasi Database (Live Cloud &rarr; Lokal)
                </h3>
                <p style="font-size:12px; color:var(--color-ink-mute, #64748b); line-height:1.5; margin:0 0 16px 0;">
                    Kloning seluruh skema DDL, fungsi Stored Procedures, Triggers, Views, Sequences, dan 50 tabel data aktif Supabase ke database lokal secara atomik.
                </p>

                <!-- Spec Table Box with 14px Curves -->
                <div style="padding:14px 16px; border-radius:14px; border:1px solid var(--color-hairline, #e2e8f0); background:var(--color-canvas-soft, #f1f5f9); margin-bottom:16px; font-size:11.5px; display:flex; flex-direction:column; gap:9px;">
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                        <span style="color:var(--color-ink-mute, #64748b);">Pembersihan Tabel Sandbox:</span>
                        <span style="color:var(--color-success, #10b981); font-weight:700; font-family:var(--font-mono);">100% Zero Pollution</span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                        <span style="color:var(--color-ink-mute, #64748b);">Replikasi Stored Procedures:</span>
                        <span style="color:var(--color-ink, #0f172a); font-weight:700; font-family:var(--font-mono);">30 Prosedur / RPC</span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px;">
                        <span style="color:var(--color-ink-mute, #64748b);">Penyelarasan Sequences &amp; FK:</span>
                        <span style="color:var(--color-ink, #0f172a); font-weight:700; font-family:var(--font-mono);">Auto-Sync Otomatis</span>
                    </div>
                    <div style="display:flex; align-items:center; justify-content:space-between; gap:8px; padding-top:8px; border-top:1px solid var(--color-hairline, #e2e8f0);">
                        <span style="color:var(--color-ink-mute, #64748b);">Tabel Terisi Saat Ini:</span>
                        <span class="badge badge-mono" style="font-size:9.5px; padding:2px 7px;" x-text="tableCount + ' Tabel (' + FormatNumber(totalRows) + ' Rows)'"></span>
                    </div>
                </div>
            </div>

            <!-- Replicate Button (Clean Play Icon only / Lock when Production) -->
            <div style="padding-top:14px; border-top:1px solid var(--color-hairline, #e2e8f0); margin-top:6px;">
                <?php if (!empty($isProductionDomain)): ?>
                <button type="button" 
                        disabled
                        class="btn btn-secondary"
                        style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px; padding:11px 16px; font-size:12.5px; font-weight:800; border-radius:12px; opacity:0.6; cursor:not-allowed; pointer-events:none; background:var(--color-canvas-soft); color:var(--color-ink-mute); border:1px solid var(--color-hairline);">
                    <i data-lucide="lock" style="width:15px; height:15px;"></i>
                    <span>Replikasi Terkunci di Domain Produksi</span>
                </button>
                <?php else: ?>
                <button type="button" 
                        @click="triggerSync()" 
                        :disabled="isSyncing"
                        class="btn btn-primary"
                        style="width:100%; display:flex; align-items:center; justify-content:center; gap:8px; padding:11px 16px; font-size:12.5px; font-weight:800; border-radius:12px; transition:all 0.2s ease;">
                    <i data-lucide="play" style="width:15px; height:15px; fill:currentColor;" x-show="!isSyncing"></i>
                    <span class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin inline-block" x-show="isSyncing"></span>
                    <span x-text="isSyncing ? 'Sedang Mereplikasi Database Live ke Lokal...' : 'Mulai Replikasi Live ke Lokal'"></span>
                </button>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 3. REAL-TIME REPLICATION LOG CONSOLE (CLEAN & SPACIOUS DEV TERMINAL)      -->
    <!-- ========================================================================= -->
    <div class="dev-db-card">
        
        <!-- Header Card: Clean Title, Realtime Status Badge & Action Button -->
        <div class="dev-db-card-header">
            <!-- Left: Icon + Title + Subtitle -->
            <div style="display:flex; align-items:center; gap:14px;">
                <div style="width:40px; height:40px; border-radius:10px; background:rgba(59,130,246,0.08); color:var(--color-primary, #2563eb); display:flex; align-items:center; justify-content:center; flex-shrink:0; border:1px solid rgba(59,130,246,0.2);">
                    <i data-lucide="terminal" style="width:20px; height:20px;"></i>
                </div>
                <div>
                    <div style="display:flex; align-items:center; gap:8px;">
                        <h3 style="font-size:15px; font-weight:800; color:var(--color-ink, #0f172a); margin:0;">
                            Live Stream Log Replikasi
                        </h3>
                        <span class="badge badge-mono" style="font-size:10px; padding:2px 6px;">sync_replication.log</span>
                    </div>
                    <p style="font-size:12px; color:var(--color-ink-mute, #64748b); margin:3px 0 0 0;">
                        Output proses sinkronisasi DDL, fungsi RPC, views, triggers, dan replikasi 50 tabel database secara realtime.
                    </p>
                </div>
            </div>

            <!-- Right: Status Badge & Clear Action Button -->
            <div style="display:flex; align-items:center; gap:12px;">
                <!-- Status Badge -->
                <span class="badge" 
                      style="font-size:11px; font-weight:700; padding:5px 12px; border-radius:8px; display:inline-flex; align-items:center; gap:6px;"
                      :style="isSyncing ? 'background:rgba(56,189,248,0.12); color:#0284c7; border:1px solid rgba(56,189,248,0.3);' : 
                             (syncDuration ? 'background:rgba(16,185,129,0.12); color:#059669; border:1px solid rgba(16,185,129,0.3);' : 
                             'background:var(--color-canvas-soft); color:var(--color-ink-mute); border:1px solid var(--color-hairline);')">
                    <span class="w-2 h-2 rounded-full inline-block"
                          :class="isSyncing ? 'animate-ping' : ''"
                          :style="isSyncing ? 'background:#0284c7;' : (syncDuration ? 'background:#10b981;' : 'background:#94a3b8;')"></span>
                    <span x-text="isSyncing ? 'Sedang Replikasi...' : (syncDuration ? 'Selesai (' + syncDuration + 's)' : 'Idle / Standby')"></span>
                </span>

                <!-- Clear Button -->
                <button type="button" 
                        @click="logs = []" 
                        class="btn btn-secondary" 
                        style="font-size:11.5px; font-weight:600; padding:6px 14px; border-radius:8px; display:inline-flex; align-items:center; gap:6px;"
                        title="Bersihkan teks log di layar">
                    <i data-lucide="trash-2" style="width:13px; height:13px;"></i>
                    <span>Bersihkan</span>
                </button>
            </div>
        </div>

        <!-- Terminal Output Window (Modern Obsidian Developer Terminal) -->
        <div class="dev-terminal-wrapper">
            
            <!-- Window Top Bar with Mac Dots & Clean Path Prompt -->
            <div class="dev-terminal-topbar">
                <!-- Left: Traffic Light Window Dots + Command Prompt Label -->
                <div style="display:flex; align-items:center; gap:8px; min-width:0; flex:1;">
                    <div class="dev-terminal-dots">
                        <span class="dev-terminal-dot red"></span>
                        <span class="dev-terminal-dot yellow"></span>
                        <span class="dev-terminal-dot green"></span>
                    </div>
                    
                    <span class="dev-terminal-divider"></span>
                    
                    <div class="dev-terminal-path">
                        <span style="color:#38bdf8; font-weight:700;">sync_db.php</span>
                        <span class="hide-mobile" style="color:#64748b; margin:0 2px;">—</span>
                        <span class="hide-mobile" style="color:#c084fc; font-weight:500;">~/kerensnack-erp</span>
                    </div>
                </div>

                <!-- Right: Stream State Chip -->
                <div style="flex-shrink:0;">
                    <span style="padding:3px 9px; border-radius:6px; font-size:10px; font-weight:700; font-family:var(--font-mono); background:rgba(56,189,248,0.12); color:#38bdf8; border:1px solid rgba(56,189,248,0.25);" 
                          x-text="logs.length > 0 ? logs.length + ' baris' : 'STANDBY'"></span>
                </div>
            </div>

            <!-- Log Text Body with Realistic & Spacious Prompt Terminal -->
            <div class="dev-terminal-screen" id="terminal-screen">
                
                <!-- State 1: Standby Prompt Mockup (Lively & Generous Spacing) -->
                <template x-if="logs.length === 0">
                    <div style="display:flex; flex-direction:column; user-select:none; padding-bottom:10px;">
                        <!-- Top Command -->
                        <div class="dev-terminal-cmd-line">
                            <span style="color:#10b981; font-weight:700; font-size:12.5px;">developer@keren-one:~$</span>
                            <span style="color:#f1f5f9; font-weight:600; font-size:12.5px;">php bin/sync_db.php --status</span>
                        </div>
                        
                        <!-- Structured Info Box -->
                        <div class="dev-terminal-info-box">
                            <div class="dev-terminal-info-item">
                                <span class="dev-terminal-info-label" style="color:#38bdf8;">[TARGET SANDBOX]</span>
                                <span class="dev-terminal-info-value">PostgreSQL 14 (127.0.0.1:5432 / kerensnack_erp_local)</span>
                            </div>
                            <div class="dev-terminal-info-item">
                                <span class="dev-terminal-info-label" style="color:#10b981;">[STATUS ENGINE]</span>
                                <span class="dev-terminal-info-value" style="color:#cbd5e1;">Standby &amp; Terhubung. Replikasi siap dieksekusi kapan saja.</span>
                            </div>
                            <div style="color:#64748b; font-style:italic; padding-top:8px; margin-top:4px; border-top:1px dashed rgba(255,255,255,0.08); font-size:11px; line-height:1.5;">
                                &rarr; Tekan tombol <span style="color:#60a5fa; font-weight:700; font-style:normal;">"Mulai Replikasi Live ke Lokal"</span> di atas untuk memulai streaming proses kloning secara realtime.
                            </div>
                        </div>

                        <!-- Active Prompt with Blinking Cursor -->
                        <div style="display:flex; align-items:center; gap:8px; margin-top:10px; color:#10b981;">
                            <span style="font-weight:700; font-size:12.5px;">developer@keren-one:~$</span>
                            <span class="dev-terminal-cursor"></span>
                        </div>
                    </div>
                </template>
                
                <!-- State 2: Active Stream Output Logs -->
                <template x-for="(line, idx) in logs" :key="idx">
                    <div class="dev-log-row" 
                         :style="line.includes('✅') || line.includes('BERHASIL') ? 'color:#34d399; font-weight:700;' : 
                                (line.includes('❌') || line.includes('GAGAL') || line.includes('ERROR') ? 'color:#f87171; font-weight:700;' : 
                                (line.includes('🔄') || line.includes('📦') || line.includes('🏗️') ? 'color:#60a5fa; font-weight:600;' : 
                                (line.includes('⚠️') || line.includes('->') ? 'color:#fbbf24;' : 'color:#cbd5e1;')))">
                        <span class="dev-log-lineno" x-text="String(idx + 1).padStart(2, '0')"></span>
                        <span style="flex:1; word-break:break-word;" x-text="line"></span>
                    </div>
                </template>
            </div>
        </div>

    </div>

</div>

<script>
function databaseManager() {
    return {
        csrfToken: '<?= $csrfToken ?>',
        isProductionDomain: <?= json_encode(!empty($isProductionDomain)) ?>,
        isLocal: <?= json_encode($dbStatus['is_local'] ?? true) ?>,
        dbHost: '<?= addslashes($dbStatus['host'] ?? '127.0.0.1') ?>',
        dbPort: '<?= addslashes((string)($dbStatus['port'] ?? '5432')) ?>',
        dbName: '<?= addslashes($dbStatus['database'] ?? 'kerensnack_erp_local') ?>',
        pingMs: <?= (float)($dbStatus['ping_ms'] ?? 0) ?>,
        tableCount: <?= (int)($dbStatus['table_count'] ?? 50) ?>,
        totalRows: <?= (int)($dbStatus['total_rows'] ?? 5827) ?>,
        isSwitching: false,
        targetSwitch: '',
        isSyncing: false,
        syncDuration: null,
        logs: [],

        init() {
            if (window.lucide) {
                window.lucide.createIcons();
            }
        },

        FormatNumber(num) {
            return new Intl.NumberFormat('id-ID').format(num || 0);
        },

        async switchDatabase(target) {
            if (this.isProductionDomain) {
                if (window.AppAlert) {
                    await window.AppAlert({
                        title: 'Aksi Dikunci',
                        message: 'Aksi perpindahan database tidak diizinkan pada domain produksi aplikasi.kerensnack.id.',
                        type: 'danger',
                        buttonText: 'Tutup'
                    });
                }
                return;
            }

            if (this.isSwitching) return;
            this.isSwitching = true;
            this.targetSwitch = target;

            try {
                const formData = new FormData();
                formData.append('csrf_token', this.csrfToken);
                formData.append('target', target);

                const res = await fetch('<?= Router::url('/developer/database/switch') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (!data.success) {
                    throw new Error(data.error || 'Gagal mengubah target database.');
                }

                this.isLocal = (target === 'local');
                
                if (window.AppAlert) {
                    await window.AppAlert({
                        title: 'Koneksi Berhasil Dialihkan!',
                        message: data.message || 'Koneksi database telah berhasil diubah.',
                        type: 'success',
                        buttonText: 'Lanjutkan'
                    });
                } else {
                    alert(data.message);
                }

                window.location.reload();
            } catch (err) {
                if (window.AppAlert) {
                    await window.AppAlert({
                        title: 'Peralihan Gagal',
                        message: err.message || 'Terjadi kesalahan saat mengubah database.',
                        type: 'danger',
                        buttonText: 'Tutup'
                    });
                } else {
                    alert('Error: ' + err.message);
                }
            } finally {
                this.isSwitching = false;
                this.targetSwitch = '';
            }
        },

        async confirmSwitchLive() {
            if (this.isProductionDomain) return;

            let confirmed = false;
            if (window.AppConfirm) {
                confirmed = await window.AppConfirm({
                    title: 'Beralih ke Live Supabase?',
                    message: 'Anda akan terhubung langsung ke database produksi Cloud Supabase. Segala transaksi pesanan, mutasi kas, dan perubahan master data akan berdampak langsung ke bisnis riil.',
                    submessage: 'Gunakan mode Sandbox (Lokal) jika hanya ingin melakukan uji coba atau simulasi fitur baru.',
                    type: 'danger',
                    confirmText: 'Ya, Beralih ke Live',
                    cancelText: 'Batal',
                    icon: 'alert-triangle'
                });
            } else {
                confirmed = confirm('PERINGATAN: Anda akan beralih ke database produksi Live Supabase! Lanjutkan?');
            }

            if (confirmed) {
                this.switchDatabase('live');
            }
        },

        async triggerSync() {
            if (this.isProductionDomain) {
                if (window.AppAlert) {
                    await window.AppAlert({
                        title: 'Aksi Dikunci',
                        message: 'Replikasi database tidak diizinkan pada domain produksi aplikasi.kerensnack.id.',
                        type: 'danger',
                        buttonText: 'Tutup'
                    });
                }
                return;
            }

            if (this.isSyncing) return;

            let proceed = false;
            if (window.AppConfirm) {
                proceed = await window.AppConfirm({
                    title: 'Mulai Replikasi Database?',
                    message: 'Seluruh skema DDL, 30 Stored Procedures, Triggers, Views, Sequences, dan 50 tabel data Supabase akan dikloning 100% ke database lokal.',
                    submessage: 'Data lokal (kerensnack_erp_local) akan disinkronkan identik dengan kondisi produksi saat ini.',
                    type: 'primary',
                    confirmText: 'Mulai Kloning Data',
                    cancelText: 'Batal',
                    icon: 'refresh-cw'
                });
            } else {
                proceed = confirm('Mulai replikasi data penuh dari Supabase ke lokal?');
            }

            if (!proceed) return;

            this.isSyncing = true;
            this.logs = [
                '🔄 Memulai engine replikasi database (Live Cloud -> Local)...',
                '⏳ Menghubungkan ke Supabase & mempersiapkan DDL...'
            ];

            try {
                const formData = new FormData();
                formData.append('csrf_token', this.csrfToken);

                const res = await fetch('<?= Router::url('/developer/database/sync') ?>', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (!data.success) {
                    throw new Error(data.error || 'Replikasi database gagal.');
                }

                if (data.logs && Array.isArray(data.logs)) {
                    this.logs = data.logs;
                }
                this.syncDuration = data.duration;
                this.tableCount = data.total_tables;
                this.totalRows = data.total_rows;

                if (window.AppAlert) {
                    await window.AppAlert({
                        title: 'Replikasi Selesai 100%!',
                        message: `Berhasil mereplikasi ${data.total_tables} tabel (${new Intl.NumberFormat('id-ID').format(data.total_rows)} baris) dalam ${data.duration} detik.`,
                        type: 'success',
                        buttonText: 'Selesai'
                    });
                }

                this.$nextTick(() => {
                    const screen = document.getElementById('terminal-screen');
                    if (screen) screen.scrollTop = screen.scrollHeight;
                });

            } catch (err) {
                this.logs.push('❌ ERROR: ' + err.message);
                if (window.AppAlert) {
                    await window.AppAlert({
                        title: 'Replikasi Gagal',
                        message: err.message || 'Terjadi kegagalan saat proses replikasi data.',
                        type: 'danger',
                        buttonText: 'Tutup'
                    });
                }
            } finally {
                this.isSyncing = false;
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            }
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

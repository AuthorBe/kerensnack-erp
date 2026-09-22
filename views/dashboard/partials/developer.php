<?php
/**
 * views/dashboard/partials/developer.php
 * Dasbor Terpersonalisasi Developer Engine & System Health Hub
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;

$latencyMs = (float)($roleData['latencyMs'] ?? 0);
$tablesCount = (int)($roleData['tablesCount'] ?? 47);
$usersCount = (int)($roleData['usersCount'] ?? 1);
$rolesCount = (int)($roleData['rolesCount'] ?? 6);
$permissionsCount = (int)($roleData['permissionsCount'] ?? 71);
$testSuitesCount = (int)($roleData['testSuitesCount'] ?? 27);
$activeUsers = $roleData['activeUsers'] ?? [];
$recentLogs = $roleData['recentLogs'] ?? [];
$isDev = (Auth::user()['peran'] ?? '') === 'developer';
?>

<div class="space-y-4 sm:space-y-5">

    <!-- 1. 4 KPI TELEMETRY CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Ping Latency -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #06b6d4;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Ping Latency</span>
                <i data-lucide="database" style="width:14px;height:14px;color:#06b6d4;flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                <?= $latencyMs > 0 ? $latencyMs : '1.5' ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">ms</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate" style="color:var(--color-success);font-weight:700;display:inline-flex;align-items:center;gap:3px;">
                    <span style="width:5px;height:5px;border-radius:50%;background:var(--color-success);display:inline-block;"></span>
                    SSL Pooler
                </span>
                <span class="font-mono text-[9.5px] flex-shrink-0">Supabase</span>
            </div>
        </div>

        <!-- 2. Public Schema Tables -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Skema Tabel</span>
                <i data-lucide="table" style="width:14px;height:14px;color:var(--color-primary);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-primary);line-height:1.2;">
                <?= $tablesCount ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Tabel</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">PostgreSQL 17</span>
                <span class="badge badge-mono text-[9px] px-1.5 py-0 flex-shrink-0">SSOT</span>
            </div>
        </div>

        <!-- 3. Automated Test Suites -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Test Suites</span>
                <i data-lucide="flask-conical" style="width:14px;height:14px;color:var(--color-success);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                <?= $testSuitesCount ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Suites</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Auto-Rollback</span>
                <span class="badge badge-success font-mono text-[8.5px] px-1 py-0 flex-shrink-0">ISOLATED</span>
            </div>
        </div>

        <!-- 4. Real-time Active Users & RBAC -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #a855f7;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Pengguna Aktif</span>
                <i data-lucide="users" style="width:14px;height:14px;color:#a855f7;flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:#a855f7;line-height:1.2;">
                <?= count($activeUsers) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">Online</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate" style="color:var(--color-success);font-weight:700;display:inline-flex;align-items:center;gap:3px;">
                    <span style="width:5px;height:5px;border-radius:50%;background:var(--color-success);display:inline-block;"></span>
                    <?= $usersCount ?> User
                </span>
                <span class="font-mono text-[9.5px] flex-shrink-0"><?= $permissionsCount ?> Izin</span>
            </div>
        </div>

    </div>

    <!-- 1.5 REAL-TIME ACTIVE USERS PRESENCE STRIP -->
    <div class="card p-3.5 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-3.5">
            <div class="flex items-center gap-2.5">
                <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="activity" style="width:16px;height:16px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 style="font-size:12.5px;sm:font-size:13.5px;font-weight:800;color:var(--color-ink);line-height:1.25;margin:0;">Pengguna Aktif Saat <span style="white-space:nowrap;">Ini<span class="badge badge-success font-mono text-[9px] flex-shrink-0 align-middle" style="padding:1px 6px;vertical-align:middle;margin-left:4px;"><span style="width:4px;height:4px;border-radius:50%;background:currentColor;display:inline-block;margin-right:2px;"></span><?= count($activeUsers) ?> ONLINE</span></span></h3>
                </div>
            </div>
            <span style="font-size:10.5px;color:var(--color-ink-mute);">Sesi aktif dalam 5 menit terakhir</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2.5">
            <?php if (empty($activeUsers)): ?>
                <div class="col-span-full py-4 text-center text-xs text-muted" style="background:var(--color-canvas-soft);border-radius:12px;border:1px dashed var(--color-hairline);">
                    Tidak ada aktivitas pengguna lain dalam 5 menit terakhir.
                </div>
            <?php else: ?>
                <?php foreach ($activeUsers as $u): ?>
                <div class="p-2.5 rounded-xl flex flex-col justify-between gap-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);transition:border-color 0.15s ease;">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2 min-w-0">
                            <div style="position:relative;flex-shrink:0;">
                                <div style="width:28px;height:28px;border-radius:50%;background:var(--color-canvas);border:1.5px solid var(--color-hairline);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:11px;color:var(--color-primary);">
                                    <?= strtoupper(substr($u['nama_lengkap'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span style="position:absolute;bottom:-1px;right:-1px;width:8px;height:8px;border-radius:50%;background:var(--color-success);border:1.5px solid var(--color-canvas);"></span>
                            </div>
                            <div class="min-w-0">
                                <strong class="text-xs truncate block" style="color:var(--color-ink);" title="<?= htmlspecialchars($u['nama_lengkap']) ?>"><?= htmlspecialchars($u['nama_lengkap']) ?></strong>
                                <span class="badge badge-mono text-[8.5px] uppercase" style="padding:0px 4px;"><?= htmlspecialchars($u['peran'] ?? 'user') ?></span>
                            </div>
                        </div>
                        <span class="text-[9.5px] font-mono text-muted flex-shrink-0" style="background:var(--color-canvas);padding:1px 5px;border-radius:4px;border:1px solid var(--color-hairline);">
                            <?php
                            $detik = (int)($u['detik_lalu'] ?? 0);
                            if ($detik < 60) echo 'Baru saja';
                            elseif ($detik < 3600) echo floor($detik / 60) . 'm lalu';
                            elseif ($detik < 86400) echo floor($detik / 3600) . 'j lalu';
                            else echo floor($detik / 86400) . 'h lalu';
                            ?>
                        </span>
                    </div>
                    <div class="text-[10.5px] text-muted line-clamp-1" style="line-height:1.3;" title="<?= htmlspecialchars($u['deskripsi_aktivitas'] ?? '') ?>">
                        <span class="font-semibold text-primary"><?= htmlspecialchars($u['jenis_aksi'] ?? 'AKTIF') ?></span>: <?= htmlspecialchars($u['deskripsi_aktivitas'] ?? '') ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. QUICK ACTION LAUNCHPAD -->
    <div class="card p-3.5 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-3 sm:gap-4">
            <div class="flex items-center gap-3 min-w-0">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(37,99,235,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="terminal" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h2 style="font-size:14px;sm:font-size:14.5px;font-weight:800;color:var(--color-ink);line-height:1.3;margin:0;">Pusat Kendali Pengembang &amp; Sistem</h2>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.35;">Jalankan automated test runner, cek koneksi SSL database, dan eksplorasi blueprint skema kanonikal.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <?php if (Auth::can('system.blueprint') || $isDev): ?>
                <a href="<?= Router::url('/developer/tests') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="play-circle" style="width:14px;height:14px;"></i>
                    <span>Run <?= $testSuitesCount ?> Suites</span>
                </a>
                <a href="<?= Router::url('/developer/test-db') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="activity" style="width:14px;height:14px;"></i>
                    <span>Test DB Ping</span>
                </a>
                <a href="<?= Router::url('/developer/architecture') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="network" style="width:14px;height:14px;"></i>
                    <span>Blueprint Skema</span>
                </a>
                <?php endif; ?>
                <?php if (Auth::can('system.activity_log')): ?>
                <a href="<?= Router::url('/settings/activity-logs') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:35px;font-size:12px;white-space:nowrap;">
                    <i data-lucide="scroll-text" style="width:14px;height:14px;"></i>
                    <span>Log Audit</span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 3. RECENT ACTIVITY LOGS PANEL (Gaya Tabel /owner) -->
    <div class="card overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
        <!-- Card Header (Standard 'Lihat semua >' Button) -->
        <div class="px-3 py-2.5 sm:px-4 sm:py-3.5 border-b border-hairline" style="background:var(--color-canvas);">
            <div class="flex items-center justify-between gap-2 sm:gap-3">
                <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                    <div style="width:30px;height:30px;border-radius:8px;background:rgba(59,130,246,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="history" style="width:15px;height:15px;"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 style="font-size:12px;sm:font-size:13px;font-weight:800;color:var(--color-ink);line-height:1.25;margin:0;">Log Aktivitas <span style="white-space:nowrap;">Sistem<span class="inline-flex items-center justify-center font-mono" style="font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:9999px;background:rgba(59,130,246,0.12);color:var(--color-primary);border:1px solid rgba(59,130,246,0.25);line-height:1.2;min-width:18px;vertical-align:middle;margin-left:4px;"><?= count($recentLogs) ?></span></span></h3>
                        <p class="truncate" style="font-size:10.5px;sm:font-size:11px;color:var(--color-ink-mute);margin-top:2px;line-height:1.25;">Aktivitas riil pengguna &amp; mutasi data terkini</p>
                    </div>
                </div>
                <?php if (Auth::can('system.activity_log')): ?>
                <a href="<?= Router::url('/settings/activity-logs') ?>" class="btn btn-secondary btn-sm" style="font-size:10.5px;sm:font-size:11px;font-weight:700;padding:3px 8px;border-radius:7px;display:inline-flex;align-items:center;gap:3px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);box-shadow:0 1px 2px rgba(0,0,0,0.04);white-space:nowrap;flex-shrink:0;height:28px;">
                    <span>Lihat semua</span>
                    <i data-lucide="chevron-right" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (empty($recentLogs)): ?>
        <div class="p-6 sm:p-8 text-center flex-1 flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);padding-top:32px;padding-bottom:32px;">
            <div style="width:44px;height:44px;border-radius:50%;background:rgba(59,130,246,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;flex-shrink:0;">
                <i data-lucide="shield" style="width:22px;height:22px;"></i>
            </div>
            <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0 0 4px 0;">Belum Ada Log Aktivitas</h4>
            <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:290px;margin:0 auto;line-height:1.4;">Setiap aksi mutasi, otentikasi, atau sinkronisasi data akan tercatat otomatis di sini.</p>
        </div>
        <?php else: ?>
        <div class="table-container overflow-x-auto" style="width:100%;-webkit-overflow-scrolling:touch;">
            <table class="table w-full text-left" style="margin-bottom:0;border-collapse:collapse;min-width:680px;">
                <thead>
                    <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                        <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;width:130px;white-space:nowrap;">Waktu</th>
                        <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;width:140px;white-space:nowrap;">Aktor / Peran</th>
                        <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;width:110px;white-space:nowrap;">Kategori</th>
                        <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;width:90px;white-space:nowrap;">Aksi</th>
                        <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;min-width:200px;">Deskripsi Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr class="hover:bg-slate-500/5 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                        <td class="font-mono text-xs" style="color:var(--color-ink-mute);white-space:nowrap;padding:10px 12px;vertical-align:middle;">
                            <?= Format::tanggalWaktu($log['waktu_kejadian'] ?? null) ?>
                        </td>
                        <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                            <strong style="color:var(--color-ink);font-size:12px;display:block;"><?= htmlspecialchars($log['nama_aktor'] ?? 'System') ?></strong>
                            <span style="font-size:10px;color:var(--color-ink-mute);text-transform:capitalize;"><?= htmlspecialchars($log['peran_aktor'] ?? '—') ?></span>
                        </td>
                        <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                            <span class="badge badge-slate font-semibold text-[9px] px-1.5 py-0.5 text-uppercase"><?= htmlspecialchars(str_replace('_', ' ', $log['kategori_aktivitas'] ?? 'SISTEM')) ?></span>
                        </td>
                        <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                            <span class="badge badge-primary font-mono text-[9.5px] px-1.5 py-0.5"><?= htmlspecialchars($log['jenis_aksi'] ?? 'AKSI') ?></span>
                        </td>
                        <td style="padding:10px 12px;vertical-align:middle;">
                            <div style="font-size:11.5px;color:var(--color-ink);line-height:1.35;" title="<?= htmlspecialchars($log['deskripsi_aktivitas'] ?? '') ?>">
                                <?= htmlspecialchars($log['deskripsi_aktivitas'] ?? '—') ?>
                            </div>
                            <?php if (!empty($log['tabel_terdampak'])): ?>
                            <div style="margin-top:2px;font-size:9.5px;color:var(--color-ink-mute);font-family:monospace;">
                                <i data-lucide="database" style="width:9px;height:9px;display:inline-block;vertical-align:middle;margin-right:2px;"></i><?= htmlspecialchars($log['tabel_terdampak']) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

</div>

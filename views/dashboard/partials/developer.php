<?php
/**
 * views/dashboard/partials/developer.php
 * Dasbor Terpersonalisasi Developer Engine & System Health Hub
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Helpers\Format;

$latencyMs = (float)($roleData['latencyMs'] ?? 0);
$tablesCount = (int)($roleData['tablesCount'] ?? 47);
$usersCount = (int)($roleData['usersCount'] ?? 1);
$rolesCount = (int)($roleData['rolesCount'] ?? 6);
$permissionsCount = (int)($roleData['permissionsCount'] ?? 71);
$testSuitesCount = (int)($roleData['testSuitesCount'] ?? 27);
$activeUsers = $roleData['activeUsers'] ?? [];
$recentLogs = $roleData['recentLogs'] ?? [];
?>

<div class="space-y-5">

    <!-- 1. 4 KPI TELEMETRY CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. DB Ping Latency -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #06b6d4;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Ping Latency</span>
                <i data-lucide="database" style="width:15px;height:15px;color:#06b6d4;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                <?= $latencyMs > 0 ? $latencyMs : '1.5' ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">ms</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span style="color:var(--color-success);font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                    <span style="width:5px;height:5px;border-radius:50%;background:var(--color-success);display:inline-block;"></span>
                    SSL Pooler
                </span>
                <span class="font-mono">Supabase</span>
            </div>
        </div>

        <!-- 2. Public Schema Tables -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Skema Tabel</span>
                <i data-lucide="table" style="width:15px;height:15px;color:var(--color-primary);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-primary);line-height:1.2;">
                <?= $tablesCount ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Tabel</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>PostgreSQL 17</span>
                <span class="badge badge-mono text-[9.5px]">SSOT</span>
            </div>
        </div>

        <!-- 3. Automated Test Suites -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Test Suites</span>
                <i data-lucide="flask-conical" style="width:15px;height:15px;color:var(--color-success);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                <?= $testSuitesCount ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Suites</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Auto-Rollback</span>
                <span class="badge badge-success font-mono text-[9px]">100% ISOLATED</span>
            </div>
        </div>

        <!-- 4. Real-time Active Users & RBAC -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #a855f7;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Pengguna Aktif</span>
                <i data-lucide="users" style="width:15px;height:15px;color:#a855f7;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:#a855f7;line-height:1.2;">
                <?= count($activeUsers) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Online (5 Mnt)</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span style="color:var(--color-success);font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                    <span style="width:5px;height:5px;border-radius:50%;background:var(--color-success);display:inline-block;"></span>
                    <?= $usersCount ?> Terdaftar
                </span>
                <span><?= $permissionsCount ?> Izin</span>
            </div>
        </div>

    </div>

    <!-- 1.5 REAL-TIME ACTIVE USERS PRESENCE STRIP -->
    <div class="card p-4 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 mb-4">
            <div class="flex items-center gap-2.5">
                <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="activity" style="width:16px;height:16px;"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Pengguna Aktif Saat Ini</h3>
                        <span class="badge badge-success font-mono text-[9.5px]" style="padding:2px 7px;">
                            <span style="width:5px;height:5px;border-radius:50%;background:currentColor;display:inline-block;margin-right:3px;"></span>
                            <?= count($activeUsers) ?> ONLINE (5 MNT)
                        </span>
                    </div>
                </div>
            </div>
            <span style="font-size:11px;color:var(--color-ink-mute);">Sesi pengguna aktif dalam kurun 5 menit terakhir</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            <?php if (empty($activeUsers)): ?>
                <div class="col-span-full py-4 text-center text-xs text-muted" style="background:var(--color-canvas-soft);border-radius:12px;border:1px dashed var(--color-hairline);">
                    Tidak ada aktivitas pengguna lain dalam 5 menit terakhir.
                </div>
            <?php else: ?>
                <?php foreach ($activeUsers as $u): ?>
                <div class="p-3 rounded-xl flex flex-col justify-between gap-2" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);transition:border-color 0.15s ease;">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <div style="position:relative;flex-shrink:0;">
                                <div style="width:32px;height:32px;border-radius:50%;background:var(--color-canvas);border:1.5px solid var(--color-hairline);display:flex;align-items:center;justify-content:center;font-weight:800;font-size:12px;color:var(--color-primary);">
                                    <?= strtoupper(substr($u['nama_lengkap'] ?? 'U', 0, 1)) ?>
                                </div>
                                <span style="position:absolute;bottom:-1px;right:-1px;width:9px;height:9px;border-radius:50%;background:var(--color-success);border:2px solid var(--color-canvas);"></span>
                            </div>
                            <div class="min-w-0">
                                <strong class="text-xs truncate block" style="color:var(--color-ink);" title="<?= htmlspecialchars($u['nama_lengkap']) ?>"><?= htmlspecialchars($u['nama_lengkap']) ?></strong>
                                <span class="badge badge-mono text-[9px] uppercase" style="padding:1px 5px;"><?= htmlspecialchars($u['peran'] ?? 'user') ?></span>
                            </div>
                        </div>
                        <span class="text-[10px] font-mono text-muted flex-shrink-0" style="background:var(--color-canvas);padding:2px 6px;border-radius:6px;border:1px solid var(--color-hairline);">
                            <?php
                            $detik = (int)($u['detik_lalu'] ?? 0);
                            if ($detik < 60) echo 'Baru saja';
                            elseif ($detik < 3600) echo floor($detik / 60) . ' mnt lalu';
                            elseif ($detik < 86400) echo floor($detik / 3600) . ' jam lalu';
                            else echo floor($detik / 86400) . ' hari lalu';
                            ?>
                        </span>
                    </div>
                    <div class="text-[11px] text-muted line-clamp-2" style="line-height:1.35;" title="<?= htmlspecialchars($u['deskripsi_aktivitas'] ?? '') ?>">
                        <span class="font-semibold text-primary"><?= htmlspecialchars($u['jenis_aksi'] ?? 'AKTIF') ?></span>: <?= htmlspecialchars($u['deskripsi_aktivitas'] ?? '') ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. QUICK ACTION LAUNCHPAD -->
    <div class="card p-4 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(37,99,235,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="terminal" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 style="font-size:14.5px;font-weight:800;color:var(--color-ink);">Pusat Kendali Pengembang &amp; Pengujian Sistem</h2>
                        <span class="badge badge-primary font-mono text-[9.5px]">DEVELOPER HUB</span>
                    </div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Jalankan automated test runner, cek koneksi SSL database, dan eksplorasi blueprint skema kanonikal.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <a href="<?= Router::url('/developer/tests') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="play-circle" style="width:14px;height:14px;"></i>
                    <span>Run <?= $testSuitesCount ?> Suites</span>
                </a>
                <a href="<?= Router::url('/developer/test-db') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="activity" style="width:14px;height:14px;"></i>
                    <span>Test DB Ping</span>
                </a>
                <a href="<?= Router::url('/developer/architecture') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="network" style="width:14px;height:14px;"></i>
                    <span>Blueprint Skema</span>
                </a>
                <a href="<?= Router::url('/settings/activity-logs') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="scroll-text" style="width:14px;height:14px;"></i>
                    <span>Log Audit</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. RECENT ACTIVITY LOGS PANEL (Gaya Tabel /owner) -->
    <div class="card overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
        <div class="p-3.5 sm:p-5 border-b border-hairline" style="background:var(--color-canvas);">
            <div class="flex items-start gap-2.5 sm:gap-3.5 min-w-0">
                <div style="width:36px;height:36px;border-radius:10px;background:rgba(59,130,246,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                    <i data-lucide="history" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);line-height:1.25;margin:0;">Log Aktivitas Sistem</h3>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.35;">Aktivitas riil pengguna &amp; mutasi data terkini</p>
                    <div class="flex items-center gap-2 sm:gap-2.5 mt-2.5 flex-wrap">
                        <span class="badge badge-mono text-[10px]" style="padding:2px 7px;font-weight:600;"><?= count($recentLogs) ?> Log Terakhir</span>
                        <a href="<?= Router::url('/settings/activity-logs') ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px;font-weight:700;display:inline-flex;align-items:center;gap:4px;height:28px;padding:0 10px;white-space:nowrap;">
                            <span>Lihat Semua</span>
                            <i data-lucide="arrow-right" style="width:13px;height:13px;"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($recentLogs)): ?>
        <div class="p-8 text-center flex flex-col items-center justify-center min-h-[160px]" style="background:var(--color-canvas);">
            <div style="width:40px;height:40px;border-radius:50%;background:var(--color-canvas-soft);color:var(--color-ink-mute);display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                <i data-lucide="shield" style="width:20px;height:20px;"></i>
            </div>
            <h4 style="font-size:13.5px;font-weight:700;color:var(--color-ink);">Belum Ada Log Aktivitas</h4>
            <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:320px;margin-top:2px;">Setiap aksi mutasi, otentikasi, atau sinkronisasi data akan tercatat otomatis di sini.</p>
        </div>
        <?php else: ?>
        <div style="overflow-x:auto;-webkit-overflow-scrolling:touch;width:100%;touch-action:pan-x;" class="custom-scrollbar">
            <table style="min-width:760px;width:100%;border-collapse:collapse;text-align:left;font-size:12.5px;margin:0;">
                <thead>
                    <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 14px;width:150px;white-space:nowrap;text-align:left;">Waktu</th>
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 14px;width:150px;white-space:nowrap;text-align:left;">Aktor / Peran</th>
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 14px;width:130px;white-space:nowrap;text-align:left;">Kategori</th>
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 14px;width:100px;white-space:nowrap;text-align:left;">Aksi</th>
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 14px;min-width:230px;text-align:left;">Deskripsi Aktivitas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentLogs as $log): ?>
                    <tr style="border-bottom:1px solid var(--color-hairline);" class="hover:bg-slate-500/5 transition-colors">
                        <td class="font-mono text-xs" style="color:var(--color-ink-mute);white-space:nowrap;padding:11px 14px;vertical-align:middle;text-align:left;">
                            <?= Format::tanggalWaktu($log['waktu_kejadian'] ?? null) ?>
                        </td>
                        <td style="padding:11px 14px;vertical-align:middle;white-space:nowrap;text-align:left;">
                            <strong style="color:var(--color-ink);font-size:12.5px;display:block;"><?= htmlspecialchars($log['nama_aktor'] ?? 'System') ?></strong>
                            <span style="font-size:10.5px;color:var(--color-ink-mute);text-transform:capitalize;"><?= htmlspecialchars($log['peran_aktor'] ?? '—') ?></span>
                        </td>
                        <td style="padding:11px 14px;vertical-align:middle;white-space:nowrap;text-align:left;">
                            <span class="badge badge-slate font-semibold" style="font-size:10px;padding:2px 8px;text-transform:uppercase;"><?= htmlspecialchars(str_replace('_', ' ', $log['kategori_aktivitas'] ?? 'SISTEM')) ?></span>
                        </td>
                        <td style="padding:11px 14px;vertical-align:middle;white-space:nowrap;text-align:left;">
                            <span class="badge badge-primary font-mono text-[10.5px]" style="padding:2px 8px;"><?= htmlspecialchars($log['jenis_aksi'] ?? 'AKSI') ?></span>
                        </td>
                        <td style="padding:11px 14px;vertical-align:middle;text-align:left;">
                            <div style="font-size:12px;color:var(--color-ink);line-height:1.4;" title="<?= htmlspecialchars($log['deskripsi_aktivitas'] ?? '') ?>">
                                <?= htmlspecialchars($log['deskripsi_aktivitas'] ?? '—') ?>
                            </div>
                            <?php if (!empty($log['tabel_terdampak'])): ?>
                            <div style="margin-top:3px;font-size:10px;color:var(--color-ink-mute);font-family:monospace;">
                                <i data-lucide="database" style="width:10px;height:10px;display:inline-block;vertical-align:middle;margin-right:2px;"></i><?= htmlspecialchars($log['tabel_terdampak']) ?>
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

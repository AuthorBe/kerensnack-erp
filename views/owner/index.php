<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div class="space-y-4">

    <!-- KPI STAT CARDS -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Omzet Hari Ini -->
        <div class="stat-card" style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-card-label">Omzet Penjualan Hari Ini</div>
                <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                    <i data-lucide="trending-up"></i>
                </div>
            </div>
            <div class="stat-card-value"><?= Format::rupiah($omzetToday) ?></div>
            <div class="stat-card-footer">
                <span class="live-dot"></span>
                <span>POS Kasir Realtime</span>
            </div>
        </div>

        <!-- Saldo Kas -->
        <div class="stat-card" style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-card-label">Total Kas &amp; Rekening</div>
                <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                    <i data-lucide="wallet"></i>
                </div>
            </div>
            <div class="stat-card-value" style="color:#3b82f6;"><?= Format::rupiah($totalSaldoKas) ?></div>
            <div class="stat-card-footer">
                <i data-lucide="building-2" style="width:12px;height:12px;"></i>
                <span>Kasir Toko &amp; Bank Operasional</span>
            </div>
        </div>

        <!-- Piutang -->
        <div class="stat-card" style="display:flex;flex-direction:column;gap:12px;">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <div class="stat-card-label">Piutang Toko Berjalan</div>
                <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                    <i data-lucide="clock"></i>
                </div>
            </div>
            <div class="stat-card-value" style="color:#f59e0b;"><?= Format::rupiah($totalPiutang) ?></div>
            <div class="stat-card-footer">
                <i data-lucide="route" style="width:12px;height:12px;"></i>
                <span>Rute Sales-Driver Canvaser</span>
            </div>
        </div>

    </div>

    <!-- TWO-PANE -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- APPROVAL HUB -->
        <div class="card" style="display:flex;flex-direction:column;gap:16px;">

            <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:14px;border-bottom:1px solid var(--color-hairline);">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;width:32px;height:32px;">
                        <i data-lucide="shield-check" style="width:15px;height:15px;"></i>
                    </div>
                    <div>
                        <div class="section-title">Approval Hub</div>
                        <div class="section-subtitle">Bensin, Uang Jalan, &amp; Kasbon</div>
                    </div>
                </div>
                <span class="badge badge-warning font-mono"><?= count($pendingDrafts) ?> Menunggu</span>
            </div>

            <div style="display:flex;flex-direction:column;gap:10px;max-height:420px;overflow-y:auto;" class="no-scrollbar">
                <?php if (empty($pendingDrafts)): ?>
                <div style="text-align:center;padding:40px 16px;font-size:12px;color:var(--color-ink-mute-2);border:1px dashed var(--color-hairline);border-radius:var(--rounded-md);">
                    Tidak ada pengajuan pengeluaran saat ini.
                </div>
                <?php else: ?>
                <?php foreach ($pendingDrafts as $draft): ?>
                <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:10px;">
                        <div>
                            <span class="badge badge-mono" style="margin-bottom:6px;display:inline-block;"><?= htmlspecialchars($draft['kategori_beban']) ?></span>
                            <div style="font-size:12px;font-weight:600;color:var(--color-ink);"><?= htmlspecialchars($draft['pemohon'] ?? 'Driver') ?></div>
                        </div>
                        <strong style="font-family:var(--font-mono);font-size:13px;color:var(--color-primary);"><?= Format::rupiah($draft['nominal']) ?></strong>
                    </div>

                    <p style="font-size:12px;color:var(--color-ink-mute);font-style:italic;margin-bottom:10px;padding-bottom:10px;border-bottom:1px solid var(--color-hairline-cool);">
                        "<?= htmlspecialchars($draft['keterangan_mentah']) ?>"
                    </p>

                    <div style="display:flex;gap:8px;">
                        <form action="<?= Router::url('/owner/reject-draft') ?>" method="POST" style="flex:1;">
                            <input type="hidden" name="draft_id" value="<?= $draft['id'] ?>">
                            <button type="submit" class="btn btn-secondary btn-sm btn-full" style="justify-content:center;border-color:var(--color-danger-soft);color:var(--color-danger);">Tolak</button>
                        </form>
                        <form action="<?= Router::url('/owner/approve-draft') ?>" method="POST" style="flex:1;">
                            <input type="hidden" name="draft_id" value="<?= $draft['id'] ?>">
                            <button type="submit" class="btn btn-primary btn-sm btn-full" style="justify-content:center;">Setujui</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- LIVE ACTIVITY STREAM -->
        <div class="lg:col-span-2 card" style="display:flex;flex-direction:column;gap:16px;">

            <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:14px;border-bottom:1px solid var(--color-hairline);">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;width:32px;height:32px;">
                        <i data-lucide="activity" style="width:15px;height:15px;"></i>
                    </div>
                    <div>
                        <div class="section-title">Live Activity Stream</div>
                        <div class="section-subtitle">Master Forensik Audit Trail dari Seluruh Channel</div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:6px;font-size:11px;font-family:var(--font-mono);font-weight:700;color:var(--color-primary);">
                    <span class="live-dot"></span>
                    Live
                </div>
            </div>

            <div style="display:flex;flex-direction:column;gap:4px;max-height:26rem;overflow-y:auto;" class="no-scrollbar">
                <?php if (empty($activityLogs)): ?>
                <div style="text-align:center;padding:48px 16px;font-size:12px;color:var(--color-ink-mute-2);">
                    Belum ada aktivitas audit trail tercatat.
                </div>
                <?php else: ?>
                <?php foreach ($activityLogs as $log): ?>
                <div class="activity-item">
                    <div class="activity-icon">
                        <?php if ($log['sumber_aksi'] === 'telegram_bot'): ?>
                        <i data-lucide="bot" style="color:#3b82f6;"></i>
                        <?php elseif ($log['sumber_aksi'] === 'database_trigger'): ?>
                        <i data-lucide="zap" style="color:#f59e0b;"></i>
                        <?php else: ?>
                        <i data-lucide="monitor" style="color:var(--color-primary);"></i>
                        <?php endif; ?>
                    </div>

                    <div style="flex:1;min-width:0;">
                        <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;">
                            <div style="display:flex;align-items:center;gap:6px;min-width:0;">
                                <span class="activity-name"><?= htmlspecialchars($log['nama_aktor']) ?></span>
                                <span class="badge badge-muted font-mono" style="font-size:10px;"><?= htmlspecialchars($log['sumber_aksi']) ?></span>
                            </div>
                            <span class="activity-time"><?= Format::tanggal($log['waktu_kejadian'], true) ?></span>
                        </div>
                        <div class="activity-desc"><?= htmlspecialchars($log['deskripsi_aktivitas']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

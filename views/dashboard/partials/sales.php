<?php
/**
 * views/dashboard/partials/sales.php
 * Dasbor Terpersonalisasi Salesman Titip Jual Konsinyasi & Toko Binaan
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Helpers\Format;

$assignedStores = $roleData['assignedStores'] ?? [];
$recentVisits = $roleData['recentVisits'] ?? [];
$stats = $roleData['stats'] ?? [];
?>

<div class="space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Toko Binaan Saya -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid #a855f7;border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Toko Binaan</span>
                <i data-lucide="store" style="width:15px;height:15px;color:#a855f7;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                <?= (int)($stats['total_toko_binaan'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Outlet</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Outlet Langganan</span>
                <span class="badge badge-mono text-[9.5px]">AKTIF</span>
            </div>
        </div>

        <!-- 2. Perlu Opname / Dikunjungi -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Perlu Opname</span>
                <i data-lucide="calendar-clock" style="width:15px;height:15px;color:var(--color-warning);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= (int)($stats['perlu_dikunjungi'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">Toko</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Belum opname &gt; 7 hari</span>
                <span class="badge badge-warning font-mono text-[9.5px]">OPNAME</span>
            </div>
        </div>

        <!-- 3. Piutang Toko Binaan -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-danger, #ef4444);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Piutang Toko</span>
                <i data-lucide="receipt" style="width:15px;height:15px;color:var(--color-danger, #ef4444);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.4vw, 20px);font-weight:900;color:var(--color-danger, #ef4444);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= Format::rupiah((float)($stats['total_piutang_binaan'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Piutang Berjalan</span>
                <span class="badge badge-rose font-mono text-[9.5px]">TAGIHAN</span>
            </div>
        </div>

        <!-- 4. Laku Konsinyasi Bulan Ini -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Laku Bulan Ini</span>
                <i data-lucide="trending-up" style="width:15px;height:15px;color:var(--color-success);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.4vw, 20px);font-weight:900;color:var(--color-success);line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                <?= Format::rupiah((float)($stats['total_laku_bulan_ini'] ?? 0)) ?>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Total Omzet Laku</span>
                <span class="badge badge-success font-mono text-[9.5px]">KONSINYASI</span>
            </div>
        </div>

    </div>

    <!-- 2. QUICK ACTION LAUNCHPAD -->
    <div class="card p-4 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(168,85,247,0.12);color:#a855f7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="store" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 style="font-size:14.5px;font-weight:800;color:var(--color-ink);">Portal Konsinyasi &amp; Kunjungan Toko</h2>
                        <span class="badge badge-purple font-mono text-[9.5px]">SALES HUB</span>
                    </div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Catat opname fisik stok titip jual di rak toko, pantau laporan omzet laku, dan kelola penagihan piutang toko binaan.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <a href="<?= Router::url('/consignment/opname') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="clipboard-check" style="width:14px;height:14px;"></i>
                    <span>Opname Rak</span>
                </a>
                <a href="<?= Router::url('/consignment/laporan-penjualan') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="bar-chart-3" style="width:14px;height:14px;"></i>
                    <span>Laporan Laku</span>
                </a>
                <a href="<?= Router::url('/consignment/tagihan') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="receipt" style="width:14px;height:14px;"></i>
                    <span>Tagihan</span>
                </a>
                <a href="<?= Router::url('/customers') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="users" style="width:14px;height:14px;"></i>
                    <span>Daftar Toko</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. DUA KOLOM: TOKO BINAAN & LAPORAN LAKU KONSINYASI (Gaya 2-Pane /owner) -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5 items-stretch">
        
        <!-- KOLOM KIRI: TOKO BINAAN & JADWAL KUNJUNGAN OPNAME -->
        <div class="card flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div>
                <div class="p-4 sm:p-5 flex items-center justify-between border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(168,85,247,0.12);color:#a855f7;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="map-pinned" style="width:17px;height:17px;"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">Toko Binaan &amp; Jadwal Opname</h3>
                                <span class="badge badge-mono" style="font-size:10px;"><?= count($assignedStores) ?></span>
                            </div>
                            <p style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Jadwal opname rak dan saldo piutang berjalan</p>
                        </div>
                    </div>
                    <a href="<?= Router::url('/consignment') ?>" class="btn btn-ghost btn-sm" style="font-size:12px;font-weight:700;color:var(--color-primary);display:inline-flex;align-items:center;gap:4px;">
                        <span>Kelola Rak</span>
                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                    </a>
                </div>

                <?php if (empty($assignedStores)): ?>
                <div class="p-8 text-center flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);">
                    <div style="width:40px;height:40px;border-radius:50%;background:rgba(168,85,247,0.1);color:#a855f7;display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                        <i data-lucide="store" style="width:20px;height:20px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Belum Ada Toko yang Ditugaskan</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:320px;margin-top:2px;">Admin belum menetapkan toko binaan khusus ke akun Anda. Anda dapat melihat semua toko di portal konsinyasi.</p>
                </div>
                <?php else: ?>
                <div class="table-container" style="overflow-x:auto;">
                    <table class="table" style="margin-bottom:0;min-width:600px;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Toko / Outlet</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Wilayah</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Kunjungan Terakhir</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Piutang Berjalan</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assignedStores as $store): 
                                $lastVisit = $store['terakhir_kunjungan'];
                                $isOverdue = empty($lastVisit) || (strtotime($lastVisit) < strtotime('-7 days'));
                            ?>
                            <tr style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:12px 16px;">
                                    <strong style="color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($store['nama_toko']) ?></strong>
                                    <div class="font-mono" style="font-size:11px;color:var(--color-ink-mute);"><?= htmlspecialchars($store['kode_pelanggan']) ?></div>
                                </td>
                                <td style="padding:12px 16px;">
                                    <span class="badge badge-slate font-semibold" style="font-size:10px;padding:2px 8px;"><?= htmlspecialchars($store['nama_wilayah'] ?? $store['kode_rute'] ?? 'Rute Utama') ?></span>
                                </td>
                                <td style="padding:12px 16px;">
                                    <?php if ($lastVisit): ?>
                                        <span class="<?= $isOverdue ? 'badge badge-amber font-semibold' : 'text-xs text-muted' ?>" style="<?= $isOverdue ? 'font-size:10px;padding:2px 8px;' : '' ?>">
                                            <?= Format::tanggal($lastVisit) ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-danger font-semibold" style="font-size:10px;padding:2px 8px;">Belum Opname</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 16px;">
                                    <strong class="font-mono font-bold" style="font-size:13px;<?= (float)($store['total_piutang_berjalan'] ?? 0) > 0 ? 'color:var(--color-danger, #ef4444);' : 'color:var(--color-ink);' ?>">
                                        <?= Format::rupiah((float)($store['total_piutang_berjalan'] ?? 0)) ?>
                                    </strong>
                                </td>
                                <td style="padding:12px 16px;text-align:right;">
                                    <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . $store['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px;font-weight:700;padding:4px 10px;gap:4px;">
                                        <i data-lucide="clipboard-check" style="width:12px;height:12px;"></i>
                                        <span>Opname</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- KOLOM KANAN: LAPORAN LAKU & RIWAYAT KUNJUNGAN KONSINYASI -->
        <div class="card flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div>
                <div class="p-4 sm:p-5 flex items-center justify-between border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:34px;height:34px;border-radius:9px;background:rgba(34,197,94,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="file-check" style="width:17px;height:17px;"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">Laporan Laku &amp; Kunjungan</h3>
                                <span class="badge badge-mono" style="font-size:10px;"><?= count($recentVisits) ?></span>
                            </div>
                            <p style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Riwayat kunjungan toko dan laporan barang laku</p>
                        </div>
                    </div>
                    <a href="<?= Router::url('/consignment/laporan-penjualan') ?>" class="btn btn-ghost btn-sm" style="font-size:12px;font-weight:700;color:var(--color-primary);display:inline-flex;align-items:center;gap:4px;">
                        <span>Semua Laporan</span>
                        <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
                    </a>
                </div>

                <?php if (empty($recentVisits)): ?>
                <div class="p-8 text-center flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);">
                    <div style="width:40px;height:40px;border-radius:50%;background:rgba(34,197,94,0.1);color:var(--color-success);display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                        <i data-lucide="clipboard-list" style="width:20px;height:20px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Belum Ada Riwayat Kunjungan</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:320px;margin-top:2px;">Klik tombol "Opname Rak" di atas untuk mencatat opname stok dan omzet laku konsinyasi.</p>
                </div>
                <?php else: ?>
                <div class="table-container" style="overflow-x:auto;">
                    <table class="table" style="margin-bottom:0;min-width:600px;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">No. Kunjungan</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Toko / Outlet</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Total Laku</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Status</th>
                                <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentVisits as $visit): 
                                $bayarStatus = $visit['status_pembayaran'] ?? 'belum_lunas';
                                $badgeClass = match($bayarStatus) {
                                    'lunas'            => 'badge-success',
                                    'dibayar_sebagian' => 'badge-amber',
                                    default            => 'badge-slate'
                                };
                                $statusLabel = match($bayarStatus) {
                                    'lunas'            => 'Lunas',
                                    'dibayar_sebagian' => 'Sebagian',
                                    default            => 'Belum Lunas'
                                };
                            ?>
                            <tr style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:12px 16px;">
                                    <strong class="font-mono text-primary" style="font-size:12.5px;"><?= htmlspecialchars($visit['nomor_kunjungan']) ?></strong>
                                    <div style="font-size:11px;color:var(--color-ink-mute);"><?= Format::tanggal($visit['tanggal_kunjungan']) ?></div>
                                </td>
                                <td style="padding:12px 16px;">
                                    <strong style="color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($visit['nama_toko']) ?></strong>
                                    <?php if (!empty($visit['nama_wilayah'])): ?>
                                        <div style="font-size:11px;color:var(--color-ink-mute);"><?= htmlspecialchars($visit['nama_wilayah']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 16px;">
                                    <strong class="font-mono font-bold" style="font-size:13px;color:var(--color-success);"><?= Format::rupiah((float)$visit['total_laku_nominal']) ?></strong>
                                </td>
                                <td style="padding:12px 16px;">
                                    <span class="badge <?= $badgeClass ?> font-semibold" style="font-size:10px;padding:2px 8px;"><?= $statusLabel ?></span>
                                    <?php if (!empty($visit['nomor_nota'])): ?>
                                        <div class="font-mono" style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;"><?= htmlspecialchars($visit['nomor_nota']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding:12px 16px;text-align:right;">
                                    <a href="<?= Router::url('/consignment/opname/hasil?kunjungan_id=' . $visit['id']) ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px;font-weight:700;padding:4px 10px;gap:4px;">
                                        <i data-lucide="eye" style="width:12px;height:12px;"></i>
                                        <span>Nota</span>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

</div>


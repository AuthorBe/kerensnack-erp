<?php
/**
 * views/dashboard/partials/driver.php
 * Dasbor Terpersonalisasi Pengemudi / Kurir Logistik
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Helpers\Format;

$deliveries = $roleData['deliveries'] ?? [];
$stats = $roleData['stats'] ?? [];
?>

<div class="space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Tugas Pengiriman -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Tugas Antar</span>
                <i data-lucide="truck" style="width:15px;height:15px;color:var(--color-primary);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                <?= (int)($stats['total_tugas'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Penugasan Saya</span>
                <span class="badge badge-mono text-[9.5px]">RUTE</span>
            </div>
        </div>

        <!-- 2. Perlu Diantar -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Perlu Diantar</span>
                <i data-lucide="clock" style="width:15px;height:15px;color:var(--color-warning);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= (int)($stats['pending_rute'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Dalam Antrean Rute</span>
                <span class="badge badge-warning font-mono text-[9.5px]">ANTAR</span>
            </div>
        </div>

        <!-- 3. Selesai Diantar -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Selesai Antar</span>
                <i data-lucide="check-circle-2" style="width:15px;height:15px;color:var(--color-success);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                <?= (int)($stats['selesai_antar'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Diterima Toko</span>
                <span class="badge badge-success font-mono text-[9.5px]">POD OK</span>
            </div>
        </div>

        <!-- 4. Gagal / Kendala -->
        <div class="card p-3.5 sm:p-4 space-y-2" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-danger);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;">
                <span style="font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Gagal / Retur</span>
                <i data-lucide="alert-triangle" style="width:15px;height:15px;color:var(--color-danger);"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(16px, 2.8vw, 22px);font-weight:900;color:var(--color-danger);line-height:1.2;">
                <?= (int)($stats['gagal_antar'] ?? 0) ?> <span style="font-size:13px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;font-size:10.5px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span>Toko Tutup / Retur</span>
                <span class="font-mono text-[10px]">7 Hari Terakhir</span>
            </div>
        </div>

    </div>

    <!-- 2. QUICK ACTION LAUNCHPAD -->
    <div class="card p-4 sm:p-5" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);">
        <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4">
            <div class="flex items-start sm:items-center gap-3">
                <div style="width:38px;height:38px;border-radius:10px;background:rgba(6,182,212,0.12);color:#06b6d4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="truck" style="width:18px;height:18px;"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 style="font-size:14.5px;font-weight:800;color:var(--color-ink);">Portal Pengiriman &amp; Rute Kurir Armada</h2>
                        <span class="badge badge-primary font-mono text-[9.5px]">LOGISTIK HUB</span>
                    </div>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Akses navigasi rute pengantaran toko, konfirmasi penerimaan surat jalan, dan unggah foto bukti terima.</p>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:flex sm:items-center gap-2 w-full xl:w-auto">
                <a href="<?= Router::url('/driver-deliveries') ?>" class="btn btn-primary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="map-pin" style="width:14px;height:14px;"></i>
                    <span>Rute Pengiriman</span>
                </a>
                <a href="<?= Router::url('/deliveries') ?>" class="btn btn-secondary btn-sm justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;height:36px;white-space:nowrap;">
                    <i data-lucide="truck" style="width:14px;height:14px;"></i>
                    <span>Surat Jalan</span>
                </a>
            </div>
        </div>
    </div>

    <!-- 3. TABEL SURAT JALAN & DAFTAR TOKO TUJUAN (Gaya Tabel /owner) -->
    <div class="card overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
        <div class="p-4 sm:p-5 flex items-center justify-between border-b border-hairline" style="background:var(--color-canvas);">
            <div class="flex items-center gap-2.5">
                <div style="width:34px;height:34px;border-radius:9px;background:rgba(6,182,212,0.12);color:#06b6d4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="navigation" style="width:17px;height:17px;"></i>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);">Daftar Rute Pengantaran Toko</h3>
                        <span class="badge badge-mono" style="font-size:10px;"><?= count($deliveries) ?></span>
                    </div>
                    <p style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">Surat jalan dan rute tujuan pengiriman hari ini</p>
                </div>
            </div>
            <a href="<?= Router::url('/driver-deliveries') ?>" class="btn btn-ghost btn-sm" style="font-size:12px;font-weight:700;color:var(--color-primary);display:inline-flex;align-items:center;gap:4px;">
                <span>Detail Rute Maps</span>
                <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
            </a>
        </div>

        <?php if (empty($deliveries)): ?>
        <div class="p-8 text-center flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);">
            <div style="width:40px;height:40px;border-radius:50%;background:rgba(6,182,212,0.1);color:#06b6d4;display:inline-flex;align-items:center;justify-content:center;margin-bottom:8px;">
                <i data-lucide="package-check" style="width:20px;height:20px;"></i>
            </div>
            <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">Belum Ada Penugasan Surat Jalan</h4>
            <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:320px;margin-top:2px;">Seluruh paket pengiriman Anda sudah selesai diantar atau belum ada jadwal rute baru dari admin.</p>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="table" style="margin-bottom:0;">
                <thead>
                    <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">No. Surat Jalan</th>
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Toko Tujuan</th>
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Wilayah / Rute</th>
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Nilai Nota</th>
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;">Status Pengiriman</th>
                        <th style="font-size:10.5px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:10px 16px;text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($deliveries as $del): 
                        $st = $del['status_surat_jalan'] ?? 'draf_n8n';
                        $badgeClass = match($st) {
                            'sedang_dikirim'  => 'badge-amber',
                            'selesai_diterima'=> 'badge-success',
                            'gagal_kembali'   => 'badge-danger',
                            default           => 'badge-primary'
                        };
                        $badgeLabel = match($st) {
                            'sedang_dikirim'  => 'Dalam Perjalanan',
                            'selesai_diterima'=> 'Selesai Diterima',
                            'gagal_kembali'   => 'Gagal / Retur',
                            'disetujui_owner' => 'Siap Berangkat',
                            default           => 'Draft Rute'
                        };
                    ?>
                    <tr style="border-bottom:1px solid var(--color-hairline);">
                        <td style="padding:12px 16px;">
                            <strong class="font-mono text-primary" style="font-size:12.5px;"><?= htmlspecialchars($del['nomor_surat_jalan'] ?? '—') ?></strong>
                            <div style="font-size:11px;color:var(--color-ink-mute);"><?= Format::tanggal($del['tanggal_surat_jalan'] ?? date('Y-m-d')) ?></div>
                        </td>
                        <td style="padding:12px 16px;">
                            <strong style="color:var(--color-ink);font-size:13px;"><?= htmlspecialchars($del['nama_toko'] ?? 'Pelanggan Umum') ?></strong>
                            <div class="text-xs text-muted truncate max-w-xs" title="<?= htmlspecialchars($del['alamat_lengkap'] ?? '') ?>">
                                <?= htmlspecialchars($del['alamat_lengkap'] ?? '—') ?>
                            </div>
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="badge badge-slate font-semibold" style="font-size:10px;padding:2px 8px;"><?= htmlspecialchars($del['nama_wilayah'] ?? $del['kode_rute'] ?? 'Rute Utama') ?></span>
                        </td>
                        <td style="padding:12px 16px;">
                            <strong class="font-mono font-bold" style="font-size:13px;"><?= Format::rupiah((float)($del['total_netto'] ?? 0)) ?></strong>
                        </td>
                        <td style="padding:12px 16px;">
                            <span class="badge <?= $badgeClass ?> font-semibold" style="font-size:10px;padding:2px 8px;"><?= $badgeLabel ?></span>
                        </td>
                        <td style="padding:12px 16px;text-align:right;">
                            <a href="<?= Router::url('/driver-deliveries') ?>" class="btn btn-secondary btn-sm" style="font-size:11.5px;font-weight:700;padding:4px 10px;">
                                <i data-lucide="play" style="width:12px;height:12px;"></i>
                                <span>Proses</span>
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

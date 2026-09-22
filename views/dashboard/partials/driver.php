<?php
/**
 * views/dashboard/partials/driver.php
 * Dasbor Terpersonalisasi Pengemudi / Kurir Logistik
 * Pola Desain Kanonikal: Sesuai /owner (KEREN SNACK ERP)
 */

use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;

$deliveries = $roleData['deliveries'] ?? [];
$stats = $roleData['stats'] ?? [];
?>

<div class="space-y-4 sm:space-y-5">

    <!-- 1. 4 KPI STAT CARDS (Gaya Kartu Finansial /owner) -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        
        <!-- 1. Tugas Pengiriman -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-primary);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">1. Tugas Antar</span>
                <i data-lucide="truck" style="width:14px;height:14px;color:var(--color-primary);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-ink);line-height:1.2;">
                <?= (int)($stats['total_tugas'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Tugas Saya</span>
                <span class="badge badge-mono text-[9px] px-1.5 py-0 flex-shrink-0">RUTE</span>
            </div>
        </div>

        <!-- 2. Perlu Diantar -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-warning);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">2. Antrean Rute</span>
                <i data-lucide="clock" style="width:14px;height:14px;color:var(--color-warning);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-warning);line-height:1.2;">
                <?= (int)($stats['pending_rute'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Dalam Rute</span>
                <span class="badge badge-warning font-mono text-[9px] px-1.5 py-0 flex-shrink-0">ANTAR</span>
            </div>
        </div>

        <!-- 3. Selesai Diantar -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-success);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">3. Selesai Antar</span>
                <i data-lucide="check-circle-2" style="width:14px;height:14px;color:var(--color-success);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-success);line-height:1.2;">
                <?= (int)($stats['selesai_antar'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Diterima Toko</span>
                <span class="badge badge-success font-mono text-[9px] px-1.5 py-0 flex-shrink-0">POD OK</span>
            </div>
        </div>

        <!-- 4. Gagal / Kendala -->
        <div class="card p-3 sm:p-4 space-y-2 flex flex-col justify-between" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3.5px solid var(--color-danger);border-radius:14px;box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;">
                <span class="truncate" style="font-size:10px;sm:font-size:10.5px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">4. Gagal / Retur</span>
                <i data-lucide="alert-triangle" style="width:14px;height:14px;color:var(--color-danger);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(15px, 2.6vw, 22px);font-weight:900;color:var(--color-danger);line-height:1.2;">
                <?= (int)($stats['gagal_antar'] ?? 0) ?> <span style="font-size:12px;font-weight:700;color:var(--color-ink-mute);">SJ</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;font-size:10px;color:var(--color-ink-mute);padding-top:4px;border-top:1px dashed var(--color-hairline);">
                <span class="truncate">Toko Tutup / Retur</span>
                <span class="font-mono text-[9.5px] flex-shrink-0">7 Hari</span>
            </div>
        </div>

    </div>

    <!-- 2. DUA KOLOM: DAFTAR RUTE PENGANTARAN & SOP LOGISTIK -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 sm:gap-5 items-stretch">
        
        <!-- KOLOM UTAMA (8/12): TABEL SURAT JALAN & DAFTAR TOKO TUJUAN -->
        <div class="lg:col-span-8 card h-full flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div class="flex-1 flex flex-col">
                <!-- Card Header (Standard 'Lihat semua >' Button) -->
                <div class="px-3 py-2.5 sm:px-4 sm:py-3.5 border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center justify-between gap-2 sm:gap-3">
                        <div class="flex items-center gap-2 sm:gap-2.5 min-w-0 flex-1">
                            <div style="width:30px;height:30px;border-radius:8px;background:rgba(6,182,212,0.12);color:#06b6d4;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                <i data-lucide="navigation" style="width:15px;height:15px;"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-1.5 min-w-0">
                                    <h3 class="truncate" style="font-size:12px;sm:font-size:13px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;flex-shrink:1;">Daftar Rute Pengantaran Toko</h3>
                                    <span class="inline-flex items-center justify-center flex-shrink-0 font-mono" style="font-size:9.5px;font-weight:700;padding:1px 5px;border-radius:9999px;background:rgba(6,182,212,0.12);color:#06b6d4;border:1px solid rgba(6,182,212,0.25);line-height:1.2;min-width:18px;"><?= count($deliveries) ?></span>
                                </div>
                                <p class="truncate" style="font-size:10.5px;sm:font-size:11px;color:var(--color-ink-mute);margin-top:2px;line-height:1.25;">Surat jalan dan rute tujuan pengiriman hari ini</p>
                            </div>
                        </div>
                        <?php if (Auth::can(['deliveries.view_assigned', 'deliveries.view_all'])): ?>
                        <a href="<?= Router::url('/driver-deliveries') ?>" class="btn btn-secondary btn-sm" style="font-size:10.5px;sm:font-size:11px;font-weight:700;padding:3px 8px;border-radius:7px;display:inline-flex;align-items:center;gap:3px;background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink);box-shadow:0 1px 2px rgba(0,0,0,0.04);white-space:nowrap;flex-shrink:0;height:28px;">
                            <span>Lihat semua</span>
                            <i data-lucide="chevron-right" style="width:12px;height:12px;color:var(--color-ink-mute);"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Card Body -->
                <?php if (empty($deliveries)): ?>
                <div class="p-6 sm:p-8 text-center flex-1 flex flex-col items-center justify-center min-h-[200px]" style="background:var(--color-canvas);padding-top:32px;padding-bottom:32px;">
                    <div style="width:44px;height:44px;border-radius:50%;background:rgba(6,182,212,0.1);color:#06b6d4;display:flex;align-items:center;justify-content:center;margin:0 auto 12px auto;flex-shrink:0;">
                        <i data-lucide="package-check" style="width:22px;height:22px;"></i>
                    </div>
                    <h4 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0 0 4px 0;">Belum Ada Penugasan Surat Jalan</h4>
                    <p style="font-size:11.5px;color:var(--color-ink-mute);max-width:290px;margin:0 auto;line-height:1.4;">
                        Seluruh pengiriman sudah selesai diantar atau belum ada rute baru.
                    </p>
                    <?php if (Auth::can(['deliveries.view_assigned', 'deliveries.view_all'])): ?>
                    <div class="mt-3.5">
                        <a href="<?= Router::url('/driver-deliveries') ?>" class="btn btn-secondary btn-sm" style="font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px;padding:5px 12px;border-radius:8px;">
                            <i data-lucide="map" style="width:12px;height:12px;"></i>
                            <span>Buka Navigasi Rute</span>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="table-container overflow-x-auto" style="width:100%;-webkit-overflow-scrolling:touch;">
                    <table class="table w-full text-left" style="margin-bottom:0;border-collapse:collapse;min-width:480px;">
                        <thead>
                            <tr style="background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);">
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">No. SJ</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Toko Tujuan</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Wilayah</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Total</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;">Status</th>
                                <th style="font-size:10px;font-weight:800;letter-spacing:0.04em;text-transform:uppercase;color:var(--color-ink-mute);padding:8px 12px;text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deliveries as $del): 
                                $st = $del['status_surat_jalan'] ?? 'siap_kirim';
                                $badgeClass = match($st) {
                                    'sedang_dikirim'  => 'badge-amber',
                                    'selesai_diterima'=> 'badge-success',
                                    'gagal_kembali'   => 'badge-danger',
                                    default           => 'badge-primary'
                                };
                                $badgeLabel = match($st) {
                                    'sedang_dikirim'  => 'Di Jalan',
                                    'selesai_diterima'=> 'Diterima',
                                    'gagal_kembali'   => 'Retur',
                                    'siap_kirim'      => 'Siap Kirim',
                                    default           => ucfirst(str_replace('_', ' ', $st))
                                };
                            ?>
                            <tr class="hover:bg-slate-500/5 transition-colors" style="border-bottom:1px solid var(--color-hairline);">
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong class="font-mono" style="font-size:12px;color:var(--color-primary);"><?= htmlspecialchars($del['nomor_surat_jalan'] ?? '—') ?></strong>
                                    <div style="font-size:10.5px;color:var(--color-ink-mute);"><?= Format::tanggal($del['tanggal_surat_jalan'] ?? date('Y-m-d')) ?></div>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;">
                                    <strong style="color:var(--color-ink);font-size:12.5px;" class="block truncate max-w-[140px]"><?= htmlspecialchars($del['nama_toko'] ?? 'Pelanggan Umum') ?></strong>
                                    <div class="text-xs text-muted truncate max-w-xs" title="<?= htmlspecialchars($del['alamat_lengkap'] ?? '') ?>">
                                        <?= htmlspecialchars($del['alamat_lengkap'] ?? '—') ?>
                                    </div>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <span class="badge badge-slate font-semibold text-[9.5px] px-2 py-0.5"><?= htmlspecialchars($del['nama_wilayah'] ?? $del['kode_rute'] ?? 'Rute Utama') ?></span>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <strong class="font-mono font-bold text-[12.5px]" style="color:var(--color-ink);"><?= Format::rupiah((float)($del['total_netto'] ?? 0)) ?></strong>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;white-space:nowrap;">
                                    <span class="badge <?= $badgeClass ?> font-semibold text-[9.5px] px-2 py-0.5"><?= $badgeLabel ?></span>
                                </td>
                                <td style="padding:10px 12px;vertical-align:middle;text-align:right;">
                                    <?php if (Auth::can(['deliveries.view_assigned', 'deliveries.view_all', 'deliveries.update_assigned', 'deliveries.update_all'])): ?>
                                    <a href="<?= Router::url('/driver-deliveries') ?>" class="btn btn-secondary btn-sm" style="font-size:11px;font-weight:700;padding:3px 8px;">
                                        <span>Proses</span>
                                        <i data-lucide="arrow-right" style="width:11px;height:11px;"></i>
                                    </a>
                                    <?php else: ?>
                                    <span class="text-muted text-[11px]">—</span>
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

        <!-- KOLOM SAMPING (4/12): SOP PENGIRIMAN & CHECKLIST KURIR -->
        <div class="lg:col-span-4 card h-full flex flex-col justify-between overflow-hidden" style="border-radius:16px;border:1px solid var(--color-hairline);box-shadow:var(--shadow-1);background:var(--color-canvas);">
            <div class="flex-1 flex flex-col">
                <!-- Card Header (Tombol Aksi di Bawah Subjudul) -->
                <div class="px-3 py-2.5 sm:px-4 sm:py-3.5 border-b border-hairline" style="background:var(--color-canvas);">
                    <div class="flex items-center gap-2 sm:gap-2.5">
                        <div style="width:30px;height:30px;border-radius:8px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="shield-check" style="width:15px;height:15px;"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 style="font-size:12.5px;sm:font-size:13.5px;font-weight:800;color:var(--color-ink);line-height:1.2;margin:0;" class="truncate">SOP &amp; Checklist Kurir</h3>
                            <p style="font-size:10.5px;sm:font-size:11px;color:var(--color-ink-mute);margin-top:1px;line-height:1.25;" class="truncate">Panduan kelengkapan pengiriman armada</p>
                        </div>
                    </div>
                </div>

                <!-- Checklist Content -->
                <div class="p-3.5 sm:p-4 space-y-2.5 flex-1">
                    
                    <div class="p-2.5 sm:p-3 rounded-xl flex items-center gap-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="width:26px;height:26px;border-radius:7px;background:rgba(37,99,235,0.1);color:var(--color-primary);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;flex-shrink:0;">
                            1
                        </div>
                        <div class="min-w-0 text-xs flex-1">
                            <strong style="color:var(--color-ink);display:block;font-size:12px;">Cek Fisik Muatan</strong>
                            <p style="color:var(--color-ink-mute);font-size:10.5px;margin-top:1px;line-height:1.3;">Pastikan dus &amp; varian rasa sesuai faktur sebelum berangkat.</p>
                        </div>
                    </div>

                    <div class="p-2.5 sm:p-3 rounded-xl flex items-center gap-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="width:26px;height:26px;border-radius:7px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;flex-shrink:0;">
                            2
                        </div>
                        <div class="min-w-0 text-xs flex-1">
                            <strong style="color:var(--color-ink);display:block;font-size:12px;">TTD &amp; Stempel Outlet</strong>
                            <p style="color:var(--color-ink-mute);font-size:10.5px;margin-top:1px;line-height:1.3;">Wajib paraf penerima &amp; cap toko pada surat jalan fisik.</p>
                        </div>
                    </div>

                    <div class="p-2.5 sm:p-3 rounded-xl flex items-center gap-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="width:26px;height:26px;border-radius:7px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;flex-shrink:0;">
                            3
                        </div>
                        <div class="min-w-0 text-xs flex-1">
                            <strong style="color:var(--color-ink);display:block;font-size:12px;">Unggah Foto (POD)</strong>
                            <p style="color:var(--color-ink-mute);font-size:10.5px;margin-top:1px;line-height:1.3;">Ambil foto toko &amp; barang saat serah terima di aplikasi.</p>
                        </div>
                    </div>

                    <div class="p-2.5 sm:p-3 rounded-xl flex items-center gap-3" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="width:26px;height:26px;border-radius:7px;background:rgba(239,68,68,0.1);color:var(--color-danger);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:900;flex-shrink:0;">
                            4
                        </div>
                        <div class="min-w-0 text-xs flex-1">
                            <strong style="color:var(--color-ink);display:block;font-size:12px;">Laporkan Kendala</strong>
                            <p style="color:var(--color-ink-mute);font-size:10.5px;margin-top:1px;line-height:1.3;">Jika toko tutup atau barang retur, hubungi admin segera.</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

    </div>

</div>

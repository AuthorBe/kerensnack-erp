<?php
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div class="space-y-4 sm:space-y-6 pb-20">

    <!-- PAGE HEADER -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary btn-sm p-2 rounded-xl" title="Kembali ke Portal">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#ea580c;"></span>
                    <span>Monitoring Risiko Rak • Admin &amp; Owner</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl">Early Warning Toko</h1>
                <p class="page-subtitle text-xs sm:text-sm">Daftar toko konsinyasi aktif yang belum diopname lebih dari 14 hari atau belum pernah dikunjungi.</p>
            </div>
        </div>
    </div>

    <!-- NOTICE BANNER -->
    <div class="p-3.5 sm:p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="background:rgba(234,88,12,0.04);border:1px solid var(--color-hairline);">
        <div class="flex items-center gap-3">
            <i data-lucide="alert-circle" class="w-6 h-6 flex-shrink-0" style="color:#ea580c;"></i>
            <div>
                <div class="text-xs sm:text-sm font-bold" style="color:var(--color-ink);">Batas Waktu Opname Berkala: <?= $thresholdDays ?> Hari</div>
                <div class="text-[11px] sm:text-xs" style="color:var(--color-ink-mute);">Toko yang melewati batas waktu berisiko kehilangan kontrol stok fisik dan piutang macet.</div>
            </div>
        </div>

        <div class="text-xs font-bold text-orange-500">
            Terdeteksi: <?= count($stores) ?> Toko Terlambat
        </div>
    </div>

    <!-- TABLE EARLY WARNING -->
    <?php if (empty($stores)): ?>
        <div class="card p-8 sm:p-12 text-center rounded-3xl" style="border:1px solid var(--color-hairline);">
            <i data-lucide="shield-check" class="w-12 h-12 mx-auto mb-3" style="color:#10b981;"></i>
            <h3 class="text-base sm:text-lg font-bold" style="color:var(--color-ink);">Semua Toko Terkontrol dengan Baik!</h3>
            <p class="text-xs sm:text-sm mt-1 max-w-md mx-auto" style="color:var(--color-ink-mute);">
                Semua toko konsinyasi aktif sudah diopname dalam 14 hari terakhir. Tidak ada risiko keterlambatan opname. 👍
            </p>
        </div>
    <?php else: ?>
        <div class="card rounded-3xl overflow-hidden" style="border:1px solid var(--color-hairline);">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs min-w-[650px]">
                    <thead>
                        <tr class="font-bold uppercase tracking-wider text-[10.5px]" style="background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);">
                            <th class="py-3.5 px-4">Toko Mitra</th>
                            <th class="py-3.5 px-4">Sales Pemegang</th>
                            <th class="py-3.5 px-4 text-center">Stok Titip Rak</th>
                            <th class="py-3.5 px-4 text-center">Terakhir Opname</th>
                            <th class="py-3.5 px-4 text-center">Keterlambatan</th>
                            <th class="py-3.5 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y" style="border-color:var(--color-hairline);">
                        <?php foreach ($stores as $st): 
                            $days = (int)$st['hari_sejak_opname'];
                        ?>
                        <tr class="hover:bg-slate-500/5 transition-colors">
                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink);">
                                <div class="flex items-center gap-1.5">
                                    <span style="font-size:10.5px;font-weight:800;color:#ea580c;letter-spacing:0.04em;">[<?= htmlspecialchars($st['kode_pelanggan'] ?? 'TOKO') ?>]</span>
                                    <span class="text-xs sm:text-sm font-black"><?= htmlspecialchars($st['nama_toko']) ?></span>
                                </div>
                                <span class="text-[10.5px] font-normal block mt-0.5" style="color:var(--color-ink-mute);"><?= htmlspecialchars($st['alamat_lengkap'] ?? '-') ?></span>
                            </td>

                            <td class="py-3.5 px-4 font-bold" style="color:var(--color-ink-secondary);">
                                <?= htmlspecialchars($st['nama_sales'] ?? 'Belum Di-assign') ?>
                            </td>

                            <td class="py-3.5 px-4 text-center font-black text-sky-600 dark:text-sky-400 text-xs sm:text-sm">
                                <?= number_format((float)$st['total_pcs_titip']) ?> pcs
                            </td>

                            <td class="py-3.5 px-4 text-center" style="color:var(--color-ink-mute);">
                                <?= !empty($st['terakhir_opname']) ? date('d/m/Y', strtotime($st['terakhir_opname'])) : '<span style="color:var(--color-ink-mute);">Belum pernah</span>' ?>
                            </td>

                            <td class="py-3.5 px-4 text-center">
                                <?php if ($days === 999): ?>
                                    <span style="background:rgba(244,63,94,0.15);color:#f43f5e;border:1px solid rgba(244,63,94,0.3);padding:2px 8px;border-radius:12px;font-weight:800;font-size:9.5px;">
                                        BELUM OPNAME
                                    </span>
                                <?php else: ?>
                                    <span style="background:rgba(244,63,94,0.15);color:#f43f5e;border:1px solid rgba(244,63,94,0.3);padding:2px 8px;border-radius:12px;font-weight:800;font-size:9.5px;">
                                        <?= $days ?> HARI LALU
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <a href="<?= Router::url('/consignment/opname?pelanggan_id=' . urlencode((string)$st['id'])) ?>" class="btn btn-primary btn-sm flex items-center justify-center gap-1.5 py-1.5 px-3 rounded-xl text-xs font-bold" style="background:#ea580c;border-color:#ea580c;">
                                    <i data-lucide="clipboard-check" class="w-3.5 h-3.5"></i>
                                    <span>Opname</span>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

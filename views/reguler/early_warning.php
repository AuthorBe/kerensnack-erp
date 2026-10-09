<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="{ activeTab: 'over_plafon' }" class="space-y-4 sm:space-y-6 pb-20">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER                                                            -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <a href="<?= Router::url('/reguler') ?>" 
               class="btn btn-secondary btn-sm p-2 rounded-xl" 
               title="Kembali ke Portal Reguler">
                <i data-lucide="arrow-left" class="w-5 h-5" style="pointer-events:none;"></i>
            </a>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#ea580c;"></span>
                    <span>Monitoring Risiko Kredit Toko &bull; Admin &amp; Owner</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= $pageTitle ?? 'Early Warning Kredit Toko' ?></h1>
                <p class="page-subtitle text-xs sm:text-sm"><?= $pageSubtitle ?? 'Deteksi Dini Toko Over-Plafon, Faktur Menumpuk, Tagihan Overdue &amp; Toko Pasif Berhutang' ?></p>
            </div>
        </div>
        <div class="page-header-actions flex items-center gap-2">
            <a href="<?= Router::url('/reguler/tagihan') ?>" 
               class="btn btn-secondary flex items-center gap-2 text-xs font-bold"
               style="border-radius:12px;">
                <i data-lucide="book-open-check" class="w-4 h-4 text-blue-600"></i>
                <span>Buku Piutang Toko</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. TAB CONTROLS RADAR RISIKO (CLEAN SEGMENTED CONTROL)                    -->
    <!-- ========================================================================= -->
    <div class="flex items-center gap-2 overflow-x-auto pb-1 custom-scrollbar">
        <button type="button" @click="activeTab = 'over_plafon'" 
                :class="activeTab === 'over_plafon' ? 'btn-primary' : 'btn-secondary text-ink-mute'"
                class="btn btn-sm flex items-center gap-2 font-bold whitespace-nowrap"
                style="height:38px;border-radius:12px;">
            <i data-lucide="shield-alert" class="w-4 h-4"></i>
            <span>Over-Plafon</span>
            <span class="badge-counter" style="background:rgba(239,68,68,0.2);color:#ef4444;"><?= count($overPlafonStores) ?></span>
        </button>

        <button type="button" @click="activeTab = 'menumpuk'" 
                :class="activeTab === 'menumpuk' ? 'btn-primary' : 'btn-secondary text-ink-mute'"
                class="btn btn-sm flex items-center gap-2 font-bold whitespace-nowrap"
                style="height:38px;border-radius:12px;">
            <i data-lucide="layers" class="w-4 h-4"></i>
            <span>Tempo Faktur Menumpuk (&ge;2 Nota)</span>
            <span class="badge-counter" style="background:rgba(234,88,12,0.2);color:#ea580c;"><?= count($menumpukStores) ?></span>
        </button>

        <button type="button" @click="activeTab = 'overdue'" 
                :class="activeTab === 'overdue' ? 'btn-primary' : 'btn-secondary text-ink-mute'"
                class="btn btn-sm flex items-center gap-2 font-bold whitespace-nowrap"
                style="height:38px;border-radius:12px;">
            <i data-lucide="calendar-x" class="w-4 h-4"></i>
            <span>Overdue Kalender</span>
            <span class="badge-counter" style="background:rgba(220,38,38,0.2);color:#dc2626;"><?= count($overdueStores) ?></span>
        </button>

        <button type="button" @click="activeTab = 'dormant'" 
                :class="activeTab === 'dormant' ? 'btn-primary' : 'btn-secondary text-ink-mute'"
                class="btn btn-sm flex items-center gap-2 font-bold whitespace-nowrap"
                style="height:38px;border-radius:12px;">
            <i data-lucide="user-x" class="w-4 h-4"></i>
            <span>Toko Pasif Berhutang (&gt;30 Hari)</span>
            <span class="badge-counter" style="background:rgba(100,116,139,0.2);color:#64748b;"><?= count($dormantStores) ?></span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. TAB PANE 1: OVER-PLAFON                                                -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'over_plafon'" x-cloak class="card p-0 rounded-2xl overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--color-hairline);background:rgba(239,68,68,0.03);">
            <div class="flex items-center gap-2.5">
                <i data-lucide="alert-octagon" class="w-5 h-5 text-danger"></i>
                <div>
                    <h3 class="text-sm font-black text-ink">Daftar Toko Melebihi Plafon Kredit</h3>
                    <p class="text-xs text-ink-mute">Toko dengan total piutang berjalan melampaui limit kredit yang telah ditetapkan</p>
                </div>
            </div>
            <span class="badge badge-danger"><?= count($overPlafonStores) ?> Toko Terdeteksi</span>
        </div>

        <?php if (empty($overPlafonStores)): ?>
            <div class="p-8 text-center text-ink-mute text-xs">
                <i data-lucide="shield-check" class="w-10 h-10 mx-auto mb-2 text-emerald-500"></i>
                <div class="font-bold text-sm text-ink">Semua Piutang Terkendali</div>
                <div>Tidak ada toko yang melampaui batas plafon kredit maksimal saat ini.</div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="data-table min-w-[750px]">
                    <thead>
                        <tr>
                            <th style="width:40px;" class="cell-center">No</th>
                            <th>Toko Mitra</th>
                            <th>Wilayah &amp; Sales</th>
                            <th class="cell-right">Plafon Limit</th>
                            <th class="cell-right">Piutang Berjalan</th>
                            <th class="cell-right">Kelebihan Plafon</th>
                            <th class="cell-center" style="width:110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overPlafonStores as $idx => $st): ?>
                        <tr>
                            <td class="cell-center font-bold text-ink-mute"><?= $idx + 1 ?></td>
                            <td>
                                <div class="font-bold text-sm text-ink"><?= htmlspecialchars($st['nama_toko']) ?></div>
                                <div class="text-xs text-ink-mute font-mono"><?= htmlspecialchars($st['kode_pelanggan']) ?> &bull; <?= htmlspecialchars($st['nama_pemilik'] ?? '-') ?></div>
                            </td>
                            <td>
                                <div class="font-semibold text-xs text-ink"><?= htmlspecialchars($st['nama_wilayah'] ?? '—') ?></div>
                                <div class="text-[11px] text-ink-mute">Sales: <?= htmlspecialchars($st['nama_sales'] ?? '—') ?></div>
                            </td>
                            <td class="cell-right font-mono font-bold text-ink-mute"><?= Format::rupiah((float)$st['plafon_piutang']) ?></td>
                            <td class="cell-right font-mono font-black text-danger"><?= Format::rupiah((float)$st['total_piutang_berjalan']) ?></td>
                            <td class="cell-right font-mono font-black text-danger">
                                +<?= Format::rupiah((float)$st['kelebihan_plafon']) ?>
                            </td>
                            <td class="cell-center">
                                <a href="<?= Router::url('/reguler/tagihan?q=' . urlencode($st['kode_pelanggan'])) ?>" class="btn btn-secondary btn-sm text-xs font-bold">
                                    Buka Tagihan
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 4. TAB PANE 2: TEMPO FAKTUR MENUMPUK (>= 2 NOTA GANTUNG)                  -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'menumpuk'" x-cloak class="card p-0 rounded-2xl overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--color-hairline);background:rgba(234,88,12,0.03);">
            <div class="flex items-center gap-2.5">
                <i data-lucide="layers" class="w-5 h-5 text-warning"></i>
                <div>
                    <h3 class="text-sm font-black text-ink">Toko Menumpuk Faktur Tempo (&ge; 2 Nota)</h3>
                    <p class="text-xs text-ink-mute">Toko dengan skema Tempo Faktur yang mengambil nota baru sebelum nota sebelumnya dilunasi</p>
                </div>
            </div>
            <span class="badge badge-warning"><?= count($menumpukStores) ?> Toko Terdeteksi</span>
        </div>

        <?php if (empty($menumpukStores)): ?>
            <div class="p-8 text-center text-ink-mute text-xs">
                <i data-lucide="check-circle" class="w-10 h-10 mx-auto mb-2 text-emerald-500"></i>
                <div class="font-bold text-sm text-ink">Asas 1-to-1 Rolling Tertib</div>
                <div>Tidak ada toko tempo faktur yang memiliki 2 nota gantung secara bersamaan.</div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="data-table min-w-[750px]">
                    <thead>
                        <tr>
                            <th style="width:40px;" class="cell-center">No</th>
                            <th>Toko Mitra</th>
                            <th>Wilayah &amp; Sales</th>
                            <th class="cell-center">Jumlah Nota</th>
                            <th class="cell-center">Usia Nota Terlama</th>
                            <th class="cell-right">Total Piutang</th>
                            <th class="cell-center" style="width:110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($menumpukStores as $idx => $st): ?>
                        <tr>
                            <td class="cell-center font-bold text-ink-mute"><?= $idx + 1 ?></td>
                            <td>
                                <div class="font-bold text-sm text-ink"><?= htmlspecialchars($st['nama_toko']) ?></div>
                                <div class="text-xs text-ink-mute font-mono"><?= htmlspecialchars($st['kode_pelanggan']) ?> &bull; <?= htmlspecialchars($st['nama_pemilik'] ?? '-') ?></div>
                            </td>
                            <td>
                                <div class="font-semibold text-xs text-ink"><?= htmlspecialchars($st['nama_wilayah'] ?? '—') ?></div>
                                <div class="text-[11px] text-ink-mute">Sales: <?= htmlspecialchars($st['nama_sales'] ?? '—') ?></div>
                            </td>
                            <td class="cell-center">
                                <span class="badge badge-danger font-mono font-bold"><?= (int)$st['total_nota_gantung'] ?> Faktur</span>
                            </td>
                            <td class="cell-center">
                                <span class="badge badge-neutral font-mono font-bold"><?= (int)$st['usia_nota_terlama'] ?> Hari</span>
                                <div class="text-[10px] text-ink-mute mt-0.5">Nota tgl <?= date('d/m/y', strtotime($st['nota_tertua'])) ?></div>
                            </td>
                            <td class="cell-right font-mono font-black text-danger"><?= Format::rupiah((float)$st['total_piutang_berjalan']) ?></td>
                            <td class="cell-center">
                                <a href="<?= Router::url('/reguler/tagihan?q=' . urlencode($st['kode_pelanggan'])) ?>" class="btn btn-secondary btn-sm text-xs font-bold">
                                    Buka Tagihan
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 5. TAB PANE 3: OVERDUE KALENDER (TEMPO TANGGAL)                           -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'overdue'" x-cloak class="card p-0 rounded-2xl overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--color-hairline);background:rgba(220,38,38,0.03);">
            <div class="flex items-center gap-2.5">
                <i data-lucide="calendar-x" class="w-5 h-5 text-danger"></i>
                <div>
                    <h3 class="text-sm font-black text-ink">Faktur Overdue Kalender (Tempo Tanggal)</h3>
                    <p class="text-xs text-ink-mute">Tagihan kredit bertanggal jatuh tempo pasti yang telah melewati tanggal batas waktu</p>
                </div>
            </div>
            <span class="badge badge-danger"><?= count($overdueStores) ?> Faktur Menunggak</span>
        </div>

        <?php if (empty($overdueStores)): ?>
            <div class="p-8 text-center text-ink-mute text-xs">
                <i data-lucide="calendar-check" class="w-10 h-10 mx-auto mb-2 text-emerald-500"></i>
                <div class="font-bold text-sm text-ink">Semua Pembayaran Tepat Waktu</div>
                <div>Tidak ada faktur tempo tanggal yang melewati jatuh tempo kalender.</div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="data-table min-w-[750px]">
                    <thead>
                        <tr>
                            <th style="width:40px;" class="cell-center">No</th>
                            <th>No. Nota &amp; Toko</th>
                            <th>Tgl Jatuh Tempo</th>
                            <th class="cell-center">Keterlambatan</th>
                            <th class="cell-right">Sisa Tagihan</th>
                            <th>Wilayah &amp; Sales</th>
                            <th class="cell-center" style="width:110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($overdueStores as $idx => $st): ?>
                        <tr>
                            <td class="cell-center font-bold text-ink-mute"><?= $idx + 1 ?></td>
                            <td>
                                <div class="font-bold text-sm font-mono text-ink"><?= htmlspecialchars($st['nomor_nota']) ?></div>
                                <div class="text-xs text-ink-mute font-semibold"><?= htmlspecialchars($st['nama_toko']) ?> (<?= htmlspecialchars($st['kode_pelanggan']) ?>)</div>
                            </td>
                            <td class="font-mono font-bold text-danger">
                                <?= date('d/m/Y', strtotime($st['tanggal_jatuh_tempo'])) ?>
                            </td>
                            <td class="cell-center">
                                <span class="badge badge-danger font-mono font-bold">
                                    Telat <?= (int)$st['hari_terlambat'] ?> Hari
                                </span>
                            </td>
                            <td class="cell-right font-mono font-black text-danger"><?= Format::rupiah((float)$st['sisa_tagihan']) ?></td>
                            <td>
                                <div class="text-xs text-ink"><?= htmlspecialchars($st['nama_wilayah'] ?? '—') ?></div>
                                <div class="text-[11px] text-ink-mute"><?= htmlspecialchars($st['nama_sales'] ?? '—') ?></div>
                            </td>
                            <td class="cell-center">
                                <a href="<?= Router::url('/reguler/tagihan?q=' . urlencode($st['kode_pelanggan'])) ?>" class="btn btn-secondary btn-sm text-xs font-bold">
                                    Tagih Sekarang
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- 6. TAB PANE 4: TOKO PASIF BERHUTANG (>30 HARI TANPA ORDER)                -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'dormant'" x-cloak class="card p-0 rounded-2xl overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--color-hairline);background:rgba(100,116,139,0.03);">
            <div class="flex items-center gap-2.5">
                <i data-lucide="user-x" class="w-5 h-5 text-slate-500"></i>
                <div>
                    <h3 class="text-sm font-black text-ink">Toko Pasif Berhutang (&gt; 30 Hari Tanpa Order)</h3>
                    <p class="text-xs text-ink-mute">Toko yang masih memiliki saldo piutang berjalan tetapi tidak melakukan pemesanan baru selama lebih dari 30 hari</p>
                </div>
            </div>
            <span class="badge badge-neutral"><?= count($dormantStores) ?> Toko Terdeteksi</span>
        </div>

        <?php if (empty($dormantStores)): ?>
            <div class="p-8 text-center text-ink-mute text-xs">
                <i data-lucide="activity" class="w-10 h-10 mx-auto mb-2 text-emerald-500"></i>
                <div class="font-bold text-sm text-ink">Tidak Ada Toko Pasif Berhutang</div>
                <div>Seluruh toko yang memiliki piutang aktif rutin bertransaksi dalam 30 hari terakhir.</div>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto custom-scrollbar">
                <table class="data-table min-w-[750px]">
                    <thead>
                        <tr>
                            <th style="width:40px;" class="cell-center">No</th>
                            <th>Toko Mitra</th>
                            <th>Wilayah &amp; Sales</th>
                            <th class="cell-center">Order Terakhir</th>
                            <th class="cell-center">Durasi Pasif</th>
                            <th class="cell-right">Sisa Piutang</th>
                            <th class="cell-center" style="width:110px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($dormantStores as $idx => $st): ?>
                        <tr>
                            <td class="cell-center font-bold text-ink-mute"><?= $idx + 1 ?></td>
                            <td>
                                <div class="font-bold text-sm text-ink"><?= htmlspecialchars($st['nama_toko']) ?></div>
                                <div class="text-xs text-ink-mute font-mono"><?= htmlspecialchars($st['kode_pelanggan']) ?> &bull; <?= htmlspecialchars($st['nama_pemilik'] ?? '-') ?></div>
                            </td>
                            <td>
                                <div class="font-semibold text-xs text-ink"><?= htmlspecialchars($st['nama_wilayah'] ?? '—') ?></div>
                                <div class="text-[11px] text-ink-mute">Sales: <?= htmlspecialchars($st['nama_sales'] ?? '—') ?></div>
                            </td>
                            <td class="cell-center font-mono">
                                <?= !empty($st['tanggal_order_terakhir']) ? date('d/m/Y', strtotime($st['tanggal_order_terakhir'])) : 'Belum Ada' ?>
                            </td>
                            <td class="cell-center">
                                <span class="badge badge-warning font-mono font-bold">
                                    <?= (int)$st['hari_sejak_order_terakhir'] ?> Hari Lalu
                                </span>
                            </td>
                            <td class="cell-right font-mono font-black text-danger"><?= Format::rupiah((float)$st['total_piutang_berjalan']) ?></td>
                            <td class="cell-center">
                                <a href="<?= Router::url('/reguler/tagihan?q=' . urlencode($st['kode_pelanggan'])) ?>" class="btn btn-secondary btn-sm text-xs font-bold">
                                    Kunjungi &amp; Tagih
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

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

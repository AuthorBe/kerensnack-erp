<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="komisiApp()" x-init="init()" class="space-y-5 pb-20">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-amber">
                <i data-lucide="percent"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f59e0b;"></span>
                    <span><?= $isSalesLocked ? 'Modul Konsinyasi • Komisi Penjualan Saya' : 'Modul Konsinyasi • Rekap Seluruh Sales Lapangan' ?></span>
                </div>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Rekap Komisi Sales') ?></h1>
                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Perhitungan insentif komisi bulanan berdasarkan omzet laku toko binaan tetap.') ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <a href="<?= Router::url('/consignment') ?>" class="btn btn-secondary">
                <i data-lucide="arrow-left"></i>
                <span>Kembali ke Portal</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- ALERT: AKUN SALES BELUM DITAUTKAN KE MASTER KARYAWAN                      -->
    <!-- ========================================================================= -->
    <?php if ($unlinkedAccount): ?>
    <div class="card p-4 rounded-2xl flex items-start gap-3.5" style="background:rgba(239,68,68,0.05);border:1px solid rgba(239,68,68,0.25);">
        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background:rgba(239,68,68,0.12);color:#ef4444;">
            <i data-lucide="alert-triangle" class="w-5 h-5"></i>
        </div>
        <div>
            <div class="text-xs sm:text-sm font-bold text-rose-600">Akun Belum Ditautkan ke Master Karyawan</div>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                Akun pengguna Anda belum terhubung dengan data karyawan sales di Master Pengguna. Silakan hubungi Administrator atau Owner untuk menautkan akun Anda agar data komisi dapat dihitung dan ditampilkan otomatis.
            </p>
        </div>
    </div>
    <?php endif; ?>

    <!-- ========================================================================= -->
    <!-- TOP STATS CARDS (VERTICAL HIERARCHY — ZERO CLIPPING / SQUISHING)          -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <!-- Card 1: Total Omzet -->
        <div class="stat-card">
            <div class="flex items-center justify-between mb-2.5">
                <span class="stat-card-label" style="margin-bottom:0;letter-spacing:0.04em;">
                    <?= $isSalesLocked ? 'Omzet Penjualan Saya' : 'Total Omzet Laku' ?>
                </span>
                <div class="stat-card-icon" style="background:rgba(16,185,129,0.12);color:#10b981;width:38px;height:38px;border-radius:10px;">
                    <i data-lucide="trending-up" style="width:19px;height:19px;"></i>
                </div>
            </div>
            <div class="stat-card-value" style="color:#10b981;font-size:1.65rem;line-height:1.2;">
                <?= Format::rupiah((float)$grandOmzet) ?>
            </div>
            <div class="stat-card-footer" style="margin-top:12px;padding-top:10px;border-top:1px solid var(--color-hairline);">
                <i data-lucide="info" style="width:13px;height:13px;color:var(--color-ink-mute);flex-shrink:0;"></i>
                <span style="font-size:11.5px;color:var(--color-ink-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= $isSalesLocked ? 'Hasil opname toko binaan Anda' : 'Dari seluruh toko binaan aktif' ?>
                </span>
            </div>
        </div>

        <!-- Card 2: Estimasi Komisi -->
        <div class="stat-card" style="border-color:rgba(245,158,11,0.35);">
            <div class="flex items-center justify-between mb-2.5">
                <span class="stat-card-label" style="margin-bottom:0;color:#d97706;letter-spacing:0.04em;">
                    <?= $isSalesLocked ? 'Estimasi Komisi Saya' : 'Total Alokasi Komisi' ?>
                </span>
                <div class="stat-card-icon" style="background:rgba(245,158,11,0.12);color:#f59e0b;width:38px;height:38px;border-radius:10px;">
                    <i data-lucide="award" style="width:19px;height:19px;"></i>
                </div>
            </div>
            <div class="stat-card-value" style="color:#f59e0b;font-size:1.65rem;line-height:1.2;">
                <?= Format::rupiah((float)$grandKomisi) ?>
            </div>
            <div class="stat-card-footer" style="margin-top:12px;padding-top:10px;border-top:1px solid var(--color-hairline);">
                <i data-lucide="percent" style="width:13px;height:13px;color:#d97706;flex-shrink:0;"></i>
                <span style="font-size:11.5px;color:var(--color-ink-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= $isSalesLocked ? 'Berdasarkan rate komisi binaan' : 'Beban estimasi insentif sales' ?>
                </span>
            </div>
        </div>

        <!-- Card 3: Toko Binaan Terlibat -->
        <div class="stat-card">
            <div class="flex items-center justify-between mb-2.5">
                <span class="stat-card-label" style="margin-bottom:0;letter-spacing:0.04em;">
                    <?= $isSalesLocked ? 'Toko Binaan Saya' : 'Total Toko Binaan' ?>
                </span>
                <div class="stat-card-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;width:38px;height:38px;border-radius:10px;">
                    <i data-lucide="store" style="width:19px;height:19px;"></i>
                </div>
            </div>
            <div class="stat-card-value" style="color:#3b82f6;font-size:1.65rem;line-height:1.2;">
                <?= (int)$totalStoresInvolved ?> <span style="font-size:13px;font-weight:600;color:var(--color-ink-mute);">Toko</span>
            </div>
            <div class="stat-card-footer" style="margin-top:12px;padding-top:10px;border-top:1px solid var(--color-hairline);">
                <i data-lucide="check-circle-2" style="width:13px;height:13px;color:#3b82f6;flex-shrink:0;"></i>
                <span style="font-size:11.5px;color:var(--color-ink-mute);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    Terdaftar sebagai penanggung jawab
                </span>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- DEDICATED FILTER CARD (FLEKSIBEL TANGGAL & PRESETS CEPAT)                 -->
    <!-- ========================================================================= -->
    <div class="card p-4 sm:p-5 rounded-2xl space-y-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
        
        <!-- Bar Atas: Pilihan Periode Cepat (Presets) -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="padding-bottom: 16px; margin-bottom: 16px; border-bottom: 1px solid var(--color-hairline);">
            <div class="flex items-center gap-2 text-xs font-bold" style="color:var(--color-ink-secondary);">
                <i data-lucide="calendar" class="w-4 h-4 text-amber-500"></i>
                <span>PILIHAN PERIODE CEPAT:</span>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" 
                        @click="setQuickPeriod('this_month')" 
                        :class="isActivePreset('this_month') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    Bulan Ini (Default)
                </button>
                <button type="button" 
                        @click="setQuickPeriod('last_month')" 
                        :class="isActivePreset('last_month') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    Bulan Lalu
                </button>
                <button type="button" 
                        @click="setQuickPeriod('today')" 
                        :class="isActivePreset('today') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    Hari Ini
                </button>
                <button type="button" 
                        @click="setQuickPeriod('this_week')" 
                        :class="isActivePreset('this_week') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    Minggu Ini
                </button>
                <button type="button" 
                        @click="setQuickPeriod('last_30_days')" 
                        :class="isActivePreset('last_30_days') ? 'btn btn-primary' : 'btn btn-secondary'"
                        style="font-size:11.5px;padding:5px 12px;height:32px;border-radius:8px;">
                    30 Hari Terakhir
                </button>
            </div>
        </div>

        <!-- Form Kontrol: Grid Responsif 4 Kolom -->
        <form x-ref="filterForm" method="GET" action="<?= Router::url('/consignment/komisi-sales') ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 items-end">
                
                <!-- Field 1: Dari Tanggal -->
                <div>
                    <label class="form-label text-xs font-bold" style="color:var(--color-ink-secondary);margin-bottom:6px;">
                        Dari Tanggal
                    </label>
                    <input type="date" 
                           name="start_date" 
                           x-model="filterStartDate" 
                           class="form-input text-xs" 
                           style="height:38px;">
                </div>

                <!-- Field 2: Sampai Tanggal -->
                <div>
                    <label class="form-label text-xs font-bold" style="color:var(--color-ink-secondary);margin-bottom:6px;">
                        Sampai Tanggal
                    </label>
                    <input type="date" 
                           name="end_date" 
                           x-model="filterEndDate" 
                           class="form-input text-xs" 
                           style="height:38px;">
                </div>

                <!-- Field 3: Filter Sales Lapangan -->
                <div>
                    <?php if ($canViewAll): ?>
                        <label class="form-label text-xs font-bold" style="color:var(--color-ink-secondary);margin-bottom:6px;">
                            Filter Sales Lapangan
                        </label>
                        <select name="sales_id" class="form-select text-xs" style="height:38px;">
                            <option value="">-- Semua Sales Lapangan --</option>
                            <?php foreach ($salesOptions as $so): ?>
                            <option value="<?= $so['id'] ?>" <?= $selectedSalesId === $so['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($so['nama_karyawan']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <label class="form-label text-xs font-bold" style="color:var(--color-ink-secondary);margin-bottom:6px;">
                            Sales Lapangan (Terkunci)
                        </label>
                        <div class="flex items-center justify-between px-3 rounded-lg border bg-slate-500/5 text-xs font-bold" 
                             style="height:38px;border-color:var(--color-hairline);color:var(--color-ink);" 
                             title="Filter otomatis terkunci ke akun sales Anda">
                            <div class="flex items-center gap-1.5 truncate">
                                <i data-lucide="lock" style="width:13px;height:13px;color:#f59e0b;flex-shrink:0;"></i>
                                <span class="truncate"><?= htmlspecialchars($currentSalesName) ?></span>
                            </div>
                            <span class="badge badge-warning text-[9px] py-0.5 px-1.5 flex-shrink-0">Terkunci</span>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Field 4: Tombol Aksi Terapkan & Reset -->
                <div class="flex items-center gap-2">
                    <button type="submit" 
                            class="btn btn-primary flex-1 text-xs font-bold" 
                            style="height:38px;background:#f59e0b;border-color:#f59e0b;color:#090d16;">
                        <i data-lucide="filter" style="width:14px;height:14px;"></i>
                        <span>Terapkan</span>
                    </button>

                    <?php if (!empty($selectedSalesId) && $canViewAll): ?>
                        <a href="<?= Router::url('/consignment/komisi-sales?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)) ?>" 
                           class="btn btn-secondary text-xs" 
                           style="height:38px;padding:0 12px;" 
                           title="Reset Filter Sales">
                            <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i>
                            <span>Reset</span>
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </form>

        <!-- Bar Keterangan Rentang Aktif -->
        <div class="flex flex-wrap items-center justify-between gap-2 text-xs" style="padding-top:14px; margin-top:16px; border-top:1px solid var(--color-hairline); color:var(--color-ink-mute);">
            <div class="flex items-center gap-1.5">
                <i data-lucide="clock" class="w-3.5 h-3.5 text-amber-500 flex-shrink-0"></i>
                <span>Periode Transaksi: <strong style="color:var(--color-ink);"><?= date('d/m/Y', strtotime($startDate)) ?></strong> s/d <strong style="color:var(--color-ink);"><?= date('d/m/Y', strtotime($endDate)) ?></strong></span>
            </div>
            <div style="font-size:11.5px;">
                Ditemukan <strong style="color:var(--color-ink);"><?= count($commissions) ?></strong> Sales Lapangan
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TABEL REKAPITULASI KOMISI SALES                                           -->
    <!-- ========================================================================= -->
    <div class="card p-0 rounded-2xl overflow-hidden" style="border:1px solid var(--color-hairline);">
        <div class="table-responsive">
            <table class="data-table" style="min-width: 860px;">
                <thead>
                    <tr>
                        <th style="min-width:240px;">Sales Lapangan</th>
                        <th class="cell-center cell-nowrap" style="width:140px; min-width:130px;">Toko Binaan</th>
                        <th class="cell-right cell-nowrap" style="min-width:180px;">Total Omzet Laku (Rp)</th>
                        <th class="cell-center cell-nowrap" style="width:120px; min-width:110px;">Rate Komisi</th>
                        <th class="cell-right cell-nowrap" style="min-width:180px;">Estimasi Komisi (Rp)</th>
                        <th class="cell-center cell-nowrap" style="width:140px; min-width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commissions)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center;padding:56px 20px;color:var(--color-ink-mute);">
                                <div class="w-12 h-12 rounded-2xl mx-auto mb-3 flex items-center justify-center" style="background:rgba(245,158,11,0.12);color:#f59e0b;">
                                    <i data-lucide="percent" style="width:24px;height:24px;"></i>
                                </div>
                                <div style="font-weight:700;color:var(--color-ink);font-size:14px;">Belum Ada Transaksi Penjualan Konsinyasi</div>
                                <div style="font-size:12px;margin-top:4px;max-width:440px;margin-left:auto;margin-right:auto;line-height:1.5;">
                                    Belum ada nota kunjungan atau opname fisik rak toko binaan yang mencatat barang laku pada rentang tanggal terpilih.
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($commissions as $c): ?>
                        <tr>
                            <!-- Sales Lapangan Info -->
                            <td>
                                <div style="display:flex;align-items:center;gap:12px;">
                                    <div style="width:38px;height:38px;border-radius:50%;background:rgba(245,158,11,0.15);color:#d97706;font-weight:800;font-size:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(245,158,11,0.3);">
                                        <?= strtoupper(substr($c['nama_karyawan'], 0, 2)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;color:var(--color-ink);font-size:13.5px;"><?= htmlspecialchars($c['nama_karyawan']) ?></div>
                                        <div style="display:flex;align-items:center;gap:6px;margin-top:2px;">
                                            <span class="badge" style="background:rgba(245,158,11,0.1);color:#d97706;font-size:9.5px;padding:1px 6px;">
                                                <?= htmlspecialchars(strtoupper($c['posisi'] ?? 'SALES')) ?>
                                            </span>
                                            <?php if (!empty($c['nomor_telepon'])): ?>
                                                <span class="font-mono text-[11px] flex items-center gap-1" style="color:var(--color-ink-mute);">
                                                    <i data-lucide="phone" style="width:10px;height:10px;"></i>
                                                    <span><?= htmlspecialchars($c['nomor_telepon']) ?></span>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Jumlah Toko Binaan Tetap -->
                            <td class="cell-center cell-nowrap">
                                <?php if ((int)$c['total_toko_assigned'] > 0): ?>
                                    <span class="badge badge-success" style="font-weight:700;font-size:11px;padding:3.5px 9px;">
                                        <i data-lucide="store" style="width:12px;height:12px;"></i>
                                        <span><?= (int)$c['total_toko_assigned'] ?> Toko</span>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-warning" style="font-size:10.5px;padding:2px 8px;">
                                        0 Toko
                                    </span>
                                <?php endif; ?>
                            </td>

                            <!-- Total Omzet Laku -->
                            <td class="cell-right cell-nowrap cell-currency font-black" style="font-size:13px;color:var(--color-ink);">
                                <?= Format::rupiah((float)$c['total_omzet']) ?>
                            </td>

                            <!-- Rate Komisi -->
                            <td class="cell-center cell-nowrap">
                                <span class="badge badge-info" style="font-weight:700;font-size:11px;padding:3.5px 9px;">
                                    <?= number_format((float)$c['persentase_komisi'], 2) ?> %
                                </span>
                            </td>

                            <!-- Estimasi Nominal Komisi -->
                            <td class="cell-right cell-nowrap cell-currency font-black" style="color:#d97706;font-size:13.5px;">
                                <?= Format::rupiah((float)$c['nominal_komisi']) ?>
                            </td>

                            <!-- Tombol Aksi: Rincian Toko -->
                            <td class="cell-center cell-nowrap">
                                <button type="button" 
                                        @click="openBreakdownModal(<?= htmlspecialchars(json_encode($c)) ?>)"
                                        class="btn btn-secondary btn-sm"
                                        style="height:32px;font-size:11.5px;padding:0 10px;font-weight:600;"
                                        title="Lihat rincian toko binaan">
                                    <i data-lucide="list-checks" style="width:13px;height:13px;color:#d97706;"></i>
                                    <span>Rincian Toko</span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($commissions)): ?>
                <tfoot>
                    <tr class="font-bold text-xs" style="background:var(--color-canvas);border-top:2px solid var(--color-hairline);">
                        <td colspan="2" style="padding:13px 18px;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.05em;">
                            TOTAL KESELURUHAN:
                        </td>
                        <td class="cell-right cell-currency font-black" style="padding:13px 18px;color:var(--color-ink);font-size:13.5px;">
                            <?= Format::rupiah((float)$grandOmzet) ?>
                        </td>
                        <td class="cell-center" style="padding:13px 18px;color:var(--color-ink-mute);">—</td>
                        <td class="cell-right cell-currency font-black text-amber-500" style="padding:13px 18px;font-size:14.5px;">
                            <?= Format::rupiah((float)$grandKomisi) ?>
                        </td>
                        <td class="cell-center" style="padding:13px 18px;color:var(--color-ink-mute);">—</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: POP-UP RINCIAN OMZET PER TOKO BINAAN (TELEPORTED)                  -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="showBreakdownModal" 
             x-cloak 
             class="modal-backdrop"
             @click.self="showBreakdownModal = false"
             @keydown.escape.window="showBreakdownModal = false">
            
            <div class="modal-box" style="max-width: 760px; width: 100%; padding: 24px; border-radius: 20px;">
                
                <!-- Modal Header -->
                <div class="modal-header flex items-center justify-between" style="padding-bottom: 14px; margin-bottom: 16px; border-bottom: 1px solid var(--color-hairline);">
                    <div class="flex items-center gap-3">
                        <div style="width: 42px; height: 42px; border-radius: 12px; background: rgba(245,158,11,0.12); color: #f59e0b; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <i data-lucide="store" style="width: 22px; height: 22px;"></i>
                        </div>
                        <div>
                            <div class="modal-title" style="font-size: 16px; font-weight: 800; color: var(--color-ink);">
                                Rincian Omzet Toko Binaan
                            </div>
                            <div style="font-size: 12px; color: var(--color-ink-mute); margin-top: 2px;">
                                Sales: <strong style="color:var(--color-ink);" x-text="activeSales.nama_karyawan"></strong> &bull;
                                Rate: <span class="font-bold text-amber-500" x-text="Number(activeSales.persentase_komisi || 2.5).toFixed(2) + '%'"></span> &bull;
                                Periode: <span class="font-medium" style="color:var(--color-ink);" x-text="formatDateIndo(filterStartDate) + ' s/d ' + formatDateIndo(filterEndDate)"></span>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="showBreakdownModal = false" class="btn btn-ghost btn-sm" style="padding: 6px; border-radius: 10px;" title="Tutup Modal">
                        <i data-lucide="x" style="width: 18px; height: 18px;"></i>
                    </button>
                </div>

                <!-- Search Input Bar & Counter -->
                <div class="flex items-center justify-between gap-3" style="margin-bottom: 14px;">
                    <div class="form-input-icon flex-1">
                        <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                        <input type="text" 
                               x-model="storeSearchQuery" 
                               placeholder="Cari nama toko, kode pelanggan, alamat, atau wilayah..." 
                               class="form-input text-xs" 
                               style="height: 38px;">
                    </div>
                    <div class="text-xs text-slate-500 whitespace-nowrap hidden sm:block">
                        Menampilkan <strong style="color:var(--color-ink);" x-text="filteredStores().length"></strong> Toko
                    </div>
                </div>

                <!-- Tabel Rincian Toko Binaan (Sticky Header & Scrollable) -->
                <div class="table-scroll custom-scrollbar" style="max-height: 340px; border: 1px solid var(--color-hairline); border-radius: 12px; background: var(--color-canvas); overflow-y: auto;">
                    <table class="table" style="margin: 0; width: 100%; font-size: 12.5px;">
                        <thead style="background: var(--color-canvas-soft); position: sticky; top: 0; z-index: 2;">
                            <tr style="border-bottom: 1px solid var(--color-hairline);">
                                <th style="padding: 11px 14px; min-width: 220px;">Toko & Alamat</th>
                                <th class="cell-center" style="width: 110px; padding: 11px 12px;">Wilayah</th>
                                <th class="cell-center" style="width: 130px; padding: 11px 12px;">Kunjungan</th>
                                <th class="cell-right cell-currency" style="width: 140px; padding: 11px 14px;">Omzet Laku</th>
                                <th class="cell-right cell-currency" style="width: 130px; padding: 11px 14px;">Komisi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="st in filteredStores()" :key="st.store_id">
                                <tr style="border-bottom: 1px solid var(--color-hairline);">
                                    <!-- Toko & Alamat -->
                                    <td style="padding: 12px 14px;">
                                        <div class="flex items-center gap-2">
                                            <span class="badge badge-mono text-[10px]" style="padding: 1px 5px;" x-text="st.kode_pelanggan"></span>
                                            <span class="font-bold text-xs" style="color:var(--color-ink);" x-text="st.nama_toko"></span>
                                        </div>
                                        <div class="text-[11px] mt-1 text-slate-400 flex items-center gap-1 truncate" style="max-width: 240px;">
                                            <svg class="flex-shrink-0" style="width: 11px; height: 11px; color: var(--color-ink-mute);" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                                                <circle cx="12" cy="10" r="3"/>
                                            </svg>
                                            <span class="truncate" x-text="st.alamat_lengkap || 'Alamat tidak terdata'"></span>
                                        </div>
                                    </td>

                                    <!-- Wilayah -->
                                    <td class="cell-center" style="padding: 12px;">
                                        <span class="badge badge-muted text-[10px]" x-text="st.nama_wilayah || 'Tanpa Wilayah'"></span>
                                    </td>

                                    <!-- Kunjungan -->
                                    <td class="cell-center" style="padding: 12px;">
                                        <template x-if="Number(st.total_kunjungan) > 0">
                                            <div>
                                                <span class="badge badge-success text-[10px] font-bold" x-text="st.total_kunjungan + 'x Kunjungan'"></span>
                                                <div class="text-[10.5px] text-slate-400 mt-0.5" x-text="'Tgl: ' + formatDateIndo(st.terakhir_kunjungan)"></div>
                                            </div>
                                        </template>
                                        <template x-if="!st.total_kunjungan || Number(st.total_kunjungan) === 0">
                                            <span class="badge badge-warning text-[10px]">0 Kunjungan</span>
                                        </template>
                                    </td>

                                    <!-- Omzet Laku -->
                                    <td class="cell-right cell-currency font-black text-xs" style="padding: 12px 14px; color:var(--color-ink);" x-text="formatRupiah(st.omzet_toko)"></td>

                                    <!-- Komisi Sales -->
                                    <td class="cell-right cell-currency font-black text-xs text-amber-500" style="padding: 12px 14px;" x-text="formatRupiah(calcStoreCommission(st.omzet_toko))"></td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <template x-if="filteredStores().length === 0">
                                <tr>
                                    <td colspan="5" class="py-10 text-center text-slate-400" style="padding: 40px 20px;">
                                        <div class="w-10 h-10 rounded-xl mx-auto mb-2.5 flex items-center justify-center" style="background:var(--color-canvas-soft);">
                                            <svg style="width: 20px; height: 20px; opacity: 0.5;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/>
                                                <path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/>
                                                <path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/>
                                                <path d="M2 7h20"/>
                                            </svg>
                                        </div>
                                        <div class="font-bold text-xs" style="color:var(--color-ink);">Tidak Ada Toko Binaan</div>
                                        <div class="text-[11px] mt-0.5">Tidak ada toko yang cocok dengan pencarian.</div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <!-- Modal Action Footer (Ringkasan Komprehensif) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3" style="padding-top: 16px; margin-top: 16px; border-top: 1px solid var(--color-hairline);">
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span style="color:var(--color-ink-mute);">Total Toko:</span>
                            <strong style="color:var(--color-ink);" x-text="filteredStores().length + ' Toko'"></strong>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span style="color:var(--color-ink-mute);">Total Omzet:</span>
                            <strong style="color:var(--color-ink);" class="font-mono font-bold" x-text="formatRupiah(activeSales.total_omzet)"></strong>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span style="color:var(--color-ink-mute);">Total Komisi:</span>
                            <strong class="font-mono font-bold text-amber-500" x-text="formatRupiah(activeSales.nominal_komisi)"></strong>
                        </div>
                    </div>
                    <button type="button" @click="showBreakdownModal = false" class="btn btn-secondary" style="height: 36px; font-size: 12.5px; padding: 0 16px;">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </template>

</div>

<script>
function komisiApp() {
    return {
        showBreakdownModal: false,
        activeSales: {},
        activeStores: [],
        storeSearchQuery: '',
        filterStartDate: '<?= htmlspecialchars($startDate) ?>',
        filterEndDate: '<?= htmlspecialchars($endDate) ?>',

        // Data Breakdown dari PHP
        allBreakdowns: <?= json_encode($storeBreakdown) ?>,

        init() {
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        // Helper Format YYYY-MM-DD
        formatYMD(d) {
            const pad = (n) => String(n).padStart(2, '0');
            return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
        },

        // Pilihan Periode Cepat
        setQuickPeriod(type) {
            const today = new Date();
            if (type === 'this_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                this.filterStartDate = this.formatYMD(firstDay);
                this.filterEndDate = this.formatYMD(lastDay);
            } else if (type === 'last_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
                this.filterStartDate = this.formatYMD(firstDay);
                this.filterEndDate = this.formatYMD(lastDay);
            } else if (type === 'today') {
                this.filterStartDate = this.formatYMD(today);
                this.filterEndDate = this.formatYMD(today);
            } else if (type === 'this_week') {
                const curr = new Date();
                const day = curr.getDay();
                const diff = curr.getDate() - day + (day === 0 ? -6 : 1); // Senin
                const monday = new Date(curr.setDate(diff));
                const sunday = new Date(curr.setDate(diff + 6));
                this.filterStartDate = this.formatYMD(monday);
                this.filterEndDate = this.formatYMD(sunday);
            } else if (type === 'last_30_days') {
                const past = new Date();
                past.setDate(today.getDate() - 30);
                this.filterStartDate = this.formatYMD(past);
                this.filterEndDate = this.formatYMD(today);
            }

            this.$nextTick(() => {
                this.$refs.filterForm.submit();
            });
        },

        // Deteksi tombol preset mana yang sedang aktif
        isActivePreset(type) {
            const today = new Date();
            if (type === 'this_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                return this.filterStartDate === this.formatYMD(firstDay) && this.filterEndDate === this.formatYMD(lastDay);
            } else if (type === 'last_month') {
                const firstDay = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lastDay = new Date(today.getFullYear(), today.getMonth(), 0);
                return this.filterStartDate === this.formatYMD(firstDay) && this.filterEndDate === this.formatYMD(lastDay);
            } else if (type === 'today') {
                return this.filterStartDate === this.formatYMD(today) && this.filterEndDate === this.formatYMD(today);
            } else if (type === 'last_30_days') {
                const past = new Date();
                past.setDate(today.getDate() - 30);
                return this.filterStartDate === this.formatYMD(past) && this.filterEndDate === this.formatYMD(today);
            }
            return false;
        },

        openBreakdownModal(sales) {
            this.activeSales = sales;
            this.activeStores = this.allBreakdowns[sales.sales_id] || [];
            this.storeSearchQuery = '';
            this.showBreakdownModal = true;
            this.$nextTick(() => {
                if (window.lucide) window.lucide.createIcons();
            });
        },

        filteredStores() {
            if (!this.storeSearchQuery) {
                return this.activeStores;
            }
            const q = this.storeSearchQuery.toLowerCase().trim();
            return this.activeStores.filter(st => {
                const combined = (st.nama_toko + ' ' + st.kode_pelanggan + ' ' + (st.alamat_lengkap || '') + ' ' + (st.nama_wilayah || '')).toLowerCase();
                return combined.includes(q);
            });
        },

        calcStoreCommission(omzet) {
            const rate = parseFloat(this.activeSales.persentase_komisi || 2.5);
            return (parseFloat(omzet || 0) * rate) / 100.0;
        },

        formatRupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        },

        formatDateIndo(dateStr) {
            if (!dateStr) return '-';
            const parts = dateStr.split('-');
            if (parts.length === 3) {
                return parts[2] + '/' + parts[1] + '/' + parts[0];
            }
            return dateStr;
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div x-data="cashReportsApp()" x-init="init()" class="space-y-4 sm:space-y-5">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (Clean Standard Responsive Layout)                         -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-violet">
                <i data-lucide="trending-up"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#8b5cf6;"></span>
                    <span>Laporan Keuangan &amp; Akuntansi</span>
                </div>
                <h1 class="page-title">
                    <?= htmlspecialchars($pageTitle ?? 'Laporan Arus Kas & Valuasi Aset') ?>
                </h1>
                <p class="page-subtitle">
                    <?= htmlspecialchars($pageSubtitle ?? 'Analisis Cash Flow Masuk-Keluar, Saldo Awal/Akhir, Serta Valuasi Persediaan HPP & Piutang') ?>
                </p>
            </div>
        </div>

        <!-- Quick Top Actions: Lihat Transaksi & Export Excel -->
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <a href="<?= Router::url('/cash/transactions') ?>" class="btn btn-secondary btn-sm flex-1 sm:flex-initial" style="font-weight:600; display:inline-flex; align-items:center; justify-content:center; gap:6px; height:36px;">
                <i data-lucide="arrow-left-right" style="width:14px; height:14px;"></i>
                <span>Transaksi Kas</span>
            </a>
            <a href="<?= Router::url('/cash/reports/export-excel?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . '&account_id=' . urlencode($accountId ?? 'all')) ?>" 
               class="btn btn-secondary btn-sm flex-1 sm:flex-initial" 
               style="height:36px; background:#10b981; color:#fff; border-color:#059669; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px;"
               title="Unduh Rekapitulasi Laporan Arus Kas ke Excel">
                <i data-lucide="file-spreadsheet" style="width:15px;height:15px;"></i>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. DEDICATED FILTER CARD (100% RESPONSIVE: PRESET CEPAT + FILTER RENTANG)  -->
    <!-- ========================================================================= -->
    <div class="card p-3 sm:p-4 rounded-xl" style="background:var(--color-canvas); border:1px solid var(--color-hairline); box-shadow:var(--shadow-1);">
        <form method="GET" action="<?= Router::url('/cash/reports') ?>" style="display:flex; flex-direction:column; gap:12px;">
            
            <!-- Baris Preset Periode Cepat -->
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; padding-bottom:10px; border-bottom:1px solid var(--color-hairline);">
                <div style="display:flex; align-items:center; gap:6px; font-size:12px; font-weight:700; color:var(--color-ink);">
                    <i data-lucide="calendar" style="width:14px; height:14px; color:#8b5cf6;"></i>
                    <span>Filter Periode Laporan:</span>
                </div>
                <!-- Preset Buttons: 4 kolom di HP, horizontal flex di desktop -->
                <div class="grid grid-cols-4 sm:flex gap-1.5 w-full sm:w-auto">
                    <button type="button" @click="setPreset('today')" class="btn btn-ghost btn-sm" style="padding:4px 8px; font-size:11px; font-weight:600; border-radius:6px; border:1px solid var(--color-hairline); justify-content:center;">
                        Hari Ini
                    </button>
                    <button type="button" @click="setPreset('this_month')" class="btn btn-ghost btn-sm" style="padding:4px 8px; font-size:11px; font-weight:600; border-radius:6px; border:1px solid var(--color-hairline); justify-content:center;">
                        Bulan Ini
                    </button>
                    <button type="button" @click="setPreset('last_month')" class="btn btn-ghost btn-sm" style="padding:4px 8px; font-size:11px; font-weight:600; border-radius:6px; border:1px solid var(--color-hairline); justify-content:center;">
                        Bulan Lalu
                    </button>
                    <button type="button" @click="setPreset('this_year')" class="btn btn-ghost btn-sm" style="padding:4px 8px; font-size:11px; font-weight:600; border-radius:6px; border:1px solid var(--color-hairline); justify-content:center;">
                        Tahun Ini
                    </button>
                </div>
            </div>

            <!-- Form Input Fields (2 Kolom di HP, 4 Kolom di Desktop) -->
            <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-3 items-end">
                <div>
                    <label class="form-label" style="font-size:11px; font-weight:700; margin-bottom:4px; display:block;">Tanggal Mulai</label>
                    <input type="date" name="start_date" x-model="filterStart" class="form-input" style="height:38px; font-size:12px; width:100%;">
                </div>

                <div>
                    <label class="form-label" style="font-size:11px; font-weight:700; margin-bottom:4px; display:block;">Tanggal Selesai</label>
                    <input type="date" name="end_date" x-model="filterEnd" class="form-input" style="height:38px; font-size:12px; width:100%;">
                </div>

                <div class="col-span-2 sm:col-span-1 lg:col-span-1">
                    <label class="form-label" style="font-size:11px; font-weight:700; margin-bottom:4px; display:block;">Pilihan Akun Kas</label>
                    <select name="account_id" class="form-input" style="height:38px; font-size:12.5px; width:100%;">
                        <option value="all" <?= ($accountId ?? 'all') === 'all' ? 'selected' : '' ?>>Semua Akun (Konsolidasi)</option>
                        <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>" <?= ($accountId ?? '') === $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nama_akun']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-span-2 sm:col-span-1 lg:col-span-1">
                    <button type="submit" class="btn btn-primary" style="height:38px; width:100%; font-weight:700; display:inline-flex; align-items:center; justify-content:center; gap:6px;">
                        <i data-lucide="filter" style="width:14px; height:14px;"></i>
                        <span>Tampilkan Laporan</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. 5 KEY CARDS: STANDAR CASH FLOW STATEMENT (100% RESPONSIVE)             -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-2 sm:gap-3">
        
        <!-- 1. SALDO AWAL PERIODE -->
        <div class="card p-3 sm:p-3.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3px solid var(--color-ink-mute);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:4px;">
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--color-ink-mute);">1. Saldo Awal</span>
                <i data-lucide="calendar" style="width:13px;height:13px;color:var(--color-ink-mute);flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(12px, 3.2vw, 15.5px);font-weight:900;color:var(--color-ink);line-height:1.2;word-break:break-all;">
                <?= Format::rupiah($beginningBalance) ?>
            </div>
            <div style="font-size:9.5px;color:var(--color-ink-mute);margin-top:2px;">Per <?= date('d M Y', strtotime($startDate)) ?></div>
        </div>

        <!-- 2. TOTAL KAS MASUK -->
        <div class="card p-3 sm:p-3.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3px solid #10b981;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:4px;">
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:#059669;">2. Kas Masuk (+)</span>
                <i data-lucide="arrow-down-left" style="width:13px;height:13px;color:#10b981;flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(12px, 3.2vw, 15.5px);font-weight:900;color:#10b981;line-height:1.2;word-break:break-all;">
                + <?= Format::rupiah($totalIn) ?>
            </div>
            <div style="font-size:9.5px;color:var(--color-ink-mute);margin-top:2px;">Penjualan &amp; Inflow</div>
        </div>

        <!-- 3. TOTAL KAS KELUAR -->
        <div class="card p-3 sm:p-3.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3px solid #ef4444;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:4px;">
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:#dc2626;">3. Kas Keluar (-)</span>
                <i data-lucide="arrow-up-right" style="width:13px;height:13px;color:#ef4444;flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(12px, 3.2vw, 15.5px);font-weight:900;color:#ef4444;line-height:1.2;word-break:break-all;">
                - <?= Format::rupiah($totalOut) ?>
            </div>
            <div style="font-size:9.5px;color:var(--color-ink-mute);margin-top:2px;">Beban &amp; Belanja</div>
        </div>

        <!-- 4. NET ARUS KAS BERSIH -->
        <div class="card p-3 sm:p-3.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-left:3px solid <?= $netCashFlow >= 0 ? '#3b82f6' : '#ef4444' ?>;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:4px;">
                <span style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--color-ink-mute);">4. Net Cash Flow</span>
                <i data-lucide="scale" style="width:13px;height:13px;color:<?= $netCashFlow >= 0 ? '#3b82f6' : '#ef4444' ?>;flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(12px, 3.2vw, 15.5px);font-weight:900;color:<?= $netCashFlow >= 0 ? '#3b82f6' : '#ef4444' ?>;line-height:1.2;word-break:break-all;">
                <?= ($netCashFlow >= 0 ? '+ ' : '- ') . Format::rupiah(abs($netCashFlow)) ?>
            </div>
            <div style="font-size:9.5px;color:var(--color-ink-mute);margin-top:2px;">Surplus / Defisit</div>
        </div>

        <!-- 5. SALDO AKHIR PERIODE (Highlighted & full width di HP jika sendirian) -->
        <div class="card p-3 sm:p-3.5 col-span-2 sm:col-span-1" style="background:rgba(139,92,246,0.04);border:1px solid rgba(139,92,246,0.35);border-left:3px solid #8b5cf6;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:4px;margin-bottom:4px;">
                <span style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.03em;color:#8b5cf6;">5. Saldo Akhir</span>
                <i data-lucide="wallet-cards" style="width:13px;height:13px;color:#8b5cf6;flex-shrink:0;"></i>
            </div>
            <div class="font-mono" style="font-size:clamp(13px, 3.5vw, 16px);font-weight:900;color:#8b5cf6;line-height:1.2;word-break:break-all;">
                <?= Format::rupiah($endingBalance) ?>
            </div>
            <div style="font-size:9.5px;color:var(--color-ink-mute);margin-top:2px;">Per <?= date('d M Y', strtotime($endDate)) ?></div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. 2 KOLOM: BREAKDOWN BEBAN & NERACA VALUASI ASET BISNIS                  -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
        
        <!-- KOLOM KIRI: BREAKDOWN BEBAN BIAYA OPERASIONAL -->
        <div class="card p-0 overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="padding:14px 16px; border-bottom:1px solid var(--color-hairline); background-color:var(--color-canvas); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="pie-chart" style="width:16px;height:16px;"></i>
                    </div>
                    <div style="min-width:0;">
                        <h3 style="font-size:13px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Breakdown Beban Biaya</h3>
                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Komposisi pos pengeluaran kas periode ini</div>
                    </div>
                </div>
                <span class="badge badge-secondary" style="font-size:11px;flex-shrink:0;"><?= count($expenseBreakdown) ?> Kategori</span>
            </div>

            <!-- TAMPILAN DESKTOP/TABLET: DATA TABLE -->
            <div class="hidden sm:block overflow-x-auto custom-scrollbar">
                <table class="data-table" style="min-width:100%;">
                    <thead>
                        <tr>
                            <th>Kategori Beban</th>
                            <th class="cell-center" style="width:75px;">Jumlah</th>
                            <th class="cell-right" style="width:130px;">Total (Rp)</th>
                            <th style="width:110px;">Porsi (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenseBreakdown)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;padding:28px;color:var(--color-ink-mute);font-size:12px;">
                                Belum ada pengeluaran beban biaya pada periode ini
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($expenseBreakdown as $eb): 
                            $percent = $totalOut > 0 ? round(((float)$eb['total_nominal'] / $totalOut) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$eb['kategori']))) ?></strong>
                            </td>
                            <td class="cell-center">
                                <span class="badge badge-mono" style="font-size:10.5px;"><?= $eb['total_transaksi'] ?>x</span>
                            </td>
                            <td class="cell-right cell-currency" style="font-weight:700;color:#ef4444;font-size:12px;">
                                <?= Format::rupiah((float)$eb['total_nominal']) ?>
                            </td>
                            <td>
                                <div style="display:flex; align-items:center; gap:6px;">
                                    <div style="flex:1; background:var(--color-hairline); height:5px; border-radius:3px; overflow:hidden;">
                                        <div style="background:#ef4444; width:<?= min(100, $percent) ?>%; height:100%; border-radius:3px;"></div>
                                    </div>
                                    <span class="font-mono" style="font-size:10.5px; font-weight:700; min-width:30px; text-align:right;">
                                        <?= $percent ?>%
                                    </span>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- TAMPILAN MOBILE: TOUCH-FRIENDLY PROGRESS LIST (LEGA & TIDAK NEMPEL) -->
            <div class="block sm:hidden" style="display:flex; flex-direction:column;">
                <?php if (empty($expenseBreakdown)): ?>
                <div style="padding:28px; text-align:center; color:var(--color-ink-mute); font-size:12px;">
                    Belum ada pengeluaran beban biaya pada periode ini
                </div>
                <?php else: ?>
                <?php foreach ($expenseBreakdown as $eb): 
                    $percent = $totalOut > 0 ? round(((float)$eb['total_nominal'] / $totalOut) * 100, 1) : 0;
                ?>
                <div style="padding:12px 16px; border-bottom:1px solid var(--color-hairline); display:flex; flex-direction:column; gap:8px;">
                    <!-- Baris 1: Nama Kategori & Nominal -->
                    <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                        <span style="font-weight:700; font-size:12.5px; color:var(--color-ink);">
                            <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$eb['kategori']))) ?>
                        </span>
                        <span class="font-mono" style="font-weight:800; font-size:13px; color:#ef4444; flex-shrink:0;">
                            <?= Format::rupiah((float)$eb['total_nominal']) ?>
                        </span>
                    </div>
                    <!-- Baris 2: Progress Bar dengan Porsi % dan Frekuensi Trx -->
                    <div style="display:flex; align-items:center; gap:10px;">
                        <div style="flex:1; background:var(--color-canvas-soft); border:1px solid var(--color-hairline); height:8px; border-radius:4px; overflow:hidden;">
                            <div style="background:#ef4444; width:<?= min(100, $percent) ?>%; height:100%; border-radius:4px;"></div>
                        </div>
                        <div style="display:flex; align-items:center; gap:6px; flex-shrink:0;">
                            <span class="font-mono" style="font-size:11px; font-weight:700; color:var(--color-ink-mute); min-width:34px; text-align:right;">
                                <?= $percent ?>%
                            </span>
                            <span class="badge badge-mono" style="font-size:10px; padding:1px 6px;">
                                <?= $eb['total_transaksi'] ?>x
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- KOLOM KANAN: NERACA LIKUIDITAS & VALUASI ASET BISNIS TERKINI -->
        <div class="card p-0 overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="padding:14px 16px; border-bottom:1px solid var(--color-hairline); background-color:var(--color-canvas); display:flex; align-items:center; justify-content:space-between; gap:12px;">
                <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(139,92,246,0.1);color:#8b5cf6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="landmark" style="width:16px;height:16px;"></i>
                    </div>
                    <div style="min-width:0;">
                        <h3 style="font-size:13px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Neraca Aset Bisnis Terkini</h3>
                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Posisi kekayaan usaha (Kas + HPP + Piutang)</div>
                    </div>
                </div>
            </div>

            <div class="space-y-2.5" style="padding:14px 16px;">
                
                <!-- 1. KAS & SALDO BANK CAIR -->
                <div style="padding:12px 14px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;gap:12px;background:var(--color-canvas);">
                    <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="wallet" style="width:16px;height:16px;"></i>
                        </div>
                        <div style="min-width:0;">
                            <div style="font-weight:700;font-size:12.5px;color:var(--color-ink);line-height:1.3;">1. Total Kas &amp; Bank</div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Uang cair di kasir &amp; bank</div>
                        </div>
                    </div>
                    <div style="font-weight:800;font-family:var(--font-mono);font-size:13.5px;color:#10b981;flex-shrink:0;text-align:right;">
                        <?= Format::rupiah($liquidCashTotal) ?>
                    </div>
                </div>

                <!-- 2. KAS PERSEDIAAN BARANG (HPP) + TOMBOL POPUP DI BAWAH (ANTI TABRAKAN) -->
                <div style="padding:12px 14px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:flex-start;gap:12px;background:var(--color-canvas);">
                    <div style="display:flex;align-items:flex-start;gap:10px;min-width:0;flex:1;">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                            <i data-lucide="boxes" style="width:16px;height:16px;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div style="font-weight:700;font-size:12.5px;color:var(--color-ink);line-height:1.3;">2. Kas Persediaan</div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Nilai modal HPP persediaan gudang</div>
                            <div style="margin-top:6px;">
                                <button type="button" @click="showHppModal = true" class="btn btn-ghost btn-sm" style="padding:2px 8px;height:22px;border-radius:6px;font-size:10px;font-weight:700;color:#3b82f6;background:rgba(59,130,246,0.1);border:1px solid rgba(59,130,246,0.25);display:inline-flex;align-items:center;gap:4px;" title="Lihat Rincian HPP Bahan Mentah, Kemasan & Barang Jadi">
                                    <i data-lucide="info" style="width:11px;height:11px;"></i>
                                    <span>Rincian HPP</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div style="font-weight:800;font-family:var(--font-mono);font-size:13.5px;color:#3b82f6;flex-shrink:0;text-align:right;margin-top:2px;">
                        <?= Format::rupiah($inventoryTotal) ?>
                    </div>
                </div>

                <!-- 3. PIUTANG BERJALAN TOKO MITRA -->
                <div style="padding:12px 14px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;gap:12px;background:var(--color-canvas);">
                    <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,0.12);color:#f59e0b;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="receipt" style="width:16px;height:16px;"></i>
                        </div>
                        <div style="min-width:0;">
                            <div style="font-weight:700;font-size:12.5px;color:var(--color-ink);line-height:1.3;">3. Piutang Berjalan</div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Tagihan tempo &amp; konsinyasi toko</div>
                        </div>
                    </div>
                    <div style="font-weight:800;font-family:var(--font-mono);font-size:13.5px;color:#f59e0b;flex-shrink:0;text-align:right;">
                        <?= Format::rupiah($receivablesTotal) ?>
                    </div>
                </div>

                <!-- 4. TOTAL ESTIMASI KEKAYAAN BERSIH (RESPONSIVE WRAP SAFE) -->
                <div style="padding:14px 16px;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.25);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;margin-top:8px;">
                    <div style="min-width:0;">
                        <div style="font-weight:800;font-size:13px;color:var(--color-ink);">Total Estimasi Kekayaan Usaha:</div>
                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">Kas Cair + HPP Persediaan + Piutang</div>
                    </div>
                    <div style="font-size:clamp(14.5px, 3.5vw, 18px);font-weight:900;font-family:var(--font-mono);color:#8b5cf6;flex-shrink:0;">
                        <?= Format::rupiah($totalWealth) ?>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- 5. TABEL REKAPITULASI ARUS KAS HARIAN (DAILY CASH FLOW SUMMARY)           -->
    <!-- ZERO DUPLICATION: MURNI REKAP PER TANGGAL, BUKAN LIST MUTASI MENTAH      -->
    <!-- ========================================================================= -->
    <div class="card p-0 overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
        <div style="padding:14px 16px; border-bottom:1px solid var(--color-hairline); background-color:var(--color-canvas); display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div style="display:flex;align-items:center;gap:10px;min-width:0;flex:1;">
                <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(59,130,246,0.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="calendar-range" style="width:16px;height:16px;"></i>
                </div>
                <div style="min-width:0;">
                    <h3 style="font-size:13px;font-weight:800;color:var(--color-ink);margin:0;line-height:1.3;">Rekapitulasi Arus Kas Harian</h3>
                    <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;line-height:1.3;">Ringkasan mutasi kas dan penutupan saldo per tanggal</div>
                </div>
            </div>
            <span class="badge badge-secondary" style="font-size:11px;flex-shrink:0;"><?= count($dailySummary) ?> Hari Aktif</span>
        </div>

        <!-- TAMPILAN DESKTOP/TABLET (>=768px): TABEL DATA LENGKAP -->
        <div class="hidden md:block overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 760px;">
                <thead>
                    <tr>
                        <th style="width:120px;" class="cell-nowrap">Tanggal</th>
                        <th class="cell-center" style="width:120px;">Frekuensi</th>
                        <th class="cell-right" style="width:150px;">Kas Masuk (Rp)</th>
                        <th class="cell-right" style="width:150px;">Kas Keluar (Rp)</th>
                        <th class="cell-right" style="width:150px;">Net Harian (Rp)</th>
                        <th class="cell-right" style="width:160px;">Saldo Akhir Hari</th>
                        <th class="cell-center" style="width:90px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dailySummary)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                            <i data-lucide="calendar-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.4;"></i>
                            <div style="font-weight:700;font-size:13.5px;color:var(--color-ink);">Tidak ada aktivitas arus kas pada periode ini</div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <!-- Baris Pembuka: Saldo Awal Periode -->
                    <tr style="background:var(--color-canvas-soft); font-weight:700;">
                        <td class="font-mono cell-nowrap" style="padding:13px 18px; font-size:12px; color:var(--color-ink-mute);">
                            <?= date('d/m/Y', strtotime($startDate)) ?>
                        </td>
                        <td class="cell-center" style="padding:13px 18px; color:var(--color-ink-mute);">
                            <span class="badge badge-mono">Saldo Awal</span>
                        </td>
                        <td class="cell-right cell-currency" style="padding:13px 18px; color:var(--color-ink-mute);">-</td>
                        <td class="cell-right cell-currency" style="padding:13px 18px; color:var(--color-ink-mute);">-</td>
                        <td class="cell-right cell-currency" style="padding:13px 18px; color:var(--color-ink-mute);">-</td>
                        <td class="cell-right cell-currency" style="padding:13px 18px; font-weight:800; color:var(--color-ink);">
                            <?= Format::rupiah($beginningBalance) ?>
                        </td>
                        <td class="cell-center" style="padding:13px 18px; color:var(--color-ink-mute); font-size:11px;">Awal</td>
                    </tr>

                    <?php foreach ($dailySummary as $ds): 
                        $isNetPos = $ds['net_harian'] >= 0;
                    ?>
                    <tr>
                        <td class="cell-nowrap font-mono" style="padding:13px 18px; font-size:12px; font-weight:700;">
                            <?= Format::tanggal($ds['tanggal'], false) ?>
                        </td>
                        <td class="cell-center" style="padding:13px 18px;">
                            <span class="badge badge-mono" style="font-size:11px;"><?= $ds['total_transaksi'] ?> Transaksi</span>
                        </td>
                        <td class="cell-right cell-currency" style="padding:13px 18px; font-weight:700; color:#10b981;">
                            <?= $ds['kas_masuk'] > 0 ? '+ ' . Format::rupiah($ds['kas_masuk']) : '-' ?>
                        </td>
                        <td class="cell-right cell-currency" style="padding:13px 18px; font-weight:700; color:#ef4444;">
                            <?= $ds['kas_keluar'] > 0 ? '- ' . Format::rupiah($ds['kas_keluar']) : '-' ?>
                        </td>
                        <td class="cell-right cell-currency" style="padding:13px 18px; font-weight:800; color:<?= $isNetPos ? '#10b981' : '#ef4444' ?>;">
                            <?= ($isNetPos ? '+ ' : '- ') . Format::rupiah(abs($ds['net_harian'])) ?>
                        </td>
                        <td class="cell-right cell-currency" style="padding:13px 18px; font-weight:800; font-family:var(--font-mono); color:var(--color-ink);">
                            <?= Format::rupiah($ds['saldo_akhir_hari']) ?>
                        </td>
                        <td class="cell-center" style="padding:13px 18px;">
                            <a href="<?= Router::url('/cash/transactions?start_date=' . urlencode($ds['tanggal']) . '&end_date=' . urlencode($ds['tanggal']) . ($accountId !== 'all' ? '&account_id=' . urlencode($accountId) : '')) ?>" 
                               class="btn btn-ghost btn-sm" 
                               style="padding:3px 8px; font-size:11px; display:inline-flex; align-items:center; gap:3px;" 
                               title="Buka daftar transaksi rincian di halaman Transaksi Kas">
                                <span>Detail</span>
                                <i data-lucide="arrow-up-right" style="width:11px; height:11px;"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <?php if (!empty($dailySummary)): ?>
                <tfoot>
                    <tr style="background:var(--color-canvas-soft); font-weight:800; border-top:2px solid var(--color-hairline);">
                        <td colspan="2" style="padding:14px 18px; font-size:12px; text-transform:uppercase; vertical-align:middle; letter-spacing:0.04em;">TOTAL MUTASI PERIODE</td>
                        <td class="cell-right cell-currency" style="padding:14px 18px; color:#10b981; font-size:13.5px; vertical-align:middle;">+ <?= Format::rupiah($totalIn) ?></td>
                        <td class="cell-right cell-currency" style="padding:14px 18px; color:#ef4444; font-size:13.5px; vertical-align:middle;">- <?= Format::rupiah($totalOut) ?></td>
                        <td class="cell-right cell-currency" style="padding:14px 18px; color:<?= $netCashFlow >= 0 ? '#10b981' : '#ef4444' ?>; font-size:13.5px; vertical-align:middle;">
                            <?= ($netCashFlow >= 0 ? '+ ' : '- ') . Format::rupiah(abs($netCashFlow)) ?>
                        </td>
                        <td class="cell-right cell-currency" style="padding:14px 18px; color:#8b5cf6; font-size:14px; vertical-align:middle;"><?= Format::rupiah($endingBalance) ?></td>
                        <td class="cell-center" style="padding:14px 18px; font-size:11px; color:var(--color-ink-mute); vertical-align:middle;">Akhir</td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>
        </div>

        <!-- TAMPILAN MOBILE (<768px): TOUCH-FRIENDLY DAILY FEED (ZERO HORIZONTAL SCROLL & LEGA) -->
        <div class="block md:hidden">
            <?php if (empty($dailySummary)): ?>
            <div style="padding:32px; text-align:center; color:var(--color-ink-mute);">
                <i data-lucide="calendar-x" style="width:32px;height:32px;margin:0 auto 8px auto;opacity:0.4;"></i>
                <div style="font-weight:700;font-size:13px;color:var(--color-ink);">Tidak ada aktivitas arus kas pada periode ini</div>
            </div>
            <?php else: ?>

            <!-- Saldo Awal Header Card -->
            <div style="padding:12px 16px;background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);display:flex;justify-content:space-between;align-items:center;gap:10px;">
                <div style="display:flex;align-items:center;gap:8px;font-size:12px;font-weight:700;color:var(--color-ink-mute);">
                    <i data-lucide="calendar" style="width:14px;height:14px;color:#8b5cf6;"></i>
                    <span>Saldo Awal (<?= date('d/m/Y', strtotime($startDate)) ?>)</span>
                </div>
                <div class="font-mono" style="font-size:13.5px;font-weight:800;color:var(--color-ink);">
                    <?= Format::rupiah($beginningBalance) ?>
                </div>
            </div>

            <!-- List Per Hari -->
            <div style="display:flex; flex-direction:column;">
                <?php foreach ($dailySummary as $ds): 
                    $isNetPos = $ds['net_harian'] >= 0;
                ?>
                <div style="padding:14px 16px;border-bottom:1px solid var(--color-hairline);display:flex;flex-direction:column;gap:10px;">
                    <!-- Header Hari: Tanggal, Badge Trx, dan Tombol Detail -->
                    <div style="display:flex;justify-content:space-between;align-items:center;gap:8px;">
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="font-mono" style="font-size:12.5px;font-weight:800;color:var(--color-ink);">
                                <?= Format::tanggal($ds['tanggal'], false) ?>
                            </span>
                            <span class="badge badge-mono" style="font-size:10.5px;padding:2px 6px;">
                                <?= $ds['total_transaksi'] ?> Trx
                            </span>
                        </div>
                        <a href="<?= Router::url('/cash/transactions?start_date=' . urlencode($ds['tanggal']) . '&end_date=' . urlencode($ds['tanggal']) . ($accountId !== 'all' ? '&account_id=' . urlencode($accountId) : '')) ?>" 
                           class="btn btn-ghost btn-sm" 
                           style="padding:3px 8px; font-size:11px; height:26px; color:#8b5cf6; display:inline-flex; align-items:center; gap:4px; font-weight:700; background:rgba(139,92,246,0.06); border-radius:6px;" 
                           title="Lihat transaksi rinci pada tanggal ini">
                            <span>Detail</span>
                            <i data-lucide="arrow-up-right" style="width:12px; height:12px;"></i>
                        </a>
                    </div>

                    <!-- 2x2 Metric Grid dengan Padding dan Gap Nyaman -->
                    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px 14px;padding:10px 12px;border-radius:var(--rounded-md);background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div>
                            <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;color:var(--color-ink-mute);letter-spacing:0.03em;margin-bottom:2px;">Kas Masuk</div>
                            <div class="font-mono" style="font-size:12px;font-weight:700;color:#10b981;">
                                <?= $ds['kas_masuk'] > 0 ? '+ ' . Format::rupiah($ds['kas_masuk']) : '-' ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;color:var(--color-ink-mute);letter-spacing:0.03em;margin-bottom:2px;">Kas Keluar</div>
                            <div class="font-mono" style="font-size:12px;font-weight:700;color:#ef4444;">
                                <?= $ds['kas_keluar'] > 0 ? '- ' . Format::rupiah($ds['kas_keluar']) : '-' ?>
                            </div>
                        </div>
                        <div style="padding-top:6px;border-top:1px dashed var(--color-hairline);">
                            <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;color:var(--color-ink-mute);letter-spacing:0.03em;margin-bottom:2px;">Net Harian</div>
                            <div class="font-mono" style="font-size:12px;font-weight:800;color:<?= $isNetPos ? '#10b981' : '#ef4444' ?>;">
                                <?= ($isNetPos ? '+ ' : '- ') . Format::rupiah(abs($ds['net_harian'])) ?>
                            </div>
                        </div>
                        <div style="padding-top:6px;border-top:1px dashed var(--color-hairline);">
                            <div style="font-size:9.5px;font-weight:700;text-transform:uppercase;color:var(--color-ink-mute);letter-spacing:0.03em;margin-bottom:2px;">Saldo Penutupan</div>
                            <div class="font-mono" style="font-size:12px;font-weight:800;color:var(--color-ink);">
                                <?= Format::rupiah($ds['saldo_akhir_hari']) ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Total Mutasi Periode Footer Card -->
            <div style="padding:14px 16px;background:var(--color-canvas-soft);border-top:2px solid var(--color-hairline);display:flex;flex-direction:column;gap:10px;">
                <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--color-ink);letter-spacing:0.04em;">
                    Total Mutasi Periode Laporan
                </div>
                <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:10px 14px;font-size:11px;padding:10px 12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                    <div>
                        <span style="font-size:9.5px;color:var(--color-ink-mute);display:block;margin-bottom:2px;">Total Masuk:</span>
                        <span class="font-mono" style="font-weight:700;color:#10b981;font-size:12px;">+ <?= Format::rupiah($totalIn) ?></span>
                    </div>
                    <div>
                        <span style="font-size:9.5px;color:var(--color-ink-mute);display:block;margin-bottom:2px;">Total Keluar:</span>
                        <span class="font-mono" style="font-weight:700;color:#ef4444;font-size:12px;">- <?= Format::rupiah($totalOut) ?></span>
                    </div>
                    <div style="padding-top:6px;border-top:1px dashed var(--color-hairline);">
                        <span style="font-size:9.5px;color:var(--color-ink-mute);display:block;margin-bottom:2px;">Net Cash Flow:</span>
                        <span class="font-mono" style="font-weight:800;color:<?= $netCashFlow >= 0 ? '#10b981' : '#ef4444' ?>;font-size:12px;">
                            <?= ($netCashFlow >= 0 ? '+ ' : '- ') . Format::rupiah(abs($netCashFlow)) ?>
                        </span>
                    </div>
                    <div style="padding-top:6px;border-top:1px dashed var(--color-hairline);">
                        <span style="font-size:9.5px;color:var(--color-ink-mute);display:block;margin-bottom:2px;">Saldo Akhir:</span>
                        <span class="font-mono" style="font-weight:900;color:#8b5cf6;font-size:13px;"><?= Format::rupiah($endingBalance) ?></span>
                    </div>
                </div>
            </div>

            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 6. MODAL RINCIAN VALUASI KAS PERSEDIAAN (HPP POPUP)                       -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showHppModal" x-cloak class="modal-backdrop" @click.self="showHppModal = false">
        <div class="modal-box" style="max-width:540px;width:92vw;padding:20px;" @click.stop>
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(59,130,246,0.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="boxes" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title" style="font-size:14px;font-weight:800;">Rincian Valuasi Kas Persediaan</div>
                        <div style="font-size:11px;color:var(--color-ink-mute);">Kalkulasi Berdasarkan Harga Pokok Pembelian (HPP) Murni</div>
                    </div>
                </div>
                <button type="button" @click="showHppModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:12px;margin-top:12px;">
                <!-- Formula Card -->
                <div style="padding:10px 12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);font-size:11px;line-height:1.5;">
                    💡 <strong>Rumus Akuntansi:</strong><br>
                    <code>Kas Persediaan = ∑ (Stok Fisik di Gudang × HPP Beli)</code><br>
                    <span style="color:var(--color-ink-mute);">Mencerminkan nilai uang modal usaha yang saat ini berwujud persediaan fisik di gudang.</span>
                </div>

                <!-- 3 Category Breakdown -->
                <div class="space-y-2">
                    <!-- 1. Bahan Mentah -->
                    <div style="padding:10px 12px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;gap:8px;background:var(--color-canvas);">
                        <div style="min-width:0;flex:1;">
                            <div style="font-weight:700;font-size:12px;color:var(--color-ink);">1. Bahan Mentah Curah (Bal / Kg)</div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">
                                <?= $rawValuation['count'] ?> SKU Bahan • Total <?= number_format($rawValuation['total_qty'], 2, ',', '.') ?> Bal/Kg
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-weight:800;font-family:var(--font-mono);font-size:13px;color:#3b82f6;">
                                <?= Format::rupiah($rawValuation['subtotal']) ?>
                            </div>
                            <div style="font-size:9.5px;color:var(--color-ink-mute);">HPP Beli Supplier</div>
                        </div>
                    </div>

                    <!-- 2. Bahan Kemasan -->
                    <div style="padding:10px 12px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;gap:8px;background:var(--color-canvas);">
                        <div style="min-width:0;flex:1;">
                            <div style="font-weight:700;font-size:12px;color:var(--color-ink);">2. Bahan Kemasan (Plastik &amp; Label)</div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">
                                <?= $packValuation['count'] ?> SKU Kemasan • Total <?= number_format($packValuation['total_qty'], 0, ',', '.') ?> Lembar
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-weight:800;font-family:var(--font-mono);font-size:13px;color:#3b82f6;">
                                <?= Format::rupiah($packValuation['subtotal']) ?>
                            </div>
                            <div style="font-size:9.5px;color:var(--color-ink-mute);">HPP Kemasan</div>
                        </div>
                    </div>

                    <!-- 3. Barang Jadi -->
                    <div style="padding:10px 12px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;gap:8px;background:var(--color-canvas);">
                        <div style="min-width:0;flex:1;">
                            <div style="font-weight:700;font-size:12px;color:var(--color-ink);">3. Barang Jadi Siap Jual (Bungkus)</div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;">
                                <?= $fgValuation['count'] ?> SKU Produk • Total <?= number_format($fgValuation['total_qty'], 0, ',', '.') ?> Bungkus
                            </div>
                        </div>
                        <div style="text-align:right;flex-shrink:0;">
                            <div style="font-weight:800;font-family:var(--font-mono);font-size:13px;color:#3b82f6;">
                                <?= Format::rupiah($fgValuation['subtotal']) ?>
                            </div>
                            <div style="font-size:9.5px;color:var(--color-ink-mute);">HPP Produksi</div>
                        </div>
                    </div>
                </div>

                <!-- Total Summary -->
                <div style="padding:12px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:6px;">
                    <div style="font-weight:800;font-size:12.5px;color:var(--color-ink);">Total Valuasi Kas Persediaan:</div>
                    <div style="font-size:15px;font-weight:900;font-family:var(--font-mono);color:#3b82f6;flex-shrink:0;">
                        <?= Format::rupiah($inventoryTotal) ?>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;margin-top:4px;">
                    <button type="button" @click="showHppModal = false" class="btn btn-secondary btn-sm" style="min-width:80px;justify-content:center;">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    </template>

</div>

<script>
function cashReportsApp() {
    return {
        showHppModal: false,
        filterStart: '<?= htmlspecialchars($startDate) ?>',
        filterEnd: '<?= htmlspecialchars($endDate) ?>',

        init() {
            this.$nextTick(() => {
                if (window.lucide) {
                    lucide.createIcons();
                }
            });
        },

        setPreset(preset) {
            const today = new Date();
            const y = today.getFullYear();
            const m = String(today.getMonth() + 1).padStart(2, '0');
            const d = String(today.getDate()).padStart(2, '0');

            if (preset === 'today') {
                this.filterStart = `${y}-${m}-${d}`;
                this.filterEnd = `${y}-${m}-${d}`;
            } else if (preset === 'this_month') {
                this.filterStart = `${y}-${m}-01`;
                this.filterEnd = `${y}-${m}-${d}`;
            } else if (preset === 'last_month') {
                const lastMonthDate = new Date(today.getFullYear(), today.getMonth() - 1, 1);
                const lmY = lastMonthDate.getFullYear();
                const lmM = String(lastMonthDate.getMonth() + 1).padStart(2, '0');
                const lastDay = new Date(today.getFullYear(), today.getMonth(), 0).getDate();
                this.filterStart = `${lmY}-${lmM}-01`;
                this.filterEnd = `${lmY}-${lmM}-${String(lastDay).padStart(2, '0')}`;
            } else if (preset === 'this_year') {
                this.filterStart = `${y}-01-01`;
                this.filterEnd = `${y}-${m}-${d}`;
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

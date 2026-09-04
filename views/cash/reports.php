<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-violet">
                <i data-lucide="trending-up"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#8b5cf6;"></span>
                    <span>Laporan Keuangan</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Laporan Arus Kas &amp; Valuasi Aset' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Analisis Cash Flow Masuk vs Keluar serta Ringkasan Kekayaan Usaha' ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center;">
            <form method="GET" action="<?= Router::url('/cash/reports') ?>" class="flex items-center gap-2">
                <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-input" style="height:38px;font-size:12.5px;">
                <span style="color:var(--color-ink-mute);font-size:12px;">s/d</span>
                <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-input" style="height:38px;font-size:12.5px;">
                <button type="submit" class="btn btn-primary" style="height:38px;font-weight:700;">
                    <i data-lucide="filter"></i>
                    <span>Tampilkan</span>
                </button>
            </form>
            <a href="<?= Router::url('/cash/reports/export-excel?start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate)) ?>" class="btn btn-secondary" style="height:38px; background:#10b981; color:#fff; border-color:#059669; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="file-spreadsheet"></i>
                <span>Export Excel</span>
            </a>
        </div>
    </div>

    <!-- 3 KEY CARDS FOR CASH FLOW IN THE PERIOD -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;border-left:4px solid #10b981;">
            <div class="stat-card-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
                <i data-lucide="arrow-down-left"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Pemasukan Kas</div>
                <div class="stat-card-value" style="color:#10b981;font-size:18px;">
                    <?= Format::rupiah($totalIn) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Penjualan POS &amp; Kas Masuk</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;border-left:4px solid #ef4444;">
            <div class="stat-card-icon" style="background:rgba(239,68,68,0.1);color:#ef4444;">
                <i data-lucide="arrow-up-right"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Pengeluaran Beban</div>
                <div class="stat-card-value" style="color:#ef4444;font-size:18px;">
                    <?= Format::rupiah($totalOut) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Beban Operasional &amp; Belanja</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;border-left:4px solid <?= $netCashFlow >= 0 ? '#3b82f6' : '#ef4444' ?>;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="wallet-cards"></i>
            </div>
            <div>
                <div class="stat-card-label">Net Surplus / Defisit Kas</div>
                <div class="stat-card-value" style="color:<?= $netCashFlow >= 0 ? '#3b82f6' : '#ef4444' ?>;font-size:18px;">
                    <?= ($netCashFlow >= 0 ? '+ ' : '- ') . Format::rupiah(abs($netCashFlow)) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Total Masuk - Total Keluar</div>
            </div>
        </div>
    </div>

    <!-- 2 COLUMNS: EXPENSE BREAKDOWN & ASSET BALANCE SHEET -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        
        <!-- KOLOM KIRI: BREAKDOWN BIAYA OPERASIONAL -->
        <div class="card p-0 overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:28px;height:28px;border-radius:var(--rounded-md);background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="pie-chart" style="width:15px;height:15px;"></i>
                    </div>
                    <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0;">Rincian Pengeluaran Beban Biaya</h3>
                </div>
                <span class="badge badge-secondary"><?= count($expenseBreakdown) ?> Kategori</span>
            </div>

            <div class="overflow-x-auto custom-scrollbar">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kategori Beban</th>
                            <th class="cell-center" style="width:90px;">Transaksi</th>
                            <th class="cell-right" style="width:140px;">Total (Rp)</th>
                            <th class="cell-right" style="width:80px;">Porsi (%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenseBreakdown)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center;padding:32px;color:var(--color-ink-mute);">
                                Belum ada pengeluaran beban pada periode ini
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($expenseBreakdown as $eb): 
                            $percent = $totalOut > 0 ? round(((float)$eb['total_nominal'] / $totalOut) * 100, 1) : 0;
                        ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($eb['kategori']) ?></strong>
                            </td>
                            <td class="cell-center">
                                <span class="badge badge-mono"><?= $eb['total_transaksi'] ?>x</span>
                            </td>
                            <td class="cell-right cell-currency" style="font-weight:700;color:#ef4444;">
                                <?= Format::rupiah((float)$eb['total_nominal']) ?>
                            </td>
                            <td class="cell-right font-mono" style="font-weight:600;font-size:12px;">
                                <?= $percent ?>%
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- KOLOM KANAN: NERACA KEKAYAAN & LIKUIDITAS USAHA -->
        <div class="card p-0 overflow-hidden">
            <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:28px;height:28px;border-radius:var(--rounded-md);background:rgba(139,92,246,0.1);color:#8b5cf6;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="landmark" style="width:15px;height:15px;"></i>
                    </div>
                    <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);margin:0;">Ringkasan Neraca Aset Bisnis Terkini</h3>
                </div>
            </div>

            <div class="p-5 space-y-3">
                
                <div style="padding:12px 14px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i data-lucide="wallet" style="width:18px;height:18px;color:#10b981;"></i>
                        <div>
                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);">1. Total Kas &amp; Saldo Bank</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Uang cair di kasir, kas kecil &amp; bank</div>
                        </div>
                    </div>
                    <div style="font-weight:800;font-family:var(--font-mono);font-size:14px;color:#10b981;">
                        <?= Format::rupiah($liquidCashTotal) ?>
                    </div>
                </div>

                <div style="padding:12px 14px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i data-lucide="boxes" style="width:18px;height:18px;color:#3b82f6;"></i>
                        <div>
                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);">2. Kas Persediaan Barang (HPP)</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Nilai modal bahan mentah, kemasan &amp; barang jadi</div>
                        </div>
                    </div>
                    <div style="font-weight:800;font-family:var(--font-mono);font-size:14px;color:#3b82f6;">
                        <?= Format::rupiah($inventoryTotal) ?>
                    </div>
                </div>

                <div style="padding:12px 14px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <i data-lucide="receipt" style="width:18px;height:18px;color:#f59e0b;"></i>
                        <div>
                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);">3. Total Piutang Toko Berjalan</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Tagihan tempo &amp; konsinyasi di toko mitra</div>
                        </div>
                    </div>
                    <div style="font-weight:800;font-family:var(--font-mono);font-size:14px;color:#f59e0b;">
                        <?= Format::rupiah($receivablesTotal) ?>
                    </div>
                </div>

                <!-- GRAND TOTAL KEKAYAAN -->
                <div style="padding:14px 16px;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.25);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                    <div style="font-weight:800;font-size:13.5px;color:var(--color-ink);">Total Estimasi Kekayaan Bersih:</div>
                    <div style="font-size:20px;font-weight:900;font-family:var(--font-mono);color:#8b5cf6;">
                        <?= Format::rupiah($totalWealth) ?>
                    </div>
                </div>

            </div>
        </div>

    </div>

    <!-- DETAIL ARUS KAS TABEL LEDGER -->
    <div class="card p-0 overflow-hidden">
        <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div>
                <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);margin:0;">Buku Besar Arus Kas (Ledger Periode)</h3>
                <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Urutan kronologis seluruh uang masuk dan uang keluar</div>
            </div>
            <span class="badge badge-secondary"><?= count($transactions) ?> Baris Transaksi</span>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 860px;">
                <thead>
                    <tr>
                        <th style="width:110px; min-width:95px;" class="cell-nowrap">Tanggal</th>
                        <th style="min-width:140px;">Akun Kas</th>
                        <th class="cell-nowrap" style="width:120px; min-width:110px;">Jenis</th>
                        <th style="min-width:120px;">Kategori</th>
                        <th style="min-width:180px;">Keterangan</th>
                        <th class="cell-right cell-nowrap" style="width:140px; min-width:120px;">Nominal (Rp)</th>
                        <th class="cell-right cell-nowrap" style="width:140px; min-width:120px;">Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                            Tidak ada transaksi arus kas pada periode ini
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                    <tr>
                        <td class="cell-nowrap font-mono" style="font-size:12px;">
                            <?= Format::tanggal($t['tanggal_transaksi'], false) ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($t['nama_akun']) ?></strong>
                        </td>
                        <td>
                            <?php if ($t['jenis_kas'] === 'masuk' || $t['jenis_kas'] === 'transfer_masuk'): ?>
                                <span class="badge badge-success">Masuk</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Keluar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-secondary"><?= htmlspecialchars($t['kategori']) ?></span>
                        </td>
                        <td style="font-size:12.5px;color:var(--color-ink);">
                            <?= htmlspecialchars($t['keterangan']) ?>
                        </td>
                        <td class="cell-right cell-currency" style="font-weight:800;color:<?= ($t['jenis_kas'] === 'masuk' || $t['jenis_kas'] === 'transfer_masuk') ? '#10b981' : '#ef4444' ?>;">
                            <?= ($t['jenis_kas'] === 'masuk' || $t['jenis_kas'] === 'transfer_masuk' ? '+ ' : '- ') . Format::rupiah((float)$t['nominal']) ?>
                        </td>
                        <td class="cell-right cell-currency" style="font-weight:600;">
                            <?= Format::rupiah((float)$t['saldo_berjalan']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

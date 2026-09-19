<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="cashTransactionsApp()" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-rose">
                <i data-lucide="arrow-left-right"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#ef4444;"></span>
                    <span>Arus Kas Harian &amp; Mutasi</span>
                </div>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Transaksi Kas & Transfer Dana') ?></h1>
                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Catat Pemasukan Kas, Beban Operasional & Mutasi Dana Antar Rekening') ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <?php if (Auth::can('cash.inflow')): ?>
            <button type="button" @click="openInflowModal()" class="btn btn-success" style="background:#059669; color:#fff; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-down-left" style="width:16px; height:16px;"></i>
                <span>Kas Masuk</span>
            </button>
            <?php endif; ?>
            <?php if (Auth::can('cash.outflow')): ?>
            <button type="button" @click="openOutflowModal()" class="btn btn-danger" style="background:#ef4444; color:#fff; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-up-right" style="width:16px; height:16px;"></i>
                <span>Kas Keluar (Beban)</span>
            </button>
            <?php endif; ?>
            <?php if (Auth::can('cash.transfer')): ?>
            <button type="button" @click="openTransferModal()" class="btn btn-primary" style="background:#3b82f6; color:#fff; font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-left-right" style="width:16px; height:16px;"></i>
                <span>Transfer Antar Kas</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- REKAP SUMMARY CARDS (4 KARTU MUTASI PERIODE)                              -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
        
        <!-- 1. TOTAL KAS MASUK -->
        <div class="stat-card" style="display:flex;align-items:center;gap:12px;border-left:4px solid #10b981;background:var(--color-canvas);padding:14px;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div class="stat-card-icon" style="background:rgba(16,185,129,0.12);color:#10b981;width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-lucide="arrow-down-left" style="width:18px;height:18px;"></i>
            </div>
            <div style="min-width:0;">
                <div class="stat-card-label" style="font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Kas Masuk Periode</div>
                <div class="stat-card-value" style="color:#10b981;font-size:17px;font-weight:900;font-family:var(--font-mono);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= Format::rupiah($summary['total_inflow']) ?>
                </div>
                <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Penjualan &amp; Inflow Modal</div>
            </div>
        </div>

        <!-- 2. TOTAL KAS KELUAR -->
        <div class="stat-card" style="display:flex;align-items:center;gap:12px;border-left:4px solid #ef4444;background:var(--color-canvas);padding:14px;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div class="stat-card-icon" style="background:rgba(239,68,68,0.12);color:#ef4444;width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-lucide="arrow-up-right" style="width:18px;height:18px;"></i>
            </div>
            <div style="min-width:0;">
                <div class="stat-card-label" style="font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Kas Keluar (Beban)</div>
                <div class="stat-card-value" style="color:#ef4444;font-size:17px;font-weight:900;font-family:var(--font-mono);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= Format::rupiah($summary['total_outflow']) ?>
                </div>
                <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Biaya Operasional &amp; Belanja</div>
            </div>
        </div>

        <!-- 3. TOTAL TRANSFER ANTAR KAS -->
        <div class="stat-card" style="display:flex;align-items:center;gap:12px;border-left:4px solid #8b5cf6;background:var(--color-canvas);padding:14px;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div class="stat-card-icon" style="background:rgba(139,92,246,0.12);color:#8b5cf6;width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-lucide="arrow-left-right" style="width:18px;height:18px;"></i>
            </div>
            <div style="min-width:0;">
                <div class="stat-card-label" style="font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Mutasi Transfer Dana</div>
                <div class="stat-card-value" style="color:#8b5cf6;font-size:17px;font-weight:900;font-family:var(--font-mono);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= Format::rupiah($summary['total_transfer']) ?>
                </div>
                <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Setor Bank / Petty Cash</div>
            </div>
        </div>

        <!-- 4. NET MUTASI ARUS KAS RIIL -->
        <div class="stat-card" style="display:flex;align-items:center;gap:12px;border-left:4px solid <?= $summary['net'] >= 0 ? '#3b82f6' : '#ef4444' ?>;background:var(--color-canvas);padding:14px;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.12);color:#3b82f6;width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i data-lucide="scale" style="width:18px;height:18px;"></i>
            </div>
            <div style="min-width:0;">
                <div class="stat-card-label" style="font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Net Mutasi Bersih</div>
                <div class="stat-card-value" style="color:<?= $summary['net'] >= 0 ? '#3b82f6' : '#ef4444' ?>;font-size:17px;font-weight:900;font-family:var(--font-mono);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                    <?= ($summary['net'] >= 0 ? '+ ' : '- ') . Format::rupiah(abs($summary['net'])) ?>
                </div>
                <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Selisih Masuk - Keluar</div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- MAIN DATA CARD                                                            -->
    <!-- ========================================================================= -->
    <div class="card p-0 overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
        
        <!-- FILTER BAR -->
        <div class="p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <form method="GET" action="<?= Router::url('/cash/transactions') ?>" class="flex flex-wrap items-center gap-2.5">
                
                <!-- Date Range -->
                <div class="flex items-center gap-1.5">
                    <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>" class="form-input" style="height:36px;font-size:12.5px;" title="Tanggal Mulai">
                    <span style="color:var(--color-ink-mute);font-size:12px;">s/d</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>" class="form-input" style="height:36px;font-size:12.5px;" title="Tanggal Selesai">
                </div>

                <!-- Account Filter -->
                <select name="account_id" class="form-input" style="height:36px;font-size:12.5px;max-width:170px;">
                    <option value="all">Semua Akun Kas</option>
                    <?php foreach ($accounts as $a): ?>
                    <option value="<?= $a['id'] ?>" <?= $filters['account_id'] === $a['id'] ? 'selected' : '' ?>><?= htmlspecialchars($a['nama_akun']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Type Filter -->
                <select name="type" class="form-input" style="height:36px;font-size:12.5px;max-width:140px;">
                    <option value="all" <?= $filters['type'] === 'all' ? 'selected' : '' ?>>Semua Tipe</option>
                    <option value="masuk" <?= $filters['type'] === 'masuk' ? 'selected' : '' ?>>Kas Masuk</option>
                    <option value="keluar" <?= $filters['type'] === 'keluar' ? 'selected' : '' ?>>Kas Keluar</option>
                    <option value="transfer" <?= $filters['type'] === 'transfer' ? 'selected' : '' ?>>Transfer Dana</option>
                </select>

                <!-- Category Filter -->
                <select name="category" class="form-input" style="height:36px;font-size:12.5px;max-width:160px;">
                    <option value="all">Semua Kategori</option>
                    <option value="penjualan" <?= $filters['category'] === 'penjualan' ? 'selected' : '' ?>>Penjualan</option>
                    <option value="modal_awal" <?= $filters['category'] === 'modal_awal' ? 'selected' : '' ?>>Modal Awal</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat['nama_kategori']) ?>" <?= $filters['category'] === $cat['nama_kategori'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Search Keyword Input -->
                <div style="position:relative; flex:1; min-width:180px;">
                    <input type="text" name="keyword" value="<?= htmlspecialchars($filters['keyword'] ?? '') ?>" class="form-input" style="height:36px;font-size:12.5px;padding-left:30px;width:100%;" placeholder="Cari No. Bukti / Keterangan...">
                    <i data-lucide="search" style="position:absolute;left:9px;top:10px;width:14px;height:14px;color:var(--color-ink-mute);pointer-events:none;"></i>
                </div>

                <!-- Action Buttons: Filter & Export -->
                <div class="flex items-center gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm" style="height:36px;font-weight:700;">
                        <i data-lucide="filter" style="width:14px;height:14px;"></i>
                        <span>Filter</span>
                    </button>
                    <a href="<?= Router::url('/cash/transactions/export-excel?' . http_build_query($filters)) ?>" class="btn btn-secondary btn-sm" style="height:36px; background:#10b981; color:#fff; border-color:#059669; font-weight:700; display:inline-flex; align-items:center; gap:5px;" title="Unduh data transaksi kas yang difilter ke Excel">
                        <i data-lucide="file-spreadsheet" style="width:14px;height:14px;"></i>
                        <span>Excel</span>
                    </a>
                </div>
            </form>
        </div>

        <!-- TABLE TRANSAKSI KAS LENGKAP -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="width:100px;" class="cell-nowrap">Tanggal</th>
                        <th style="width:130px;" class="cell-nowrap">No Bukti</th>
                        <th style="min-width:140px;">Akun Kas / Bank</th>
                        <th class="cell-nowrap" style="width:130px;">Jenis</th>
                        <th style="min-width:130px;">Kategori</th>
                        <th style="min-width:200px;">Keterangan &amp; Referensi</th>
                        <th class="cell-right cell-nowrap" style="width:140px;">Nominal (Rp)</th>
                        <th class="cell-right cell-nowrap" style="width:140px;">Saldo Berjalan</th>
                        <th style="width:120px;" class="cell-nowrap">Dicatat Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="9" style="text-align:center;padding:40px 16px;color:var(--color-ink-mute);">
                            <i data-lucide="receipt" style="width:40px;height:40px;margin:0 auto 10px auto;opacity:0.4;"></i>
                            <div style="font-weight:700;font-size:14px;color:var(--color-ink);">Tidak ada transaksi kas yang sesuai filter</div>
                            <div style="font-size:12px;margin-top:2px;">Coba ubah rentang tanggal atau kriteria pencarian Anda</div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($transactions as $t): 
                        $isMasuk = in_array($t['jenis_kas'], ['masuk', 'transfer_masuk']);
                        $isTransfer = in_array($t['jenis_kas'], ['transfer_masuk', 'transfer_keluar']);
                        
                        $badgeClass = match($t['jenis_kas']) {
                            'masuk' => 'badge-success',
                            'keluar' => 'badge-danger',
                            'transfer_masuk' => 'badge-info',
                            'transfer_keluar' => 'badge-warning',
                            default => 'badge-secondary'
                        };

                        $jenisLabel = match($t['jenis_kas']) {
                            'masuk' => 'Kas Masuk',
                            'keluar' => 'Kas Keluar',
                            'transfer_masuk' => 'Transfer Masuk',
                            'transfer_keluar' => 'Transfer Keluar',
                            default => ucfirst($t['jenis_kas'])
                        };
                    ?>
                    <tr>
                        <td class="cell-nowrap font-mono" style="font-size:12px;">
                            <?= Format::tanggal($t['tanggal_transaksi'], false) ?>
                        </td>
                        <td class="cell-nowrap font-mono" style="font-size:12px; font-weight:700; color:var(--color-ink);">
                            <span class="badge badge-mono" style="font-size:11px;">
                                <?= htmlspecialchars($t['nomor_transaksi'] ?: '-') ?>
                            </span>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($t['nama_akun']) ?></strong>
                        </td>
                        <td class="cell-nowrap">
                            <span class="badge <?= $badgeClass ?>" style="font-weight:700;">
                                <?= htmlspecialchars($jenisLabel) ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-secondary" style="font-weight:600;">
                                <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$t['kategori']))) ?>
                            </span>
                        </td>
                        <td style="font-size:12.5px;color:var(--color-ink);">
                            <div><?= htmlspecialchars($t['keterangan']) ?></div>
                            <?php if (!empty($t['referensi_tabel']) && !empty($t['referensi_id'])): ?>
                                <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
                                    Ref: <span class="font-mono"><?= htmlspecialchars($t['referensi_tabel']) ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="cell-right cell-currency" style="font-weight:800;color:<?= $isMasuk ? '#10b981' : '#ef4444' ?>;">
                            <?= ($isMasuk ? '+ ' : '- ') . Format::rupiah((float)$t['nominal']) ?>
                        </td>
                        <td class="cell-right cell-currency" style="font-weight:600;">
                            <?= Format::rupiah((float)$t['saldo_berjalan']) ?>
                        </td>
                        <td class="cell-nowrap" style="font-size:11.5px;color:var(--color-ink-mute);">
                            <?= htmlspecialchars($t['nama_user'] ?? 'Sistem') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- PAGINATION BAR -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div class="p-3 border-t flex flex-col sm:flex-row items-center justify-between gap-3" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div style="font-size:12px;color:var(--color-ink-mute);">
                Menampilkan halaman <strong><?= $pagination['page'] ?></strong> dari <strong><?= $pagination['total_pages'] ?></strong> (Total <?= $pagination['total_rows'] ?> transaksi)
            </div>

            <div class="flex items-center gap-1.5">
                <?php if ($pagination['page'] > 1): ?>
                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['page'] - 1])) ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11.5px;">
                        <i data-lucide="chevron-left" style="width:13px;height:13px;"></i>
                        <span>Sebelumnya</span>
                    </a>
                <?php endif; ?>

                <?php 
                $startP = max(1, $pagination['page'] - 2);
                $endP = min($pagination['total_pages'], $pagination['page'] + 2);
                for ($p = $startP; $p <= $endP; $p++): 
                ?>
                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $p])) ?>" 
                       class="btn btn-sm <?= $p === $pagination['page'] ? 'btn-primary' : 'btn-ghost' ?>" 
                       style="padding:4px 10px;font-size:11.5px;min-width:30px;text-align:center;">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                    <a href="?<?= http_build_query(array_merge($filters, ['page' => $pagination['page'] + 1])) ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11.5px;">
                        <span>Berikutnya</span>
                        <i data-lucide="chevron-right" style="width:13px;height:13px;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ========================================================================= -->
    <!-- MODALS                                                                    -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: CATAT KAS MASUK -->
    <?php if (Auth::can('cash.inflow')): ?>
    <template x-teleport="body">
    <div x-show="showInflowModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:480px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="arrow-down-left" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title">Catat Kas Masuk</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);">Penerimaan modal, pendapatan lain-lain</div>
                    </div>
                </div>
            </div>

            <form action="<?= Router::url('/cash/store-inflow') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label class="form-label">Masuk ke Akun Kas / Bank *</label>
                    <select name="akun_kas_id" required class="form-input">
                        <option value="">-- Pilih Akun Kas Penerima --</option>
                        <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format((float)$a['saldo_saat_ini'], 0, ',', '.') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nominal Masuk (Rp) *</label>
                        <input type="text" name="nominal" required class="form-input font-mono input-rupiah" placeholder="500.000">
                    </div>
                    <div>
                        <label class="form-label">Tanggal Transaksi *</label>
                        <input type="date" name="tanggal_transaksi" value="<?= date('Y-m-d') ?>" required class="form-input">
                    </div>
                </div>

                <div>
                    <label class="form-label">Kategori Pemasukan *</label>
                    <select name="kategori" required class="form-input">
                        <option value="Tambahan Modal Owner">Tambahan Modal Owner</option>
                        <option value="Pendapatan Bunga Bank">Pendapatan Bunga Bank / Jasa Giro</option>
                        <option value="Penjualan Non-Sistem">Penjualan Non-Sistem / Scrap</option>
                        <option value="Pendapatan Lain-lain">Pendapatan Lain-lain</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Keterangan Transaksi *</label>
                    <textarea name="keterangan" required class="form-input" rows="2" placeholder="Contoh: Suntikan modal tambahan operasional packing"></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showInflowModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background:#059669;">
                        <i data-lucide="save"></i>
                        <span>Simpan Kas Masuk</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <?php endif; ?>

    <!-- MODAL 2: CATAT KAS KELUAR (BEBAN OPERASIONAL) -->
    <?php if (Auth::can('cash.outflow')): ?>
    <template x-teleport="body">
    <div x-show="showOutflowModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:480px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="arrow-up-right" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title">Catat Kas Keluar (Beban)</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);">Pengeluaran operasional toko, bensin, listrik, dll</div>
                    </div>
                </div>
            </div>

            <form action="<?= Router::url('/cash/store-outflow') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <div>
                    <label class="form-label">Potong dari Akun Kas / Bank *</label>
                    <select name="akun_kas_id" required class="form-input">
                        <option value="">-- Pilih Akun Kas Sumber --</option>
                        <?php foreach ($accounts as $a): ?>
                        <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format((float)$a['saldo_saat_ini'], 0, ',', '.') ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nominal Keluar (Rp) *</label>
                        <input type="text" name="nominal" required class="form-input font-mono input-rupiah" placeholder="150.000">
                    </div>
                    <div>
                        <label class="form-label">Tanggal Transaksi *</label>
                        <input type="date" name="tanggal_transaksi" value="<?= date('Y-m-d') ?>" required class="form-input">
                    </div>
                </div>

                <div>
                    <label class="form-label">Kategori Beban / Biaya *</label>
                    <select name="kategori" required class="form-input">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['nama_kategori']) ?>"><?= htmlspecialchars($cat['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                        <option value="Lain-lain">Lain-lain</option>
                    </select>
                </div>

                <div>
                    <label class="form-label">Keterangan Pengeluaran *</label>
                    <textarea name="keterangan" required class="form-input" rows="2" placeholder="Contoh: Beli bensin mobil delivery kanvas rute Tangerang"></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showOutflowModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary" style="background:#ef4444;">
                        <i data-lucide="save"></i>
                        <span>Simpan Kas Keluar</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <?php endif; ?>

    <!-- MODAL 3: TRANSFER ANTAR KAS (MUTASI DANA) -->
    <?php if (Auth::can('cash.transfer')): ?>
    <template x-teleport="body">
    <div x-show="showTransferModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:500px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(59,130,246,0.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="arrow-left-right" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title">Transfer Dana Antar Kas</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);">Setor uang kasir ke bank / mutasi dana antar rekening</div>
                    </div>
                </div>
            </div>

            <form action="<?= Router::url('/cash/store-transfer') ?>" method="POST" @submit="submitTransferForm($event)" style="display:flex;flex-direction:column;gap:14px;">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Dari Akun Kas (Sumber) *</label>
                        <select name="source_account_id" x-model="transferForm.source_account_id" @change="onSourceChange()" required class="form-input">
                            <option value="">-- Pilih Akun Sumber --</option>
                            <?php foreach ($accounts as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format((float)$a['saldo_saat_ini'], 0, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Ke Akun Kas (Tujuan) *</label>
                        <select name="dest_account_id" x-model="transferForm.dest_account_id" @change="onDestChange()" required class="form-input">
                            <option value="">-- Pilih Akun Tujuan --</option>
                            <?php foreach ($accounts as $a): ?>
                            <option value="<?= $a['id'] ?>" :disabled="transferForm.source_account_id === '<?= $a['id'] ?>'">
                                <?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format((float)$a['saldo_saat_ini'], 0, ',', '.') ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Peringatan Anti-Transfer ke Akun yang Sama -->
                <template x-if="transferForm.source_account_id && transferForm.dest_account_id && transferForm.source_account_id === transferForm.dest_account_id">
                    <div style="background:rgba(239,68,68,0.1); border:1px solid rgba(239,68,68,0.3); border-radius:8px; padding:10px 14px; display:flex; align-items:center; gap:8px; color:#dc2626; font-size:12px; font-weight:700;">
                        <i data-lucide="alert-circle" style="width:16px;height:16px;flex-shrink:0;"></i>
                        <span>Transfer Ditolak: Akun kas sumber dan tujuan tidak boleh sama! Silakan pilih akun yang berbeda.</span>
                    </div>
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Nominal Transfer (Rp) *</label>
                        <input type="text" name="nominal" x-model="transferForm.nominal" required class="form-input font-mono input-rupiah" placeholder="1.000.000">
                    </div>

                    <div>
                        <label class="form-label">Tanggal Transaksi *</label>
                        <input type="date" name="tanggal_transaksi" x-model="transferForm.tanggal_transaksi" required class="form-input">
                    </div>
                </div>

                <div>
                    <label class="form-label">Keterangan Transfer</label>
                    <input type="text" name="keterangan" x-model="transferForm.keterangan" class="form-input" placeholder="Contoh: Setoran hasil penjualan POS kasir shift siang">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showTransferModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" 
                            class="btn btn-primary" 
                            :disabled="!transferForm.source_account_id || !transferForm.dest_account_id || transferForm.source_account_id === transferForm.dest_account_id"
                            :style="(transferForm.source_account_id === transferForm.dest_account_id || !transferForm.source_account_id || !transferForm.dest_account_id) ? 'background:#9ca3af; cursor:not-allowed; opacity:0.7;' : 'background:#3b82f6;'">
                        <i data-lucide="send"></i>
                        <span>Proses Transfer Dana</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>
    <?php endif; ?>

</div>

<script>
function cashTransactionsApp() {
    return {
        showInflowModal: false,
        showOutflowModal: false,
        showTransferModal: false,

        accountsList: <?= json_encode(array_values(array_map(fn($a) => ['id' => (string)$a['id'], 'nama' => (string)$a['nama_akun']], $accounts))) ?>,

        transferForm: {
            source_account_id: '<?= $accounts[0]['id'] ?? '' ?>',
            dest_account_id: '<?= $accounts[1]['id'] ?? '' ?>',
            nominal: '',
            tanggal_transaksi: '<?= date('Y-m-d') ?>',
            keterangan: 'Setoran Kas'
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        openInflowModal() {
            this.showInflowModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openOutflowModal() {
            this.showOutflowModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        onSourceChange() {
            if (this.transferForm.source_account_id && this.transferForm.source_account_id === this.transferForm.dest_account_id) {
                const other = this.accountsList.find(a => a.id !== this.transferForm.source_account_id);
                this.transferForm.dest_account_id = other ? other.id : '';
            }
            this.$nextTick(() => lucide.createIcons());
        },

        onDestChange() {
            if (this.transferForm.dest_account_id && this.transferForm.dest_account_id === this.transferForm.source_account_id) {
                this.transferForm.dest_account_id = '';
            }
            this.$nextTick(() => lucide.createIcons());
        },

        openTransferModal() {
            this.transferForm.nominal = '';
            if (this.accountsList.length >= 2) {
                this.transferForm.source_account_id = this.accountsList[0].id;
                this.transferForm.dest_account_id = this.accountsList[1].id;
            } else if (this.accountsList.length === 1) {
                this.transferForm.source_account_id = this.accountsList[0].id;
                this.transferForm.dest_account_id = '';
            }
            this.showTransferModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        submitTransferForm(e) {
            if (!this.transferForm.source_account_id || !this.transferForm.dest_account_id) {
                e.preventDefault();
                alert('Pilih akun kas sumber dan akun kas tujuan.');
                return false;
            }
            if (this.transferForm.source_account_id === this.transferForm.dest_account_id) {
                e.preventDefault();
                alert('Transfer Ditolak: Akun kas sumber dan tujuan tidak boleh sama!');
                return false;
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

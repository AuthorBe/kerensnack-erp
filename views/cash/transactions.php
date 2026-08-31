<?php
use App\Helpers\Format;
use App\Core\Router;
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
                    <span>Arus Kas Harian</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Kas Masuk &amp; Kas Keluar' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Catat Pengeluaran Beban Operasional &amp; Pendapatan Kas Lain' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <button @click="openInflowModal()" class="btn btn-secondary" style="font-weight:600;">
                <i data-lucide="arrow-down-left"></i>
                <span>Kas Masuk</span>
            </button>
            <button @click="openOutflowModal()" class="btn btn-primary" style="font-weight:700;">
                <i data-lucide="arrow-up-right"></i>
                <span>Kas Keluar</span>
            </button>
        </div>
    </div>

    <!-- REKAP SUMMARY CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;border-left:4px solid #10b981;">
            <div class="stat-card-icon" style="background:rgba(16,185,129,0.1);color:#10b981;">
                <i data-lucide="arrow-down-left"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Kas Masuk (Periode)</div>
                <div class="stat-card-value" style="color:#10b981;font-size:18px;">
                    <?= Format::rupiah($summary['total_inflow']) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Pendapatan &amp; Mutasi Masuk</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;border-left:4px solid #ef4444;">
            <div class="stat-card-icon" style="background:rgba(239,68,68,0.1);color:#ef4444;">
                <i data-lucide="arrow-up-right"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Kas Keluar (Beban)</div>
                <div class="stat-card-value" style="color:#ef4444;font-size:18px;">
                    <?= Format::rupiah($summary['total_outflow']) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Biaya Operasional &amp; Transfer</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;border-left:4px solid <?= $summary['net'] >= 0 ? '#3b82f6' : '#ef4444' ?>;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="scale"></i>
            </div>
            <div>
                <div class="stat-card-label">Net Arus Kas (Periode)</div>
                <div class="stat-card-value" style="color:<?= $summary['net'] >= 0 ? '#3b82f6' : '#ef4444' ?>;font-size:18px;">
                    <?= ($summary['net'] >= 0 ? '+ ' : '- ') . Format::rupiah(abs($summary['net'])) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Selisih Masuk - Keluar</div>
            </div>
        </div>

    </div>

    <!-- MAIN DATA CARD -->
    <div class="card p-0 overflow-hidden">
        
        <!-- FILTER & ACTION BAR -->
        <div class="flex flex-col lg:flex-row items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            
            <form method="GET" action="<?= Router::url('/cash/transactions') ?>" class="flex flex-wrap items-center gap-2 w-full lg:w-auto flex-1">
                <!-- Date Range -->
                <div class="flex items-center gap-1.5">
                    <input type="date" name="start_date" value="<?= htmlspecialchars($filters['start_date']) ?>" class="form-input" style="height:36px;font-size:12.5px;">
                    <span style="color:var(--color-ink-mute);font-size:12px;">s/d</span>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($filters['end_date']) ?>" class="form-input" style="height:36px;font-size:12.5px;">
                </div>

                <!-- Account Filter -->
                <select name="account_id" class="form-input" style="height:36px;font-size:12.5px;max-width:180px;">
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
                    <option value="transfer" <?= $filters['type'] === 'transfer' ? 'selected' : '' ?>>Transfer</option>
                </select>

                <button type="submit" class="btn btn-secondary btn-sm" style="height:36px;">
                    <i data-lucide="filter" style="width:14px;height:14px;"></i>
                    <span>Filter</span>
                </button>
            </form>

            <!-- Buttons: Kas Masuk & Kas Keluar -->
            <div class="flex items-center gap-2 w-full lg:w-auto">
                <button type="button" @click="openInflowModal()" class="btn btn-success" style="height:36px;background:#10b981;color:#fff;font-size:12.5px;">
                    <i data-lucide="plus-circle" style="width:14px;height:14px;"></i>
                    <span>+ Kas Masuk</span>
                </button>
                <button type="button" @click="openOutflowModal()" class="btn btn-danger" style="height:36px;background:#ef4444;color:#fff;font-size:12.5px;">
                    <i data-lucide="minus-circle" style="width:14px;height:14px;"></i>
                    <span>+ Kas Keluar (Beban)</span>
                </button>
            </div>

        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 860px;">
                <thead>
                    <tr>
                        <th style="width:105px; min-width:95px;" class="cell-nowrap">Tanggal</th>
                        <th style="min-width:140px;">Akun Kas / Bank</th>
                        <th class="cell-nowrap" style="width:130px; min-width:110px;">Jenis</th>
                        <th style="min-width:120px;">Kategori</th>
                        <th style="min-width:180px;">Keterangan Transaksi</th>
                        <th class="cell-right cell-nowrap" style="width:140px; min-width:120px;">Nominal (Rp)</th>
                        <th class="cell-right cell-nowrap" style="width:140px; min-width:120px;">Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                            <i data-lucide="receipt" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                            <div style="font-weight:600;font-size:13px;">Tidak ada riwayat transaksi kas pada periode ini</div>
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
                        <td class="cell-nowrap">
                            <?php if ($t['jenis_kas'] === 'masuk' || $t['jenis_kas'] === 'transfer_masuk'): ?>
                                <span class="badge badge-success">Kas Masuk</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Kas Keluar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge badge-secondary" style="font-weight:600;">
                                <?= htmlspecialchars($t['kategori']) ?>
                            </span>
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

    <!-- ========================================================================= -->
    <!-- MODALS                                                                    -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: CATAT KAS MASUK -->
    <template x-teleport="body">
    <div x-show="showInflowModal" x-cloak class="modal-backdrop" @click.self="showInflowModal = false">
        <div class="modal-box" style="max-width:480px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="plus-circle" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title">Catat Kas Masuk</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);">Penerimaan modal, pendapatan lain-lain</div>
                    </div>
                </div>
                <button type="button" @click="showInflowModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
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
                    <label class="form-label">Kategori Pemasukan</label>
                    <input type="text" name="kategori" value="Tambahan Modal / Pendapatan Lain" class="form-input" placeholder="Contoh: Suntikan Modal Owner">
                </div>

                <div>
                    <label class="form-label">Keterangan / Catatan *</label>
                    <textarea name="keterangan" required class="form-input" rows="2" placeholder="Contoh: Suntikan modal tambahan untuk belanja bahan baku"></textarea>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showInflowModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span>Simpan Kas Masuk</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL 2: CATAT KAS KELUAR (BEBAN OPERASIONAL) -->
    <template x-teleport="body">
    <div x-show="showOutflowModal" x-cloak class="modal-backdrop" @click.self="showOutflowModal = false">
        <div class="modal-box" style="max-width:480px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="minus-circle" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title">Catat Kas Keluar (Beban)</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);">Pengeluaran operasional toko, bensin, listrik, dll</div>
                    </div>
                </div>
                <button type="button" @click="showOutflowModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
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
                    <textarea name="keterangan" required class="form-input" rows="2" placeholder="Contoh: Beli bensin mobil delivery kanvas rute Bandung"></textarea>
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

</div>

<script>
function cashTransactionsApp() {
    return {
        showInflowModal: false,
        showOutflowModal: false,

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
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

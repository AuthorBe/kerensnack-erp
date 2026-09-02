<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div x-data="cashApp()" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-emerald">
                <i data-lucide="wallet"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot"></span>
                    <span>Keuangan &amp; Akuntansi Kas</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Buku Kas &amp; Rekening Bank' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Kelola Saldo Kas, Rekening Bank, Transfer &amp; Nilai Aset Stok' ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <button @click="openTransferModal()" class="btn btn-secondary" style="font-weight:600;">
                <i data-lucide="arrow-left-right"></i>
                <span>Transfer Kas</span>
            </button>
            <button @click="openAddAccountModal()" class="btn btn-primary" style="font-weight:700;">
                <i data-lucide="plus"></i>
                <span>Tambah Akun Kas</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TOP STATS: 4 MODERN ENTERPRISE FINANCIAL METRIC CARDS                    -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
        
        <!-- 1. TOTAL KAS CAIR -->
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Kas &amp; Bank Cair</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="wallet" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:16px;font-weight:900;font-family:var(--font-mono);color:#10b981;white-space:nowrap;line-height:1.2;">
                <?= Format::rupiah($liquidCashTotal) ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Uang Riil (Tunai + Bank)</div>
        </div>

        <!-- 2. LIVE KAS PERSEDIAAN + TOMBOL INFO [i] -->
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:6px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Kas Persediaan</span>
                <button type="button" @click="showInfoModal = true" class="btn btn-ghost btn-sm"
                        style="padding:1px 6px;height:22px;border-radius:10px;font-size:10px;font-weight:700;color:#3b82f6;background:rgba(59,130,246,0.1);display:inline-flex;align-items:center;gap:3px;flex-shrink:0;"
                        title="Klik untuk melihat rincian kalkulasi HPP">
                    <i data-lucide="info" style="width:11px;height:11px;"></i>
                    <span>HPP</span>
                </button>
            </div>
            <div style="font-size:16px;font-weight:900;font-family:var(--font-mono);color:#3b82f6;white-space:nowrap;line-height:1.2;">
                <?= Format::rupiah($totalInventoryValuation) ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Bahan Mentah &amp; Jadi</div>
        </div>

        <!-- 3. PIUTANG BERJALAN -->
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Piutang Berjalan</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(245,158,11,0.12);color:#f59e0b;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="receipt" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:16px;font-weight:900;font-family:var(--font-mono);color:#f59e0b;white-space:nowrap;line-height:1.2;">
                <?= Format::rupiah($receivablesTotal) ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Tagihan Toko Mitra</div>
        </div>

        <!-- 4. TOTAL KEKAYAAN USAHA -->
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Total Aset Usaha</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(139,92,246,0.12);color:#8b5cf6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="landmark" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:16px;font-weight:900;font-family:var(--font-mono);color:#8b5cf6;white-space:nowrap;line-height:1.2;">
                <?= Format::rupiah($totalBusinessWealth) ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Kas + Stok + Piutang</div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- DAFTAR AKUN KAS SECTION HEADING                                          -->
    <!-- ========================================================================= -->
    <div class="flex items-center gap-2 pt-1">
        <h2 style="font-size:15px;font-weight:700;color:var(--color-ink);margin:0;">Daftar Akun Kas &amp; Rekening Bank</h2>
        <span class="badge badge-secondary"><?= count($accounts) ?> Akun Aktif</span>
    </div>

    <!-- ========================================================================= -->
    <!-- GRID AKUN KAS / BANK CARDS                                                -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($accounts as $acc): 
            $isBank = $acc['tipe_akun'] === 'bank';
            $isQris = $acc['tipe_akun'] === 'qris';
            $isOp = in_array($acc['tipe_akun'], ['kas_operasional', 'kas_kecil']);
            $isTunai = $acc['tipe_akun'] === 'kas_tunai';

            $badgeBg = $isBank ? 'rgba(59,130,246,0.12)' : ($isQris ? 'rgba(225,29,72,0.12)' : ($isOp ? 'rgba(245,158,11,0.12)' : 'rgba(16,185,129,0.12)'));
            $badgeColor = $isBank ? '#3b82f6' : ($isQris ? '#e11d48' : ($isOp ? '#f59e0b' : '#10b981'));
            $iconName = $isBank ? 'building-2' : ($isQris ? 'qr-code' : ($isOp ? 'briefcase' : 'banknote'));

            $tipeLabel = match($acc['tipe_akun']) {
                'kas_tunai' => 'Kas Tunai (Laci / Toko)',
                'qris' => 'QRIS / E-Wallet (Digital)',
                'bank' => 'Rekening Bank',
                'kas_operasional', 'kas_kecil' => 'Kas Operasional (Petty Cash)',
                default => ucfirst(str_replace('_', ' ', $acc['tipe_akun']))
            };
        ?>
        <div class="card p-5" style="display:flex;flex-direction:column;justify-content:space-between;gap:14px;background:var(--color-canvas);border:1px solid <?= $acc['is_default_pos'] ? 'rgba(16,185,129,0.4)' : 'var(--color-hairline)' ?>;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            
            <!-- Header Kartu -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                    <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?= $badgeBg ?>;color:<?= $badgeColor ?>;">
                        <i data-lucide="<?= $iconName ?>" style="width:20px;height:20px;"></i>
                    </div>
                    <div style="min-width:0;">
                        <div style="font-weight:800;font-size:14px;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($acc['nama_akun']) ?>">
                            <?= htmlspecialchars($acc['nama_akun']) ?>
                        </div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">
                            <?php if ($isBank): ?>
                                <span class="font-mono"><?= htmlspecialchars($acc['nomor_rekening'] ?: '-') ?></span> (a.n. <?= htmlspecialchars($acc['atas_nama'] ?: '-') ?>)
                            <?php else: ?>
                                <?= htmlspecialchars($tipeLabel) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Default POS Status / Action -->
                <div>
                    <?php if ($acc['is_default_pos']): ?>
                        <span class="badge badge-success" style="font-size:11px;font-weight:700;display:inline-flex;align-items:center;gap:4px;padding:3px 8px;">
                            <i data-lucide="check-circle-2" style="width:12px;height:12px;"></i>
                            <span>Default POS</span>
                        </span>
                    <?php else: ?>
                        <form action="<?= Router::url('/cash/set-default-pos') ?>" method="POST" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="font-size:11px;color:var(--color-ink-mute);padding:3px 8px;border-radius:12px;border:1px dashed var(--color-hairline);" title="Klik untuk jadikan akun ini sebagai penampung tunai kasir POS">
                                <i data-lucide="star" style="width:12px;height:12px;"></i>
                                <span>Set Default POS</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Saldo Saat Ini -->
            <div style="padding:12px 14px;background:var(--color-canvas-soft);border-radius:var(--rounded-md);border:1px solid var(--color-hairline);">
                <div style="font-size:11px;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.05em;font-weight:700;">Saldo Kas Saat Ini</div>
                <div style="font-size:22px;font-weight:900;font-family:var(--font-mono);color:<?= (float)$acc['saldo_saat_ini'] >= 0 ? 'var(--color-ink)' : '#ef4444' ?>;margin-top:2px;white-space:nowrap;">
                    <?= Format::rupiah((float)$acc['saldo_saat_ini']) ?>
                </div>
            </div>

            <!-- Footer Kartu & Aksi -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:6px;border-top:1px solid var(--color-hairline);">
                <span style="font-size:11.5px;color:var(--color-ink-mute);">
                    <?= (int)$acc['total_transaksi'] ?> Transaksi
                </span>

                <div style="display:flex;gap:4px;">
                    <a href="<?= Router::url('/cash/transactions?account_id=' . $acc['id']) ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11.5px;" title="Lihat Mutasi Kas">
                        <i data-lucide="history" style="width:13px;height:13px;"></i>
                        <span>Mutasi</span>
                    </a>
                    <button @click="openEditAccountModal(<?= htmlspecialchars(json_encode($acc)) ?>)" class="btn btn-ghost btn-sm" style="padding:4px 8px;" title="Edit Akun Kas">
                        <i data-lucide="edit-3" style="width:13px;height:13px;"></i>
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- MUTASI ARUS KAS TERKINI TABLE                                             -->
    <!-- ========================================================================= -->
    <div class="card p-0 overflow-hidden" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);">
        <div class="p-4 border-b flex items-center justify-between" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div>
                <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);margin:0;">Mutasi Arus Kas Terkini</h3>
                <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">10 transaksi terakhir dari seluruh akun kas &amp; bank</div>
            </div>
            <a href="<?= Router::url('/cash/transactions') ?>" class="btn btn-secondary btn-sm" style="font-size:12px;">
                <span>Lihat Semua Transaksi</span>
                <i data-lucide="arrow-right" style="width:14px;height:14px;"></i>
            </a>
        </div>

        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:110px;">Tanggal</th>
                        <th>Akun Kas</th>
                        <th>Kategori</th>
                        <th>Keterangan</th>
                        <th class="cell-right" style="width:150px;">Nominal (Rp)</th>
                        <th class="cell-right" style="width:150px;">Saldo Berjalan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentMovements)): ?>
                    <tr>
                        <td colspan="6" style="text-align:center;padding:32px;color:var(--color-ink-mute);">
                            <i data-lucide="receipt" style="width:32px;height:32px;margin:0 auto 6px auto;opacity:0.5;"></i>
                            <div style="font-weight:600;font-size:13px;">Belum ada riwayat mutasi kas</div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($recentMovements as $rm): ?>
                    <tr>
                        <td class="cell-nowrap font-mono" style="font-size:12px;">
                            <?= Format::tanggal($rm['tanggal_transaksi'], false) ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($rm['nama_akun']) ?></strong>
                        </td>
                        <td>
                            <span class="badge <?= $rm['jenis_kas'] === 'masuk' || $rm['jenis_kas'] === 'transfer_masuk' ? 'badge-success' : 'badge-secondary' ?>">
                                <?= htmlspecialchars($rm['kategori']) ?>
                            </span>
                        </td>
                        <td style="font-size:12.5px;color:var(--color-ink);">
                            <?= htmlspecialchars($rm['keterangan']) ?>
                        </td>
                        <td class="cell-right cell-currency" style="font-weight:700;color:<?= $rm['jenis_kas'] === 'masuk' || $rm['jenis_kas'] === 'transfer_masuk' ? '#10b981' : '#ef4444' ?>;">
                            <?= ($rm['jenis_kas'] === 'masuk' || $rm['jenis_kas'] === 'transfer_masuk' ? '+ ' : '- ') . Format::rupiah((float)$rm['nominal']) ?>
                        </td>
                        <td class="cell-right cell-currency" style="font-weight:600;">
                            <?= Format::rupiah((float)$rm['saldo_berjalan']) ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODALS SECTION                                                            -->
    <!-- ========================================================================= -->

    <!-- MODAL 1: INFO RINCIAN VALUASI KAS PERSEDIAAN (HPP POPUP) -->
    <template x-teleport="body">
    <div x-show="showInfoModal" x-cloak class="modal-backdrop" @click.self="showInfoModal = false">
        <div class="modal-box" style="max-width:580px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(59,130,246,0.1);color:#3b82f6;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="info" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title">Rincian Valuasi Kas Persediaan</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);">Kalkulasi Berdasarkan Harga Pokok Pembelian (HPP) Murni</div>
                    </div>
                </div>
                <button type="button" @click="showInfoModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">
                <!-- Formula Card -->
                <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);font-size:12px;line-height:1.5;">
                    💡 <strong>Rumus Akuntansi:</strong><br>
                    <code>Kas Persediaan = ∑ (Stok Fisik di Gudang × HPP Beli)</code><br>
                    <span style="color:var(--color-ink-mute);">Mencerminkan nilai uang modal usaha yang saat ini berwujud barang fisik di gudang.</span>
                </div>

                <!-- 3 Category Breakdown -->
                <div class="space-y-2">
                    <!-- 1. Bahan Mentah -->
                    <div style="padding:12px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);">1. Bahan Mentah Curah (Bal / Kg)</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">
                                <?= $rawValuation['count'] ?> SKU Bahan Balan • Total <?= number_format($rawValuation['total_qty'], 2, ',', '.') ?> Bal/Kg
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:800;font-family:var(--font-mono);font-size:14px;color:#3b82f6;">
                                <?= Format::rupiah($rawValuation['subtotal']) ?>
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);">HPP Beli Supplier</div>
                        </div>
                    </div>

                    <!-- 2. Bahan Kemasan -->
                    <div style="padding:12px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);">2. Bahan Kemasan (Plastik, Label &amp; Cup)</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">
                                <?= $packValuation['count'] ?> SKU Kemasan • Total <?= number_format($packValuation['total_qty'], 0, ',', '.') ?> Lembar/Pcs
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:800;font-family:var(--font-mono);font-size:14px;color:#3b82f6;">
                                <?= Format::rupiah($packValuation['subtotal']) ?>
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);">HPP Beli Kemasan</div>
                        </div>
                    </div>

                    <!-- 3. Barang Jadi -->
                    <div style="padding:12px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);">3. Barang Jadi Siap Jual (Bungkus)</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">
                                <?= $fgValuation['count'] ?> SKU Barang Jadi • Total <?= number_format($fgValuation['total_qty'], 0, ',', '.') ?> Bungkus
                            </div>
                        </div>
                        <div style="text-align:right;">
                            <div style="font-weight:800;font-family:var(--font-mono);font-size:14px;color:#3b82f6;">
                                <?= Format::rupiah($fgValuation['subtotal']) ?>
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);">HPP Modal Produksi</div>
                        </div>
                    </div>
                </div>

                <!-- Total Summary -->
                <div style="padding:14px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:var(--rounded-md);display:flex;justify-content:space-between;align-items:center;">
                    <div style="font-weight:800;font-size:13px;color:var(--color-ink);">Total Valuasi Kas Persediaan:</div>
                    <div style="font-size:18px;font-weight:900;font-family:var(--font-mono);color:#3b82f6;">
                        <?= Format::rupiah($totalInventoryValuation) ?>
                    </div>
                </div>

                <div style="display:flex;justify-content:flex-end;margin-top:4px;">
                    <button type="button" @click="showInfoModal = false" class="btn btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- MODAL 2: TAMBAH / EDIT AKUN KAS -->
    <template x-teleport="body">
    <div x-show="showAccountModal" x-cloak class="modal-backdrop" @click.self="showAccountModal = false">
        <div class="modal-box" style="max-width:480px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div class="modal-title" x-text="isEditAccount ? 'Edit Akun Kas / Bank' : 'Tambah Akun Kas / Bank Baru'"></div>
                <button type="button" @click="showAccountModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form :action="isEditAccount ? '<?= Router::url('/cash/update-account') ?>' : '<?= Router::url('/cash/store-account') ?>'" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                <input type="hidden" name="id" :value="accountForm.id">

                <div>
                    <label class="form-label">Nama Akun Kas / Bank *</label>
                    <input type="text" name="nama_akun" x-model="accountForm.nama_akun" required class="form-input" placeholder="Contoh: BCA Bisnis KEREN Snack">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Tipe Akun Kas *</label>
                        <select name="tipe_akun" x-model="accountForm.tipe_akun" required class="form-input font-semibold">
                            <option value="kas_tunai">💵 Kas Tunai (Laci / Toko)</option>
                            <option value="qris">📱 QRIS / E-Wallet (Digital)</option>
                            <option value="bank">🏦 Rekening Bank (Transfer)</option>
                            <option value="kas_operasional">💼 Kas Operasional (Petty Cash)</option>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Nomor Rekening (Jika Bank)</label>
                        <input type="text" name="nomor_rekening" x-model="accountForm.nomor_rekening" class="form-input font-mono" placeholder="1234567890">
                    </div>
                </div>

                <div>
                    <label class="form-label">Atas Nama Rekening</label>
                    <input type="text" name="atas_nama" x-model="accountForm.atas_nama" class="form-input" placeholder="Contoh: Owner KEREN Snack">
                </div>

                <template x-if="!isEditAccount">
                    <div>
                        <label class="form-label">Saldo Awal (Rp)</label>
                        <input type="text" name="saldo_awal" x-model="accountForm.saldo_awal" class="form-input font-mono input-rupiah" placeholder="0">
                    </div>
                </template>

                <div style="display:flex;flex-direction:column;gap:8px;padding-top:4px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;font-weight:600;">
                        <input type="checkbox" name="is_default_pos" x-model="accountForm.is_default_pos" style="width:16px;height:16px;accent-color:var(--color-primary);">
                        <span>Gunakan sebagai Default Kasir POS (Penerimaan Kasir)</span>
                    </label>

                    <template x-if="isEditAccount">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;font-weight:600;">
                            <input type="checkbox" name="status_aktif" x-model="accountForm.status_aktif" style="width:16px;height:16px;accent-color:var(--color-primary);">
                            <span>Status Akun Aktif</span>
                        </label>
                    </template>
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showAccountModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="save"></i>
                        <span x-text="isEditAccount ? 'Simpan Perubahan' : 'Tambah Akun'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    <!-- MODAL 3: TRANSFER ANTAR KAS (MUTASI DANA) -->
    <template x-teleport="body">
    <div x-show="showTransferModal" x-cloak class="modal-backdrop" @click.self="showTransferModal = false">
        <div class="modal-box" style="max-width:500px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div style="display:flex;align-items:center;gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.1);color:#10b981;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="arrow-left-right" style="width:16px;height:16px;"></i>
                    </div>
                    <div>
                        <div class="modal-title">Transfer Dana Antar Kas</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);">Setor uang kasir ke bank / mutasi dana operasional</div>
                    </div>
                </div>
                <button type="button" @click="showTransferModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <form action="<?= Router::url('/cash/store-transfer') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="form-label">Dari Akun Kas (Sumber) *</label>
                        <select name="source_account_id" x-model="transferForm.source_account_id" required class="form-input">
                            <option value="">-- Pilih Akun Sumber --</option>
                            <?php foreach ($accounts as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?> (Rp <?= number_format((float)$a['saldo_saat_ini'], 0, ',', '.') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="form-label">Ke Akun Kas (Tujuan) *</label>
                        <select name="dest_account_id" x-model="transferForm.dest_account_id" required class="form-input">
                            <option value="">-- Pilih Akun Tujuan --</option>
                            <?php foreach ($accounts as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= htmlspecialchars($a['nama_akun']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

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
                    <input type="text" name="keterangan" x-model="transferForm.keterangan" class="form-input" placeholder="Contoh: Setoran hasil penjualan POS shift siang">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:12px;">
                    <button type="button" @click="showTransferModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">
                        <i data-lucide="send"></i>
                        <span>Proses Transfer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

</div>

<script>
function cashApp() {
    return {
        showInfoModal: false,
        showAccountModal: false,
        showTransferModal: false,
        isEditAccount: false,

        accountForm: {
            id: '',
            nama_akun: '',
            tipe_akun: 'kas_tunai',
            nomor_rekening: '',
            atas_nama: '',
            saldo_awal: '0',
            is_default_pos: false,
            status_aktif: true
        },

        transferForm: {
            source_account_id: '<?= $accounts[0]['id'] ?? '' ?>',
            dest_account_id: '<?= $accounts[1]['id'] ?? ($accounts[0]['id'] ?? '') ?>',
            nominal: '',
            tanggal_transaksi: '<?= date('Y-m-d') ?>',
            keterangan: 'Setoran Kas'
        },

        init() {
            this.$nextTick(() => lucide.createIcons());
        },

        openAddAccountModal() {
            this.isEditAccount = false;
            this.accountForm = {
                id: '',
                nama_akun: '',
                tipe_akun: 'kas_tunai',
                nomor_rekening: '',
                atas_nama: '',
                saldo_awal: '0',
                is_default_pos: false,
                status_aktif: true
            };
            this.showAccountModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openEditAccountModal(acc) {
            this.isEditAccount = true;
            this.accountForm = {
                id: acc.id,
                nama_akun: acc.nama_akun,
                tipe_akun: acc.tipe_akun || 'kas_tunai',
                nomor_rekening: acc.nomor_rekening === '-' ? '' : acc.nomor_rekening,
                atas_nama: acc.atas_nama === '-' ? '' : acc.atas_nama,
                saldo_awal: '0',
                is_default_pos: Boolean(acc.is_default_pos),
                status_aktif: Boolean(acc.status_aktif)
            };
            this.showAccountModal = true;
            this.$nextTick(() => lucide.createIcons());
        },

        openTransferModal() {
            this.transferForm.nominal = '';
            this.showTransferModal = true;
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

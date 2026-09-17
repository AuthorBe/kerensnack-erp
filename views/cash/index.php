<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div x-data="cashAccountsApp()" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-emerald">
                <i data-lucide="wallet-cards"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot"></span>
                    <span>Keuangan &amp; Akuntansi Kas</span>
                </div>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Manajemen Akun Kas & Rekening Bank') ?></h1>
                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Kelola Master Rekening Bank, Laci Kasir, QRIS & Verifikasi Saldo Buku Kas') ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center;">
            <a href="<?= Router::url('/cash/transactions') ?>" class="btn btn-secondary" style="font-weight:600; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="arrow-left-right" style="width:15px; height:15px;"></i>
                <span>Lihat Transaksi Kas</span>
            </a>
            <button @click="openAddAccountModal()" class="btn btn-primary" style="font-weight:700; display:inline-flex; align-items:center; gap:6px;">
                <i data-lucide="plus" style="width:16px; height:16px;"></i>
                <span>Tambah Akun Kas</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TOP STATS: 4 KARTU LIKUIDITAS UANG KAS RIIL                               -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-4 gap-2.5 sm:gap-4">
        
        <!-- 1. TOTAL KAS CAIR -->
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Total Kas Cair</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(16,185,129,0.12);color:#10b981;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="wallet" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:16px;font-weight:900;font-family:var(--font-mono);color:#10b981;white-space:nowrap;line-height:1.2;">
                <?= Format::rupiah($liquidCashTotal) ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Seluruh Saldo Kas Aktif</div>
        </div>

        <!-- 2. KAS TUNAI LACI TOKO -->
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Kas Tunai (Laci)</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(59,130,246,0.12);color:#3b82f6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="banknote" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:16px;font-weight:900;font-family:var(--font-mono);color:#3b82f6;white-space:nowrap;line-height:1.2;">
                <?= Format::rupiah($cashTunaiTotal) ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Laci Kasir &amp; Fisik Toko</div>
        </div>

        <!-- 3. REKENING BANK -->
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Rekening Bank</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(139,92,246,0.12);color:#8b5cf6;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="building-2" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:16px;font-weight:900;font-family:var(--font-mono);color:#8b5cf6;white-space:nowrap;line-height:1.2;">
                <?= Format::rupiah($bankTotal) ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">Saldo di BCA, BSI, dll</div>
        </div>

        <!-- 4. QRIS & DIGITAL / OPERASIONAL -->
        <div class="card p-3 sm:p-4" style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px;">
                <span style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">QRIS &amp; Digital</span>
                <div style="width:28px;height:28px;border-radius:6px;background:rgba(225,29,72,0.12);color:#e11d48;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="qr-code" style="width:14px;height:14px;"></i>
                </div>
            </div>
            <div style="font-size:16px;font-weight:900;font-family:var(--font-mono);color:#e11d48;white-space:nowrap;line-height:1.2;">
                <?= Format::rupiah($qrisDigitalTotal) ?>
            </div>
            <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">E-Wallet &amp; Kas Kecil</div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- BANNER REKONSILIASI KESEHATAN BUKU KAS (AUDIT HEALTH CHECK)               -->
    <!-- ========================================================================= -->
    <div class="card p-3 sm:p-4" style="background:<?= $allReconciled ? 'rgba(16,185,129,0.06)' : 'rgba(239,68,68,0.06)' ?>; border:1px solid <?= $allReconciled ? 'rgba(16,185,129,0.3)' : 'rgba(239,68,68,0.3)' ?>; border-radius:var(--rounded-lg); display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:10px;">
            <div style="width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:<?= $allReconciled ? 'rgba(16,185,129,0.15)' : 'rgba(239,68,68,0.15)' ?>; color:<?= $allReconciled ? '#10b981' : '#ef4444' ?>; flex-shrink:0;">
                <i data-lucide="<?= $allReconciled ? 'shield-check' : 'alert-triangle' ?>" style="width:18px; height:18px;"></i>
            </div>
            <div>
                <div style="font-weight:800; font-size:13px; color:<?= $allReconciled ? '#065f46' : '#991b1b' ?>;">
                    <?= $allReconciled ? 'Integritas Buku Kas Terverifikasi (100% Seimbang)' : 'Peringatan Rekonsiliasi: Ditemukan Selisih Saldo' ?>
                </div>
                <div style="font-size:11.5px; color:var(--color-ink-mute); margin-top:2px;">
                    <?= $allReconciled ? 'Seluruh saldo fisik akun kas cocok secara matematis dengan buku besar mutasi arus kas.' : 'Terdapat perbedaan antara saldo akun kas dengan akumulasi mutasi transaksi di buku besar.' ?>
                </div>
            </div>
        </div>
        <div>
            <span class="badge <?= $allReconciled ? 'badge-success' : 'badge-danger' ?>" style="font-weight:700; font-size:11.5px; padding:4px 10px;">
                <?= $allReconciled ? '✓ 100% Rekonsil' : 'Butuh Sinkronisasi' ?>
            </span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- SECTION HEADING & FILTER TABS                                             -->
    <!-- ========================================================================= -->
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-1">
        <div class="flex items-center gap-2">
            <h2 style="font-size:15px;font-weight:700;color:var(--color-ink);margin:0;">Daftar Akun Kas &amp; Rekening Bank</h2>
            <span class="badge badge-secondary"><?= count($accounts) ?> Akun Terdaftar</span>
        </div>

        <!-- Filter Tab Kategori Akun -->
        <div style="display:flex; gap:4px; background:var(--color-canvas-soft); padding:3px; border-radius:8px; border:1px solid var(--color-hairline);">
            <button type="button" @click="tabFilter = 'all'" :class="tabFilter === 'all' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'" style="padding:3px 10px; font-size:11.5px;">
                Semua
            </button>
            <button type="button" @click="tabFilter = 'kas_tunai'" :class="tabFilter === 'kas_tunai' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'" style="padding:3px 10px; font-size:11.5px;">
                Kas Tunai
            </button>
            <button type="button" @click="tabFilter = 'bank'" :class="tabFilter === 'bank' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'" style="padding:3px 10px; font-size:11.5px;">
                Bank
            </button>
            <button type="button" @click="tabFilter = 'digital'" :class="tabFilter === 'digital' ? 'btn btn-primary btn-sm' : 'btn btn-ghost btn-sm'" style="padding:3px 10px; font-size:11.5px;">
                QRIS / Digital
            </button>
        </div>
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
                'bank' => 'Rekening Bank (Transfer)',
                'kas_operasional', 'kas_kecil' => 'Kas Operasional (Petty Cash)',
                default => ucfirst(str_replace('_', ' ', $acc['tipe_akun']))
            };

            $tabCategory = $isTunai ? 'kas_tunai' : ($isBank ? 'bank' : 'digital');
        ?>
        <div x-show="tabFilter === 'all' || tabFilter === '<?= $tabCategory ?>'" 
             class="card p-5" 
             style="display:flex;flex-direction:column;justify-content:space-between;gap:14px;background:var(--color-canvas);border:1px solid <?= $acc['is_default_pos'] ? 'rgba(16,185,129,0.5)' : 'var(--color-hairline)' ?>;border-radius:var(--rounded-lg);box-shadow:var(--shadow-1);<?= !$acc['status_aktif'] ? 'opacity:0.65;' : '' ?>">
            
            <!-- Header Kartu -->
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;">
                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                    <div style="width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;background:<?= $badgeBg ?>;color:<?= $badgeColor ?>;">
                        <i data-lucide="<?= $iconName ?>" style="width:20px;height:20px;"></i>
                    </div>
                    <div style="min-width:0;">
                        <div style="display:flex; align-items:center; gap:6px;">
                            <span style="font-weight:800;font-size:14px;color:var(--color-ink);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($acc['nama_akun']) ?>">
                                <?= htmlspecialchars($acc['nama_akun']) ?>
                            </span>
                            <?php if (!$acc['status_aktif']): ?>
                                <span class="badge badge-danger" style="font-size:9.5px; padding:1px 5px;">Nonaktif</span>
                            <?php endif; ?>
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
                    <?php elseif ($acc['status_aktif']): ?>
                        <form action="<?= Router::url('/cash/set-default-pos') ?>" method="POST" style="margin:0;">
                            <input type="hidden" name="id" value="<?= $acc['id'] ?>">
                            <button type="submit" class="btn btn-ghost btn-sm" style="font-size:11px;color:var(--color-ink-mute);padding:3px 8px;border-radius:12px;border:1px dashed var(--color-hairline);" title="Jadikan akun ini sebagai penerima kasir POS">
                                <i data-lucide="star" style="width:12px;height:12px;"></i>
                                <span>Set Default POS</span>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Saldo Saat Ini & Rekonsiliasi Mini Badge -->
            <div style="padding:12px 14px;background:var(--color-canvas-soft);border-radius:var(--rounded-md);border:1px solid var(--color-hairline);">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <span style="font-size:11px;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.05em;font-weight:700;">Saldo Kas Saat Ini</span>
                    <?php if ($acc['is_reconciled']): ?>
                        <span style="font-size:10.5px; font-weight:700; color:#10b981; display:inline-flex; align-items:center; gap:3px;">
                            <i data-lucide="check" style="width:11px; height:11px;"></i> Rekonsil
                        </span>
                    <?php else: ?>
                        <span style="font-size:10.5px; font-weight:700; color:#ef4444; display:inline-flex; align-items:center; gap:3px;" title="Selisih dengan Buku Besar: Rp <?= number_format($acc['selisih_rekonsiliasi'], 2) ?>">
                            <i data-lucide="alert-circle" style="width:11px; height:11px;"></i> Selisih Rp <?= number_format($acc['selisih_rekonsiliasi'], 0, ',', '.') ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div style="font-size:22px;font-weight:900;font-family:var(--font-mono);color:<?= (float)$acc['saldo_saat_ini'] >= 0 ? 'var(--color-ink)' : '#ef4444' ?>;margin-top:2px;white-space:nowrap;">
                    <?= Format::rupiah((float)$acc['saldo_saat_ini']) ?>
                </div>
            </div>

            <!-- Footer Kartu & Aksi Lengkap -->
            <div style="display:flex;align-items:center;justify-content:space-between;padding-top:6px;border-top:1px solid var(--color-hairline);">
                <span style="font-size:11.5px;color:var(--color-ink-mute);">
                    <?= (int)$acc['total_transaksi'] ?> Transaksi
                </span>

                <div style="display:flex;gap:4px;align-items:center;">
                    <!-- Mutasi Link -->
                    <a href="<?= Router::url('/cash/transactions?account_id=' . $acc['id']) ?>" class="btn btn-secondary btn-sm" style="padding:4px 8px;font-size:11.5px;" title="Lihat Mutasi Buku Kas Akun Ini">
                        <i data-lucide="history" style="width:13px;height:13px;"></i>
                        <span>Mutasi</span>
                    </a>

                    <!-- Edit Button -->
                    <button type="button" @click="openEditAccountModal(<?= htmlspecialchars(json_encode($acc)) ?>)" class="btn btn-ghost btn-sm" style="padding:4px 8px;" title="Edit Akun Kas">
                        <i data-lucide="edit-3" style="width:13px;height:13px;"></i>
                    </button>

                    <!-- Delete Button (Hanya jika belum ada transaksi dan bukan default POS) -->
                    <?php if ((int)$acc['total_transaksi'] === 0 && !$acc['is_default_pos']): ?>
                        <button type="button" @click="openDeleteAccountModal(<?= htmlspecialchars(json_encode($acc)) ?>)" class="btn btn-ghost btn-sm text-danger" style="padding:4px 8px; color:#ef4444;" title="Hapus Akun Kosong">
                            <i data-lucide="trash-2" style="width:13px;height:13px;"></i>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL 1: TAMBAH / EDIT AKUN KAS                                           -->
    <!-- ========================================================================= -->
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
                        <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">Saldo awal akan otomatis dicatat sebagai voucher kas masuk modal awal resmi.</span>
                    </div>
                </template>

                <div style="display:flex;flex-direction:column;gap:8px;padding-top:4px;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12.5px;font-weight:600;">
                        <input type="checkbox" name="is_default_pos" x-model="accountForm.is_default_pos" style="width:16px;height:16px;accent-color:var(--color-primary);">
                        <span>Gunakan sebagai Default Kasir POS (Penerimaan Penjualan)</span>
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

    <!-- ========================================================================= -->
    <!-- MODAL 2: KONFIRMASI HAPUS AKUN KAS                                        -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showDeleteModal" x-cloak class="modal-backdrop" @click.self="showDeleteModal = false">
        <div class="modal-box" style="max-width:420px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div style="display:flex; align-items:center; gap:8px;">
                    <div style="width:32px;height:32px;border-radius:var(--rounded-md);background:rgba(239,68,68,0.1);color:#ef4444;display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="alert-triangle" style="width:16px;height:16px;"></i>
                    </div>
                    <div class="modal-title">Hapus Akun Kas</div>
                </div>
                <button type="button" @click="showDeleteModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <p style="font-size:13px; color:var(--color-ink); line-height:1.5;">
                Apakah Anda yakin ingin menghapus akun <strong x-text="deleteAccountData.nama_akun"></strong> secara permanen?
                <br><span style="font-size:11.5px; color:var(--color-ink-mute);">Akun ini belum memiliki transaksi mutasi di buku besar. Tindakan ini tidak dapat dibatalkan.</span>
            </p>

            <form action="<?= Router::url('/cash/delete-account') ?>" method="POST" style="margin-top:16px; display:flex; justify-content:flex-end; gap:8px;">
                <input type="hidden" name="id" :value="deleteAccountData.id">
                <button type="button" @click="showDeleteModal = false" class="btn btn-secondary">Batal</button>
                <button type="submit" class="btn btn-danger" style="background:#ef4444; color:#fff;">
                    <i data-lucide="trash-2"></i>
                    <span>Ya, Hapus Akun</span>
                </button>
            </form>
        </div>
    </div>
    </template>

</div>

<script>
function cashAccountsApp() {
    return {
        tabFilter: 'all',
        showAccountModal: false,
        showDeleteModal: false,
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

        deleteAccountData: {
            id: '',
            nama_akun: ''
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

        openDeleteAccountModal(acc) {
            this.deleteAccountData = {
                id: acc.id,
                nama_akun: acc.nama_akun
            };
            this.showDeleteModal = true;
            this.$nextTick(() => lucide.createIcons());
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

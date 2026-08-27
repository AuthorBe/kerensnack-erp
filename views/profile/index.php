<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div class="space-y-6 max-w-4xl mx-auto">

    <!-- USER OVERVIEW CARD -->
    <div class="card" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="width:50px;height:50px;border-radius:var(--rounded-lg);background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:white;box-shadow:0 4px 12px rgba(99,102,241,0.25);">
                <i data-lucide="user" style="width:24px;height:24px;"></i>
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <h2 style="font-size:16px;font-weight:700;color:var(--color-ink);margin:0;"><?= htmlspecialchars($user['nama_lengkap']) ?></h2>
                    <span class="badge badge-mono">@<?= htmlspecialchars($user['nama_pengguna']) ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:4px;font-size:12px;color:var(--color-ink-mute);">
                    <span class="badge badge-success" style="text-transform:capitalize;"><?= htmlspecialchars($user['peran']) ?></span>
                    <span>•</span>
                    <span>Status: <strong style="color:var(--color-primary-deep);">Aktif</strong></span>
                </div>
            </div>
        </div>

        <div>
            <?php if ($isDeveloper): ?>
            <span class="badge badge-success" style="font-size:12px;padding:4px 10px;">
                <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
                Akses Developer (Tanpa Batas)
            </span>
            <?php else: ?>
            <span class="badge <?= $remainingChanges > 0 ? 'badge-info' : 'badge-warning' ?>" style="font-size:12px;padding:4px 10px;">
                <i data-lucide="clock" style="width:14px;height:14px;"></i>
                Sisa Kuota Ganti Nama: <?= $remainingChanges ?> / <?= $maxMonthlyChanges ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN SECTION -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <!-- FORM 1: UBAH NAMA PENGGUNA (USERNAME) -->
        <div class="card flex flex-col justify-between" style="padding:0;overflow:hidden;">
            <div style="padding:22px 24px 0;">
                <div class="section-header" style="margin-bottom:16px;padding-bottom:12px;">
                    <div>
                        <div class="section-title">Nama Pengguna (Username)</div>
                        <div class="section-subtitle">Kredensial unik untuk masuk ke sistem.</div>
                    </div>
                    <i data-lucide="at-sign" style="width:18px;height:18px;color:var(--color-primary-deep);"></i>
                </div>

                <!-- Info Kuota & Aturan -->
                <div style="padding:10px 12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);margin-bottom:16px;">
                    <div style="display:flex;align-items:flex-start;gap:8px;">
                        <i data-lucide="info" style="width:15px;height:15px;color:var(--color-info);flex-shrink:0;margin-top:1px;"></i>
                        <div style="font-size:11.5px;color:var(--color-ink-secondary);line-height:1.45;">
                            <?php if ($isDeveloper): ?>
                                <strong>Akun Developer:</strong> Anda memiliki kuota tanpa batas untuk mengubah nama pengguna.
                            <?php else: ?>
                                <strong>Ketentuan:</strong> Pengguna non-developer dibatasi maksimal mengganti nama pengguna <strong>2 kali dalam 30 hari</strong> via audit log.
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <form id="form-username" action="<?= Router::url('/profile/update-username') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">

                    <!-- Nama Lengkap (Terkunci) -->
                    <div>
                        <label class="form-label" style="display:flex;align-items:center;justify-content:space-between;">
                            <span>Nama Lengkap</span>
                            <span style="font-size:11px;color:var(--color-ink-mute);display:flex;align-items:center;gap:3px;">
                                <i data-lucide="lock" style="width:12px;height:12px;"></i> Terkunci oleh HRD
                            </span>
                        </label>
                        <input type="text" value="<?= htmlspecialchars($user['nama_lengkap']) ?>" class="form-input font-semibold" disabled style="background-color:var(--color-canvas-soft);color:var(--color-ink-mute);cursor:not-allowed;">
                    </div>

                    <!-- Nama Pengguna Baru -->
                    <div>
                        <label class="form-label" for="new_username">Nama Pengguna Baru</label>
                        <div class="form-input-icon">
                            <i data-lucide="user" class="icon-left"></i>
                            <input type="text"
                                   id="new_username"
                                   name="new_username"
                                   value="<?= htmlspecialchars($user['nama_pengguna']) ?>"
                                   class="form-input font-mono font-bold"
                                   placeholder="nama_pengguna_baru"
                                   required
                                   <?= !$canChangeUsername ? 'disabled' : '' ?>>
                        </div>
                        <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">3-30 karakter huruf kecil, angka, atau underscore.</span>
                    </div>
                </form>
            </div>

            <!-- Card Footer Clean Look -->
            <div style="padding:15px 24px;border-top:1px solid var(--color-hairline);background-color:var(--color-canvas-soft);display:flex;align-items:center;justify-content:space-between;margin-top:20px;">
                <span style="font-size:11.5px;color:var(--color-ink-mute);font-family:var(--font-mono);">
                    <?= $isDeveloper ? 'Unlimited' : "Sisa: {$remainingChanges}/{$maxMonthlyChanges}" ?>
                </span>
                <button type="submit"
                        form="form-username"
                        class="btn btn-primary"
                        style="padding:9px 20px;font-size:13px;font-weight:600;"
                        <?= !$canChangeUsername ? 'disabled' : '' ?>>
                    <i data-lucide="check-circle-2" style="width:16px;height:16px;"></i>
                    Simpan Username
                </button>
            </div>
        </div>

        <!-- FORM 2: UBAH KATA SANDI -->
        <div class="card flex flex-col justify-between" style="padding:0;overflow:hidden;">
            <div style="padding:22px 24px 0;">
                <div class="section-header" style="margin-bottom:16px;padding-bottom:12px;">
                    <div>
                        <div class="section-title">Keamanan &amp; Kata Sandi</div>
                        <div class="section-subtitle">Perbarui kata sandi untuk melindungi akun Anda.</div>
                    </div>
                    <i data-lucide="key" style="width:18px;height:18px;color:var(--color-warning);"></i>
                </div>

                <form id="form-password" action="<?= Router::url('/profile/update-password') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;" x-data="{ showOld: false, showNew: false }">

                    <?php if (!empty($user['has_password']) && !$isDeveloper): ?>
                    <div>
                        <label class="form-label" for="current_password">Kata Sandi Saat Ini</label>
                        <div style="position:relative;">
                            <input :type="showOld ? 'text' : 'password'"
                                   id="current_password"
                                   name="current_password"
                                   class="form-input"
                                   placeholder="••••••••"
                                   required>
                            <button type="button" @click="showOld = !showOld" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:4px;">
                                <i :data-lucide="showOld ? 'eye-off' : 'eye'" style="width:15px;height:15px;"></i>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div>
                        <label class="form-label" for="new_password">Kata Sandi Baru</label>
                        <div style="position:relative;">
                            <input :type="showNew ? 'text' : 'password'"
                                   id="new_password"
                                   name="new_password"
                                   class="form-input"
                                   placeholder="Minimal 6 karakter"
                                   minlength="6"
                                   required>
                            <button type="button" @click="showNew = !showNew" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:4px;">
                                <i :data-lucide="showNew ? 'eye-off' : 'eye'" style="width:15px;height:15px;"></i>
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="form-label" for="confirm_password">Konfirmasi Kata Sandi Baru</label>
                        <input type="password"
                               id="confirm_password"
                               name="confirm_password"
                               class="form-input"
                               placeholder="Ulangi kata sandi baru"
                               minlength="6"
                               required>
                    </div>
                </form>
            </div>

            <!-- Card Footer Clean Look -->
            <div style="padding:15px 24px;border-top:1px solid var(--color-hairline);background-color:var(--color-canvas-soft);display:flex;align-items:center;justify-content:flex-end;margin-top:20px;">
                <button type="submit"
                        form="form-password"
                        class="btn btn-primary"
                        style="padding:9px 20px;font-size:13px;font-weight:600;">
                    <i data-lucide="check-circle-2" style="width:16px;height:16px;"></i>
                    Perbarui Kata Sandi
                </button>
            </div>
        </div>

    </div>

    <!-- AUDIT TRAIL LOG AKUN -->
    <div class="card">
        <div class="section-header" style="margin-bottom:14px;">
            <div>
                <div class="section-title">Log Aktivitas Keamanan Akun</div>
                <div class="section-subtitle">Catatan historis pergantian nama pengguna &amp; kata sandi akun ini.</div>
            </div>
            <span class="badge badge-mono">Realtime Audit</span>
        </div>

        <?php if (empty($auditLogs)): ?>
        <div style="text-align:center;padding:28px;font-size:12.5px;color:var(--color-ink-mute);border:1px dashed var(--color-hairline);border-radius:var(--rounded-md);">
            Belum ada catatan aktivitas perubahan nama atau kata sandi untuk akun ini.
        </div>
        <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:8px;">
            <?php foreach ($auditLogs as $log): ?>
            <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;align-items:center;justify-content:space-between;gap:12px;">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div style="width:28px;height:28px;border-radius:var(--rounded-xs);background:rgba(16,185,129,0.1);color:var(--color-primary-deep);display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="<?= $log['jenis_aksi'] === 'ubah_nama_pengguna' ? 'user-check' : 'key-round' ?>" style="width:14px;height:14px;"></i>
                    </div>
                    <div>
                        <div style="font-size:12.5px;font-weight:600;color:var(--color-ink);"><?= htmlspecialchars($log['deskripsi_aktivitas']) ?></div>
                        <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-ink-mute);">IP: <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></div>
                    </div>
                </div>
                <span style="font-size:11.5px;font-family:var(--font-mono);font-weight:500;color:var(--color-ink-mute);white-space:nowrap;">
                    <?= Format::tanggal($log['waktu_kejadian'], true) ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

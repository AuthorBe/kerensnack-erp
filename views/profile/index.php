<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div class="space-y-6 max-w-4xl mx-auto">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-indigo">
                <i data-lucide="user-cog"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#6366f1;"></span>
                    <span>Pengaturan Akun</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Profil Pengguna' ?></h1>
                <p class="page-subtitle">Kelola identitas akun, ubah username &amp; keamanan kata sandi</p>
            </div>
        </div>
    </div>

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

    <!-- UNIFIED PROFILE & SECURITY FORM -->
    <form id="form-profile" action="<?= Router::url('/profile/update') ?>" method="POST" x-data="{ showOld: false, showNew: false }">
        <?= \App\Helpers\CSRF::field() ?>

        <!-- MAIN TWO-COLUMN SECTION -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- CARD 1: IDENTITAS & NAMA PENGGUNA -->
            <div class="card flex flex-col justify-between" style="padding:0;overflow:hidden;">
                <div style="padding:22px 24px;">
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

                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <!-- Nama Lengkap (Terkunci oleh HRD untuk Karyawan/Non-Developer) -->
                        <div>
                            <label class="form-label" for="nama_lengkap" style="display:flex;align-items:center;justify-content:space-between;">
                                <span>Nama Lengkap</span>
                                <?php if (!$isDeveloper): ?>
                                <span style="font-size:11px;color:var(--color-ink-mute);display:flex;align-items:center;gap:3px;">
                                    <i data-lucide="lock" style="width:12px;height:12px;"></i> Terkunci oleh HRD
                                </span>
                                <?php endif; ?>
                            </label>
                            <div class="form-input-icon">
                                <i data-lucide="<?= $isDeveloper ? 'user-check' : 'lock' ?>" class="icon-left"></i>
                                <input type="text"
                                       id="nama_lengkap"
                                       name="nama_lengkap"
                                       value="<?= htmlspecialchars($user['nama_lengkap']) ?>"
                                       class="form-input font-semibold"
                                       placeholder="Nama Lengkap Anda"
                                       <?= !$isDeveloper ? 'disabled style="background-color:var(--color-canvas-soft);color:var(--color-ink-mute);cursor:not-allowed;"' : 'required' ?>>
                            </div>
                            <?php if (!$isDeveloper): ?>
                            <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">
                                Nama lengkap mengikuti data resmi Master Karyawan yang dikelola oleh HRD.
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Nama Pengguna (Username) -->
                        <div>
                            <label class="form-label" for="new_username">
                                <span>Nama Pengguna (Username)</span>
                                <span style="font-size:11px;color:var(--color-ink-mute);font-weight:normal;">Saat ini: @<?= htmlspecialchars($user['nama_pengguna']) ?></span>
                            </label>
                            <div class="form-input-icon">
                                <i data-lucide="at-sign" class="icon-left"></i>
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
                    </div>
                </div>

                <!-- Card Bottom Status Indicator -->
                <div style="padding:12px 24px;border-top:1px solid var(--color-hairline);background-color:var(--color-canvas-soft);display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:11.5px;color:var(--color-ink-mute);font-family:var(--font-mono);">
                        <?= $isDeveloper ? 'Status Kuota: Unlimited' : "Sisa Kuota Ganti Nama: {$remainingChanges}/{$maxMonthlyChanges}" ?>
                    </span>
                    <span class="badge badge-mono" style="font-size:11px;">@<?= htmlspecialchars($user['nama_pengguna']) ?></span>
                </div>
            </div>

            <!-- CARD 2: KEAMANAN & KATA SANDI -->
            <div class="card flex flex-col justify-between" style="padding:0;overflow:hidden;">
                <div style="padding:22px 24px;">
                    <div class="section-header" style="margin-bottom:16px;padding-bottom:12px;">
                        <div>
                            <div class="section-title">Keamanan &amp; Kata Sandi</div>
                            <div class="section-subtitle">Perbarui kata sandi untuk melindungi akun Anda.</div>
                        </div>
                        <i data-lucide="key" style="width:18px;height:18px;color:var(--color-warning);"></i>
                    </div>

                    <!-- Info Opsional Password -->
                    <div style="padding:10px 12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);margin-bottom:16px;">
                        <div style="display:flex;align-items:flex-start;gap:8px;">
                            <i data-lucide="shield" style="width:15px;height:15px;color:var(--color-warning);flex-shrink:0;margin-top:1px;"></i>
                            <div style="font-size:11.5px;color:var(--color-ink-secondary);line-height:1.45;">
                                Kosongkan form kata sandi di bawah jika Anda <strong>tidak ingin</strong> mengubah kata sandi saat ini.
                            </div>
                        </div>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:14px;">
                        <?php if (!empty($user['has_password']) && !$isDeveloper): ?>
                        <div>
                            <label class="form-label" for="current_password">Kata Sandi Saat Ini</label>
                            <div style="position:relative;">
                                <input :type="showOld ? 'text' : 'password'"
                                       id="current_password"
                                       name="current_password"
                                       class="form-input"
                                       placeholder="Wajib jika ubah password">
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
                                       placeholder="Kosongkan jika tidak diubah"
                                       minlength="6">
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
                                   minlength="6">
                        </div>
                    </div>
                </div>

                <!-- Card Bottom Security Indicator -->
                <div style="padding:12px 24px;border-top:1px solid var(--color-hairline);background-color:var(--color-canvas-soft);display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-size:11.5px;color:var(--color-ink-mute);">
                        Enkripsi Sandi: <strong style="color:var(--color-ink);">Bcrypt Standard</strong>
                    </span>
                    <span class="badge badge-success" style="font-size:11px;">Aktif</span>
                </div>
            </div>

        </div>

        <!-- UNIFIED SINGLE SAVE ACTION BAR -->
        <div class="card" style="margin-top:24px;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;background-color:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
            <div style="display:flex;align-items:center;gap:12px;">
                <div style="width:38px;height:38px;border-radius:var(--rounded-md);background:rgba(99,102,241,0.12);color:var(--color-primary-deep);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <i data-lucide="check-circle-2" style="width:20px;height:20px;"></i>
                </div>
                <div>
                    <div style="font-size:13.5px;font-weight:600;color:var(--color-ink);">Pembaruan Pengaturan Akun</div>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);">Semua perubahan identitas, nama pengguna, dan kata sandi disimpan sekaligus dalam 1 klik.</div>
                </div>
            </div>
            <button type="submit"
                    class="btn btn-primary"
                    style="padding:10px 26px;font-size:13.5px;font-weight:600;display:inline-flex;align-items:center;gap:8px;box-shadow:0 2px 8px rgba(99,102,241,0.25);"
                    <?= !$canChangeUsername ? 'disabled' : '' ?>>
                <i data-lucide="save" style="width:16px;height:16px;"></i>
                Simpan Perubahan
            </button>
        </div>
    </form>

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

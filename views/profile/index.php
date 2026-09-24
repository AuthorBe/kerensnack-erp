<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
$initialTab = $activeTab ?? 'karyawan';
?>

<div class="space-y-6 max-w-4xl mx-auto" x-data="{ activeTab: '<?= htmlspecialchars($initialTab) ?>' }">

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
                    <span>Pengaturan Akun &amp; Karyawan</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Profil &amp; Akun Pengguna' ?></h1>
                <p class="page-subtitle">Kelola biodata kepegawaian, kontak WhatsApp, rekening payroll, serta kredensial akun</p>
            </div>
        </div>
    </div>

    <!-- USER OVERVIEW CARD -->
    <div class="card" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:16px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <div style="width:52px;height:52px;border-radius:var(--rounded-lg);background:linear-gradient(135deg,#6366f1,#8b5cf6);display:flex;align-items:center;justify-content:center;color:white;box-shadow:0 4px 12px rgba(99,102,241,0.25);flex-shrink:0;">
                <i data-lucide="user" style="width:26px;height:26px;"></i>
            </div>
            <div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <h2 style="font-size:16px;font-weight:700;color:var(--color-ink);margin:0;">
                        <?= htmlspecialchars($user['nama_lengkap']) ?>
                        <?php if (!empty($user['nama_panggilan'])): ?>
                            <span style="font-size:13.5px;font-weight:500;color:var(--color-ink-mute);">(<?= htmlspecialchars($user['nama_panggilan']) ?>)</span>
                        <?php endif; ?>
                    </h2>
                    <span class="badge badge-mono">@<?= htmlspecialchars($user['nama_pengguna']) ?></span>
                </div>
                <div style="display:flex;align-items:center;gap:8px;margin-top:4px;font-size:12px;color:var(--color-ink-mute);flex-wrap:wrap;">
                    <span class="badge badge-success" style="text-transform:capitalize;"><?= htmlspecialchars($user['peran'] ?? 'Pengguna') ?></span>
                    <?php if (!empty($user['posisi'])): ?>
                    <span>•</span>
                    <span class="badge badge-info" style="text-transform:capitalize;">Posisi: <?= htmlspecialchars($user['posisi']) ?></span>
                    <?php endif; ?>
                    <span>•</span>
                    <span>Status: <strong style="color:var(--color-primary-deep);">Aktif</strong></span>
                </div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
            <?php if ($isDeveloper): ?>
            <span class="badge badge-success" style="font-size:12px;padding:5px 12px;">
                <i data-lucide="shield-check" style="width:14px;height:14px;"></i>
                Akses Developer (Bebas Batas)
            </span>
            <?php else: ?>
            <span class="badge <?= $canChangeName ? 'badge-info' : 'badge-warning' ?>" style="font-size:12px;padding:5px 12px;">
                <i data-lucide="<?= $canChangeName ? 'user-check' : 'lock' ?>" style="width:14px;height:14px;"></i>
                <?= $canChangeName ? 'Ganti Nama: Tersedia (1x / 3 bln)' : 'Nama Terkunci s/d ' . ($nextAllowedNameDate ?? '-') ?>
            </span>
            <span class="badge <?= $remainingChanges > 0 ? 'badge-info' : 'badge-warning' ?>" style="font-size:12px;padding:5px 12px;">
                <i data-lucide="clock" style="width:14px;height:14px;"></i>
                Username: <?= $remainingChanges ?>/<?= $maxMonthlyChanges ?>
            </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB NAVIGATION                                                            -->
    <!-- ========================================================================= -->
    <div style="display:flex;gap:8px;border-bottom:2px solid var(--color-hairline);padding-bottom:2px;overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <button type="button"
                @click="activeTab = 'karyawan'; $nextTick(() => window.lucide && lucide.createIcons());"
                :class="activeTab === 'karyawan' ? 'tab-btn-active' : 'tab-btn-inactive'"
                style="padding:10px 18px;font-size:13.5px;font-weight:600;display:inline-flex;align-items:center;gap:8px;border-radius:var(--rounded-md) var(--rounded-md) 0 0;cursor:pointer;transition:all 0.2s;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-4px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="contact-2" style="width:17px;height:17px;"></i>
            <span>Data Profil &amp; Karyawan</span>
        </button>

        <button type="button"
                @click="activeTab = 'keamanan'; $nextTick(() => window.lucide && lucide.createIcons());"
                :class="activeTab === 'keamanan' ? 'tab-btn-active' : 'tab-btn-inactive'"
                style="padding:10px 18px;font-size:13.5px;font-weight:600;display:inline-flex;align-items:center;gap:8px;border-radius:var(--rounded-md) var(--rounded-md) 0 0;cursor:pointer;transition:all 0.2s;background:none;border:none;border-bottom:2px solid transparent;margin-bottom:-4px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="shield-check" style="width:17px;height:17px;"></i>
            <span>Akun &amp; Keamanan</span>
        </button>
    </div>

    <style>
        .tab-btn-active {
            color: var(--color-primary-deep) !important;
            border-bottom: 2px solid var(--color-primary-deep) !important;
            background-color: var(--color-canvas-soft) !important;
        }
        .tab-btn-inactive {
            color: var(--color-ink-mute) !important;
        }
        .tab-btn-inactive:hover {
            color: var(--color-ink) !important;
            background-color: var(--color-canvas-soft) !important;
        }
    </style>

    <!-- ========================================================================= -->
    <!-- TAB 1: DATA PROFIL & KARYAWAN                                            -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'karyawan'" x-cloak class="space-y-6">
        <form id="form-employee-profile" action="<?= Router::url('/profile/update-employee') ?>" method="POST">
            <?= \App\Helpers\CSRF::field() ?>

            <div class="space-y-6">

                <!-- SECTION 1: BIODATA NAMA KARYAWAN -->
                <div class="card" style="padding:22px 24px;">
                    <div class="section-header" style="margin-bottom:16px;padding-bottom:12px;">
                        <div>
                            <div class="section-title">Biodata &amp; Identitas Karyawan</div>
                            <div class="section-subtitle">Nama lengkap resmi dan nama panggilan kerja. Keduanya wajib diisi.</div>
                        </div>
                        <i data-lucide="user-check" style="width:18px;height:18px;color:var(--color-primary-deep);"></i>
                    </div>

                    <!-- Info Ketentuan Kuota Ganti Nama (1x / 3 Bulan) -->
                    <div style="padding:10px 12px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);margin-bottom:16px;">
                        <div style="display:flex;align-items:flex-start;gap:8px;">
                            <i data-lucide="<?= $canChangeName ? 'info' : 'clock' ?>" style="width:15px;height:15px;color:<?= $canChangeName ? 'var(--color-info)' : 'var(--color-warning)' ?>;flex-shrink:0;margin-top:1px;"></i>
                            <div style="font-size:11.5px;color:var(--color-ink-secondary);line-height:1.45;">
                                <?php if ($isDeveloper): ?>
                                    <strong>Akun Developer:</strong> Anda memiliki kuota tanpa batas untuk memperbarui nama lengkap dan panggilan.
                                <?php elseif ($canChangeName): ?>
                                    <strong>Ketentuan Kuota:</strong> Nama Lengkap dan Nama Panggilan dapat diubah maksimal <strong>1 kali dalam 3 bulan (90 hari)</strong>. Pastikan penulisan nama sudah benar dan sesuai KTP.
                                <?php else: ?>
                                    <strong style="color:var(--color-warning);">Batas Kuota Tercapai:</strong> Anda telah memperbarui nama dalam 90 hari terakhir. Bidang nama terkunci dan baru dapat diubah kembali pada <strong><?= htmlspecialchars($nextAllowedNameDate ?? '-') ?></strong>.
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Nama Lengkap -->
                        <div>
                            <label class="form-label" for="nama_lengkap" style="display:flex;align-items:center;justify-content:space-between;">
                                <span>Nama Lengkap <span style="color:var(--color-danger);">*</span></span>
                                <?php if (!$canChangeName): ?>
                                <span style="font-size:11px;color:var(--color-warning);display:flex;align-items:center;gap:3px;">
                                    <i data-lucide="lock" style="width:12px;height:12px;"></i> Terkunci (1x / 3 bln)
                                </span>
                                <?php endif; ?>
                            </label>
                            <div class="form-input-icon">
                                <i data-lucide="user" class="icon-left"></i>
                                <input type="text"
                                       id="nama_lengkap"
                                       name="nama_lengkap"
                                       value="<?= htmlspecialchars($user['nama_lengkap']) ?>"
                                       class="form-input font-semibold"
                                       placeholder="Nama Lengkap Sesuai KTP"
                                       required
                                       <?= !$canChangeName ? 'readonly style="background-color:var(--color-canvas-soft);color:var(--color-ink-mute);cursor:not-allowed;"' : '' ?>>
                            </div>
                            <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">Wajib diisi sesuai dokumen identitas resmi KTP.</span>
                        </div>

                        <!-- Nama Panggilan -->
                        <div>
                            <label class="form-label" for="nama_panggilan" style="display:flex;align-items:center;justify-content:space-between;">
                                <span>Nama Panggilan <span style="color:var(--color-danger);">*</span></span>
                                <?php if (!$canChangeName): ?>
                                <span style="font-size:11px;color:var(--color-warning);display:flex;align-items:center;gap:3px;">
                                    <i data-lucide="lock" style="width:12px;height:12px;"></i> Terkunci (1x / 3 bln)
                                </span>
                                <?php endif; ?>
                            </label>
                            <div class="form-input-icon">
                                <i data-lucide="smile" class="icon-left"></i>
                                <input type="text"
                                       id="nama_panggilan"
                                       name="nama_panggilan"
                                       value="<?= htmlspecialchars($user['nama_panggilan'] ?? '') ?>"
                                       class="form-input font-semibold"
                                       placeholder="Nama Panggilan Kerja"
                                       required
                                       <?= !$canChangeName ? 'readonly style="background-color:var(--color-canvas-soft);color:var(--color-ink-mute);cursor:not-allowed;"' : '' ?>>
                            </div>
                            <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">Nama akrab yang digunakan dalam komunikasi harian tim operasional.</span>
                        </div>
                    </div>
                </div>

                <!-- SECTION 2: IDENTITAS RESMI KEPEGAWAIAN (LOCKED BY HRD) -->
                <div class="card" style="padding:22px 24px;">
                    <div class="section-header" style="margin-bottom:16px;padding-bottom:12px;">
                        <div>
                            <div class="section-title">Data Resmi Kepegawaian</div>
                            <div class="section-subtitle">Data resmi terdaftar di Master Karyawan. Dikelola oleh HRD &amp; Manajemen.</div>
                        </div>
                        <span class="badge badge-warning" style="display:inline-flex;align-items:center;gap:4px;font-size:11px;">
                            <i data-lucide="lock" style="width:12px;height:12px;"></i> Terkunci oleh HRD
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <!-- NIK -->
                        <div>
                            <label class="form-label" style="font-size:12px;">
                                <span>Nomor Induk Kependudukan (NIK)</span>
                            </label>
                            <div class="form-input-icon">
                                <i data-lucide="id-card" class="icon-left"></i>
                                <input type="text"
                                       value="<?= htmlspecialchars($user['nik'] ?? ($user['nik_pending'] ? 'Menunggu KTP (Pending)' : 'Belum diisi')) ?>"
                                       class="form-input font-mono"
                                       disabled
                                       style="background-color:var(--color-canvas-soft);color:var(--color-ink-mute);cursor:not-allowed;">
                            </div>
                        </div>

                        <!-- Posisi / Divisi -->
                        <div>
                            <label class="form-label" style="font-size:12px;">
                                <span>Posisi / Divisi</span>
                            </label>
                            <div class="form-input-icon">
                                <i data-lucide="briefcase" class="icon-left"></i>
                                <input type="text"
                                       value="<?= htmlspecialchars(ucfirst($user['posisi'] ?? '-')) ?>"
                                       class="form-input font-semibold"
                                       disabled
                                       style="background-color:var(--color-canvas-soft);color:var(--color-ink-mute);cursor:not-allowed;text-transform:capitalize;">
                            </div>
                        </div>

                        <!-- Tipe Penggajian -->
                        <div>
                            <label class="form-label" style="font-size:12px;">
                                <span>Tipe Penggajian</span>
                            </label>
                            <div class="form-input-icon">
                                <i data-lucide="banknote" class="icon-left"></i>
                                <input type="text"
                                       value="<?= htmlspecialchars(ucfirst($user['tipe_penggajian'] ?? 'Bulanan')) ?>"
                                       class="form-input"
                                       disabled
                                       style="background-color:var(--color-canvas-soft);color:var(--color-ink-mute);cursor:not-allowed;text-transform:capitalize;">
                            </div>
                        </div>

                        <!-- Tanggal Bergabung -->
                        <div>
                            <label class="form-label" style="font-size:12px;">
                                <span>Tanggal Bergabung</span>
                            </label>
                            <div class="form-input-icon">
                                <i data-lucide="calendar" class="icon-left"></i>
                                <input type="text"
                                       value="<?= !empty($user['tanggal_bergabung']) ? Format::tanggal($user['tanggal_bergabung'], false) : '-' ?>"
                                       class="form-input"
                                       disabled
                                       style="background-color:var(--color-canvas-soft);color:var(--color-ink-mute);cursor:not-allowed;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION 3 & 4: GRID FORM EDITABLE -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <!-- CARD: KONTAK & DOMISILI -->
                    <div class="card flex flex-col justify-between" style="padding:0;overflow:hidden;">
                        <div style="padding:22px 24px;">
                            <div class="section-header" style="margin-bottom:16px;padding-bottom:12px;">
                                <div>
                                    <div class="section-title">Informasi Domisili &amp; Kontak</div>
                                    <div class="section-subtitle">Alamat tempat tinggal dan nomor kontak aktif Anda.</div>
                                </div>
                                <i data-lucide="map-pin" style="width:18px;height:18px;color:var(--color-primary-deep);"></i>
                            </div>

                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <!-- Alamat Lengkap -->
                                <div>
                                    <label class="form-label" for="alamat">
                                        <span>Alamat Domisili Lengkap</span>
                                    </label>
                                    <textarea id="alamat"
                                              name="alamat"
                                              class="form-input"
                                              rows="3"
                                              placeholder="Jl. Nama Jalan, No. Rumah, RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten"><?= htmlspecialchars($user['alamat'] ?? '') ?></textarea>
                                    <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">Digunakan untuk administrasi surat dinas dan keperluan operasional.</span>
                                </div>

                                <!-- Nomor WhatsApp -->
                                <div>
                                    <label class="form-label" for="nomor_whatsapp">
                                        <span>Nomor WhatsApp Aktif</span>
                                    </label>
                                    <div class="form-input-icon">
                                        <i data-lucide="message-square" class="icon-left"></i>
                                        <input type="text"
                                               id="nomor_whatsapp"
                                               name="nomor_whatsapp"
                                               value="<?= htmlspecialchars($user['nomor_whatsapp'] ?? '') ?>"
                                               class="form-input font-mono"
                                               placeholder="081234567890">
                                    </div>
                                    <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">Digunakan untuk pengiriman notifikasi slip gaji / tugas logistik.</span>
                                </div>

                                <!-- ID Telegram -->
                                <div>
                                    <label class="form-label" for="id_telegram">
                                        <span>ID Chat Telegram (Opsional)</span>
                                    </label>
                                    <div class="form-input-icon">
                                        <i data-lucide="send" class="icon-left"></i>
                                        <input type="text"
                                               id="id_telegram"
                                               name="id_telegram"
                                               value="<?= htmlspecialchars((string)($user['id_telegram'] ?? '')) ?>"
                                               class="form-input font-mono"
                                               placeholder="Contoh: 123456789">
                                    </div>
                                    <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">Hubungkan dengan Bot Telegram KEREN Snack untuk notifikasi pesanan.</span>
                                </div>
                            </div>
                        </div>

                        <div style="padding:12px 24px;border-top:1px solid var(--color-hairline);background-color:var(--color-canvas-soft);display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:11.5px;color:var(--color-ink-mute);">Kontak &amp; Notifikasi</span>
                            <span class="badge badge-mono" style="font-size:11px;">WA: <?= htmlspecialchars($user['nomor_whatsapp'] ?: '-') ?></span>
                        </div>
                    </div>

                    <!-- CARD: FINANSIAL & OPERASIONAL KENDARAAN -->
                    <div class="card flex flex-col justify-between" style="padding:0;overflow:hidden;">
                        <div style="padding:22px 24px;">
                            <div class="section-header" style="margin-bottom:16px;padding-bottom:12px;">
                                <div>
                                    <div class="section-title">Finansial &amp; Operasional Kendaraan</div>
                                    <div class="section-subtitle">Data rekening transfer payroll &amp; plat kendaraan kerja.</div>
                                </div>
                                <i data-lucide="credit-card" style="width:18px;height:18px;color:var(--color-success);"></i>
                            </div>

                            <div style="display:flex;flex-direction:column;gap:14px;">
                                <!-- Nama Bank -->
                                <div>
                                    <label class="form-label" for="bank_nama">
                                        <span>Nama Bank Penerima Payroll</span>
                                    </label>
                                    <div class="form-input-icon">
                                        <i data-lucide="building" class="icon-left"></i>
                                        <input type="text"
                                               id="bank_nama"
                                               name="bank_nama"
                                               value="<?= htmlspecialchars($user['bank_nama'] ?? '') ?>"
                                               class="form-input font-semibold"
                                               placeholder="BCA, Mandiri, BRI, BNI, BSI, dll.">
                                    </div>
                                </div>

                                <!-- Nomor Rekening -->
                                <div>
                                    <label class="form-label" for="bank_nomor_rekening">
                                        <span>Nomor Rekening Bank</span>
                                    </label>
                                    <div class="form-input-icon">
                                        <i data-lucide="hash" class="icon-left"></i>
                                        <input type="text"
                                               id="bank_nomor_rekening"
                                               name="bank_nomor_rekening"
                                               value="<?= htmlspecialchars($user['bank_nomor_rekening'] ?? '') ?>"
                                               class="form-input font-mono font-bold"
                                               placeholder="1234567890">
                                    </div>
                                </div>

                                <!-- Atas Nama Rekening -->
                                <div>
                                    <label class="form-label" for="bank_atas_nama">
                                        <span>Rekening Atas Nama (A/N)</span>
                                    </label>
                                    <div class="form-input-icon">
                                        <i data-lucide="user-check" class="icon-left"></i>
                                        <input type="text"
                                               id="bank_atas_nama"
                                               name="bank_atas_nama"
                                               value="<?= htmlspecialchars($user['bank_atas_nama'] ?? '') ?>"
                                               class="form-input font-semibold"
                                               placeholder="Nama pemilik buku rekening">
                                    </div>
                                    <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">Pastikan nama sama persis dengan yang tertera di buku tabungan/m-Banking.</span>
                                </div>

                                <!-- Nomor Polisi Kendaraan -->
                                <div>
                                    <label class="form-label" for="nomor_polisi_kendaraan">
                                        <span>Nomor Polisi Kendaraan (Plat Nopol)</span>
                                    </label>
                                    <div class="form-input-icon">
                                        <i data-lucide="truck" class="icon-left"></i>
                                        <input type="text"
                                               id="nomor_polisi_kendaraan"
                                               name="nomor_polisi_kendaraan"
                                               value="<?= htmlspecialchars($user['nomor_polisi_kendaraan'] ?? '') ?>"
                                               class="form-input font-mono font-bold"
                                               placeholder="B 1234 ABC"
                                               style="text-transform:uppercase;">
                                    </div>
                                    <span style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:block;">Khusus staf Driver / Sales / Operasional distribusi armada.</span>
                                </div>
                            </div>
                        </div>

                        <div style="padding:12px 24px;border-top:1px solid var(--color-hairline);background-color:var(--color-canvas-soft);display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-size:11.5px;color:var(--color-ink-mute);">Status Payroll: <strong style="color:var(--color-ink);"><?= htmlspecialchars($user['bank_nama'] ?: 'Belum diisi') ?></strong></span>
                            <span class="badge badge-success" style="font-size:11px;">Payroll Terhubung</span>
                        </div>
                    </div>

                </div>

                <!-- SINGLE SAVE ACTION BAR FOR EMPLOYEE PROFILE -->
                <div class="card" style="padding:16px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;background-color:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:38px;height:38px;border-radius:var(--rounded-md);background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="check-circle-2" style="width:20px;height:20px;"></i>
                        </div>
                        <div>
                            <div style="font-size:13.5px;font-weight:600;color:var(--color-ink);">Simpan Pembaruan Data Karyawan</div>
                            <div style="font-size:11.5px;color:var(--color-ink-mute);">Perubahan nama, alamat, WhatsApp, rekening bank, dan nopol akan langsung diperbarui.</div>
                        </div>
                    </div>
                    <button type="submit"
                            class="btn btn-primary"
                            style="padding:10px 26px;font-size:13.5px;font-weight:600;display:inline-flex;align-items:center;gap:8px;box-shadow:0 2px 8px rgba(99,102,241,0.25);">
                        <i data-lucide="save" style="width:16px;height:16px;"></i>
                        Simpan Data Karyawan
                    </button>
                </div>

            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: AKUN & KEAMANAN                                                    -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'keamanan'" x-cloak class="space-y-6">
        <form id="form-account-security" action="<?= Router::url('/profile/update') ?>" method="POST" x-data="{ showOld: false, showNew: false, showConfirm: false }">
            <?= \App\Helpers\CSRF::field() ?>

            <!-- MAIN TWO-COLUMN SECTION -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <!-- CARD 1: IDENTITAS NAMA PENGGUNA (USERNAME) -->
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
                            <?= $isDeveloper ? 'Status Kuota: Unlimited' : "Sisa Kuota Ganti Username: {$remainingChanges}/{$maxMonthlyChanges}" ?>
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
                                <div style="position:relative;display:flex;align-items:center;">
                                    <input :type="showOld ? 'text' : 'password'"
                                           id="current_password"
                                           name="current_password"
                                           class="form-input"
                                           placeholder="Wajib jika ubah password"
                                           autocomplete="current-password"
                                           style="padding-right:42px;">
                                    <button type="button" @click="showOld = !showOld" :title="showOld ? 'Sembunyikan Kata Sandi' : 'Lihat Kata Sandi'"
                                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:6px;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                        <span x-show="!showOld" style="display:flex;align-items:center;"><i data-lucide="eye" style="width:16px;height:16px;"></i></span>
                                        <span x-show="showOld" style="display:flex;align-items:center;" x-cloak><i data-lucide="eye-off" style="width:16px;height:16px;"></i></span>
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div>
                                <label class="form-label" for="new_password">Kata Sandi Baru</label>
                                <div style="position:relative;display:flex;align-items:center;">
                                    <input :type="showNew ? 'text' : 'password'"
                                           id="new_password"
                                           name="new_password"
                                           class="form-input"
                                           placeholder="Kosongkan jika tidak diubah"
                                           autocomplete="new-password"
                                           minlength="6"
                                           style="padding-right:42px;">
                                    <button type="button" @click="showNew = !showNew" :title="showNew ? 'Sembunyikan Kata Sandi' : 'Lihat Kata Sandi'"
                                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:6px;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                        <span x-show="!showNew" style="display:flex;align-items:center;"><i data-lucide="eye" style="width:16px;height:16px;"></i></span>
                                        <span x-show="showNew" style="display:flex;align-items:center;" x-cloak><i data-lucide="eye-off" style="width:16px;height:16px;"></i></span>
                                    </button>
                                </div>
                            </div>

                            <div>
                                <label class="form-label" for="confirm_password">Konfirmasi Kata Sandi Baru</label>
                                <div style="position:relative;display:flex;align-items:center;">
                                    <input :type="showConfirm ? 'text' : 'password'"
                                           id="confirm_password"
                                           name="confirm_password"
                                           class="form-input"
                                           placeholder="Ulangi kata sandi baru"
                                           autocomplete="new-password"
                                           minlength="6"
                                           style="padding-right:42px;">
                                    <button type="button" @click="showConfirm = !showConfirm" :title="showConfirm ? 'Sembunyikan Kata Sandi' : 'Lihat Kata Sandi'"
                                            style="position:absolute;right:8px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--color-ink-mute);cursor:pointer;padding:6px;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                        <span x-show="!showConfirm" style="display:flex;align-items:center;"><i data-lucide="eye" style="width:16px;height:16px;"></i></span>
                                        <span x-show="showConfirm" style="display:flex;align-items:center;" x-cloak><i data-lucide="eye-off" style="width:16px;height:16px;"></i></span>
                                    </button>
                                </div>
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

            <!-- ACTION BAR FOR ACCOUNT & SECURITY -->
            <div class="card" style="margin-top:24px;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px;background-color:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                <div style="display:flex;align-items:center;gap:12px;">
                    <div style="width:38px;height:38px;border-radius:var(--rounded-md);background:rgba(99,102,241,0.12);color:var(--color-primary-deep);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="shield-check" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <div style="font-size:13.5px;font-weight:600;color:var(--color-ink);">Pembaruan Pengaturan Akun</div>
                        <div style="font-size:11.5px;color:var(--color-ink-mute);">Perubahan nama pengguna dan kata sandi disimpan sekaligus dalam 1 klik.</div>
                    </div>
                </div>
                <button type="submit"
                        class="btn btn-primary"
                        style="padding:10px 26px;font-size:13.5px;font-weight:600;display:inline-flex;align-items:center;gap:8px;box-shadow:0 2px 8px rgba(99,102,241,0.25);"
                        <?= !$canChangeUsername ? 'disabled' : '' ?>>
                    <i data-lucide="save" style="width:16px;height:16px;"></i>
                    Simpan Pengaturan Akun
                </button>
            </div>
        </form>

        <!-- AUDIT TRAIL LOG AKUN -->
        <div class="card">
            <div class="section-header" style="margin-bottom:14px;">
                <div>
                    <div class="section-title">Log Aktivitas Keamanan &amp; Profil Akun</div>
                    <div class="section-subtitle">Catatan historis pergantian nama pengguna, kata sandi, dan data profil akun ini.</div>
                </div>
                <span class="badge badge-mono">Realtime Audit</span>
            </div>

            <?php if (empty($auditLogs)): ?>
            <div style="text-align:center;padding:28px;font-size:12.5px;color:var(--color-ink-mute);border:1px dashed var(--color-hairline);border-radius:var(--rounded-md);">
                Belum ada catatan aktivitas perubahan nama, kata sandi, atau profil untuk akun ini.
            </div>
            <?php else: ?>
            <div style="display:flex;flex-direction:column;gap:8px;">
                <?php foreach ($auditLogs as $log): ?>
                <?php 
                    $badgeStyle = 'background:rgba(99,102,241,0.12);color:var(--color-primary-deep);';
                    $iconName = 'contact-2';
                    if ($log['jenis_aksi'] === 'ubah_nama_pengguna') {
                        $badgeStyle = 'background:rgba(99,102,241,0.12);color:var(--color-primary-deep);';
                        $iconName = 'at-sign';
                    } elseif ($log['jenis_aksi'] === 'ubah_kata_sandi') {
                        $badgeStyle = 'background:rgba(245,158,11,0.12);color:var(--color-warning);';
                        $iconName = 'key-round';
                    } elseif ($log['jenis_aksi'] === 'ubah_nama_karyawan') {
                        $badgeStyle = 'background:rgba(16,185,129,0.12);color:var(--color-success);';
                        $iconName = 'user-check';
                    }
                ?>
                <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div style="display:flex;align-items:flex-start;gap:12px;min-width:0;flex:1;">
                        <div style="width:32px;height:32px;min-width:32px;min-height:32px;aspect-ratio:1/1;border-radius:var(--rounded-md);<?= $badgeStyle ?>display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:1px;">
                            <i data-lucide="<?= $iconName ?>" style="width:16px;height:16px;flex-shrink:0;"></i>
                        </div>
                        <div style="min-width:0;flex:1;">
                            <div style="font-size:12.5px;font-weight:600;color:var(--color-ink);line-height:1.45;word-break:break-word;"><?= htmlspecialchars($log['deskripsi_aktivitas']) ?></div>
                            <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-ink-mute);margin-top:2px;">IP: <?= htmlspecialchars($log['ip_address'] ?? '127.0.0.1') ?></div>
                        </div>
                    </div>
                    <span style="font-size:11px;font-family:var(--font-mono);font-weight:500;color:var(--color-ink-mute);white-space:nowrap;flex-shrink:0;text-align:right;margin-top:2px;">
                        <?= Format::tanggal($log['waktu_kejadian'], true) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

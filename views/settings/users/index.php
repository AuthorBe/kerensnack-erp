<?php
use App\Core\Router;
use App\Core\Auth;
use App\Helpers\Format;
ob_start();
?>

<div class="space-y-4 sm:space-y-6 pb-20 max-w-7xl mx-auto" 
     x-data="{
         addModalOpen: false,
         editModalOpen: false,
         editUser: {
             id: '',
             nama_lengkap: '',
             nama_pengguna: '',
             peran_id: '',
             karyawan_id: '',
             nomor_whatsapp: '',
             id_telegram: '',
             status_aktif: true,
             is_developer: false
         },
         openEdit(user) {
             this.editUser = {
                 id: user.id || '',
                 nama_lengkap: user.nama_lengkap || '',
                 nama_pengguna: user.nama_pengguna || '',
                 peran_id: user.peran_id || '',
                 karyawan_id: user.karyawan_id || '',
                 nomor_whatsapp: user.nomor_whatsapp || '',
                 id_telegram: user.id_telegram || '',
                 status_aktif: user.status_aktif ? true : false,
                 is_developer: user.peran === 'developer'
             };
             this.editModalOpen = true;
         }
     }">

    <!-- ========================================================================= -->
    <!-- TOP TOOLBAR & QUICK ACTIONS                                              -->
    <!-- ========================================================================= -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <a href="<?= Router::url('/settings') ?>" 
               class="btn btn-secondary btn-sm" 
               style="font-weight:700;font-size:12px;padding:7px 12px;border-radius:10px;display:inline-flex;align-items:center;gap:6px;text-decoration:none;"
               title="Kembali ke Portal Pengaturan">
                <i data-lucide="arrow-left" style="width:14px;height:14px;"></i>
                <span>Pengaturan</span>
            </a>
            <span style="color:var(--color-ink-mute);font-size:12px;">•</span>
            <span style="font-size:12.5px;font-weight:700;color:var(--color-ink);">Total <?= count($users) ?> Pengguna</span>
        </div>

        <div class="flex items-center gap-2.5">
            <a href="<?= Router::url('/permissions') ?>" 
               class="btn btn-secondary btn-sm"
               style="font-weight:700;font-size:12px;padding:7px 13px;border-radius:10px;display:inline-flex;align-items:center;gap:6px;text-decoration:none;">
                <i data-lucide="shield-check" style="width:14px;height:14px;color:#f97316;"></i>
                <span>Manajemen Izin</span>
            </a>

            <?php if ($canManage): ?>
                <button type="button" 
                        @click="addModalOpen = true" 
                        class="btn btn-primary btn-sm"
                        style="font-weight:700;font-size:12px;padding:7px 14px;border-radius:10px;display:inline-flex;align-items:center;gap:6px;">
                    <i data-lucide="user-plus" style="width:14px;height:14px;"></i>
                    <span>Tambah Pengguna</span>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- FILTER BAR                                                                -->
    <!-- ========================================================================= -->
    <div class="card p-3.5 sm:p-4 rounded-xl shadow-sm" style="border:1px solid var(--color-hairline);background:var(--color-canvas);">
        <form action="<?= Router::url('/users') ?>" method="GET" class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
            <div class="relative flex-1 min-w-[220px]">
                <i data-lucide="search" style="width:15px;height:15px;color:var(--color-ink-mute);position:absolute;left:12px;top:50%;transform:translateY(-50%);pointer-events:none;"></i>
                <input type="text" 
                       name="search" 
                       value="<?= htmlspecialchars($search) ?>" 
                       placeholder="Cari nama lengkap, username, atau karyawan..." 
                       style="width:100%;padding:8px 12px 8px 36px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);background:var(--color-canvas-soft);font-size:12.5px;color:var(--color-ink);outline:none;">
            </div>

            <div class="flex items-center gap-2 flex-wrap">
                <select name="role_filter" style="padding:8px 12px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);background:var(--color-canvas-soft);font-size:12px;color:var(--color-ink);outline:none;">
                    <option value="">Semua Peran</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?= htmlspecialchars($r['nama_peran']) ?>" <?= ($roleFilter === $r['nama_peran']) ? 'selected' : '' ?>>
                            Role: <?= htmlspecialchars(ucfirst($r['nama_peran'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="status_filter" style="padding:8px 12px;border:1px solid var(--color-hairline);border-radius:var(--rounded-md);background:var(--color-canvas-soft);font-size:12px;color:var(--color-ink);outline:none;">
                    <option value="">Semua Status</option>
                    <option value="1" <?= ($statusFilter === '1' || $statusFilter === 'true') ? 'selected' : '' ?>>Aktif</option>
                    <option value="0" <?= ($statusFilter === '0' || $statusFilter === 'false') ? 'selected' : '' ?>>Nonaktif / Suspend</option>
                </select>

                <button type="submit" class="btn btn-secondary btn-sm" style="font-weight:700;font-size:12px;padding:8px 14px;">
                    Filter
                </button>

                <?php if ($search !== '' || $roleFilter !== '' || $statusFilter !== ''): ?>
                    <a href="<?= Router::url('/users') ?>" class="btn btn-ghost btn-sm" style="padding:8px;" title="Reset Filter">
                        <i data-lucide="rotate-ccw" style="width:14px;height:14px;"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- USERS DATA TABLE                                                          -->
    <!-- ========================================================================= -->
    <div class="card rounded-xl shadow-sm overflow-hidden" style="padding:0;border:1px solid var(--color-hairline);background:var(--color-canvas);">
        <div class="overflow-x-auto">
            <table class="data-table w-full text-left" style="margin:0;width:100%;">
                <thead>
                    <tr style="border-bottom:1px solid var(--color-hairline);background:var(--color-canvas-soft);">
                        <th style="padding:10px 14px;width:48px;text-align:center;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">No</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Pengguna</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Peran (Role)</th>
                        <th style="padding:10px 14px;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Karyawan Terkait</th>
                        <th style="padding:10px 14px;text-align:center;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Status</th>
                        <th style="padding:10px 14px;text-align:right;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="6" style="padding:48px 16px;text-align:center;color:var(--color-ink-mute);">
                                <i data-lucide="user-x" style="width:32px;height:32px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-size:13px;">Tidak ada data pengguna yang sesuai filter.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $no = 1;
                        foreach ($users as $u): 
                            $isDev = ($u['peran'] === 'developer');
                            $isSelf = ($u['id'] === Auth::id());
                            $isActive = (bool)$u['status_aktif'];
                            $userJson = htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8');
                        ?>
                            <tr style="border-bottom:1px solid var(--color-hairline);transition:background 0.15s ease;">
                                <td style="padding:10px 14px;text-align:center;font-family:var(--font-mono);font-size:11px;color:var(--color-ink-mute);">
                                    <?= $no++ ?>
                                </td>

                                <td style="padding:10px 14px;">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:32px;height:32px;border-radius:var(--rounded-full);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:11.5px;flex-shrink:0;<?= $isDev ? 'background:rgba(139,92,246,0.14);color:#8b5cf6;' : 'background:rgba(59,130,246,0.12);color:#3b82f6;' ?>">
                                            <?= strtoupper(substr($u['nama_lengkap'], 0, 2)) ?>
                                        </div>
                                        <div>
                                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);display:flex;align-items:center;gap:6px;">
                                                <span><?= htmlspecialchars($u['nama_lengkap']) ?></span>
                                                <?php if ($isSelf): ?>
                                                    <span class="badge badge-mono text-[9px]" style="background:rgba(59,130,246,0.12);color:#3b82f6;">Anda</span>
                                                <?php endif; ?>
                                            </div>
                                            <div style="font-size:11px;color:var(--color-ink-mute);font-family:var(--font-mono);">
                                                @<?= htmlspecialchars($u['nama_pengguna']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td style="padding:10px 14px;">
                                    <span class="badge" style="<?= match($u['peran']) {
                                        'developer' => 'background:rgba(139,92,246,0.12);color:#8b5cf6;border:1px solid rgba(139,92,246,0.25);',
                                        'owner' => 'background:rgba(245,158,11,0.12);color:#d97706;border:1px solid rgba(245,158,11,0.25);',
                                        'admin' => 'background:rgba(59,130,246,0.12);color:#2563eb;border:1px solid rgba(59,130,246,0.25);',
                                        'sales' => 'background:rgba(16,185,129,0.12);color:#059669;border:1px solid rgba(16,185,129,0.25);',
                                        'driver' => 'background:rgba(2,132,199,0.12);color:#0284c7;border:1px solid rgba(2,132,199,0.25);',
                                        default => 'background:var(--color-canvas-soft);color:var(--color-ink-secondary);border:1px solid var(--color-hairline);',
                                    } ?>font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;">
                                        <i data-lucide="<?= match($u['peran']) {
                                            'developer' => 'terminal',
                                            'owner' => 'crown',
                                            'admin' => 'briefcase',
                                            'sales' => 'trending-up',
                                            'driver' => 'truck',
                                            default => 'tag',
                                        } ?>" style="width:11px;height:11px;"></i>
                                        <span><?= htmlspecialchars(ucfirst($u['peran'])) ?></span>
                                    </span>
                                </td>

                                <td style="padding:10px 14px;">
                                    <?php if (!empty($u['nama_karyawan'])): ?>
                                        <div style="font-weight:600;font-size:12.5px;color:var(--color-ink);">
                                            <?= htmlspecialchars($u['nama_karyawan']) ?>
                                        </div>
                                        <div style="font-size:10.5px;color:var(--color-ink-mute);">
                                            <?= htmlspecialchars($u['posisi_karyawan'] ?? 'Karyawan') ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color:var(--color-ink-mute);font-style:italic;font-size:11.5px;">Tidak ditautkan</span>
                                    <?php endif; ?>
                                </td>

                                <td style="padding:10px 14px;text-align:center;">
                                    <?php if ($isActive): ?>
                                        <span class="badge" style="background:rgba(16,185,129,0.12);color:#059669;border:1px solid rgba(16,185,129,0.25);font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;">
                                            <span style="width:5px;height:5px;border-radius:50%;background:#10b981;"></span> Aktif
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background:rgba(239,68,68,0.12);color:#dc2626;border:1px solid rgba(239,68,68,0.25);font-size:10.5px;font-weight:700;padding:2px 7px;border-radius:6px;display:inline-flex;align-items:center;gap:4px;">
                                            <span style="width:5px;height:5px;border-radius:50%;background:#ef4444;"></span> Suspend
                                        </span>
                                    <?php endif; ?>
                                </td>                                <td style="padding:10px 14px;text-align:right;">
                                    <div style="display:inline-flex;align-items:center;gap:4px;">
                                        <!-- Link Loket Izin (Hanya untuk non-developer) -->
                                        <?php if (!$isDev): ?>
                                            <a href="<?= Router::url('/permissions?tab=user&user_id=' . urlencode($u['id'])) ?>" 
                                               class="btn btn-ghost btn-sm" style="padding:5px;"
                                               title="Atur Izin Kustom Pengguna">
                                                <i data-lucide="shield-check" style="width:14px;height:14px;color:#f97316;"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Tombol Edit: Akun developer hanya bisa diedit oleh developer itu sendiri -->
                                        <?php if ($canManage && (!$isDev || (Auth::isDeveloper() && $isSelf))): ?>
                                            <button type="button" 
                                                    @click="openEdit(<?= $userJson ?>)" 
                                                    class="btn btn-ghost btn-sm" style="padding:5px;" 
                                                    title="Edit Akun Pengguna">
                                                <i data-lucide="edit-3" style="width:14px;height:14px;color:var(--color-ink-mute);"></i>
                                            </button>

                                            <?php if (!$isSelf && !$isDev): ?>
                                                <!-- Toggle Suspend (Akun developer dilindungi dari suspend) -->
                                                <form action="<?= Router::url('/users/toggle-status') ?>" method="POST" style="display:inline;">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>">
                                                    <button type="submit" 
                                                            class="btn btn-ghost btn-sm" style="padding:5px;"
                                                            title="<?= $isActive ? 'Nonaktifkan Akun' : 'Aktifkan Akun' ?>">
                                                        <i data-lucide="<?= $isActive ? 'user-x' : 'user-check' ?>" style="width:14px;height:14px;color:<?= $isActive ? '#d97706' : '#059669' ?>;"></i>
                                                    </button>
                                                </form>

                                                <!-- Delete User (Akun developer dilindungi dari penghapusan) -->
                                                <form action="<?= Router::url('/users/delete') ?>" method="POST" style="display:inline;"
                                                      data-confirm="Apakah Anda yakin ingin menghapus akun @<?= htmlspecialchars($u['nama_pengguna']) ?> secara permanen?"
                                                      data-confirm-title="Hapus Akun Pengguna"
                                                      data-confirm-type="danger"
                                                      data-confirm-btn="Ya, Hapus Akun">
                                                    <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>">
                                                    <button type="submit" class="btn btn-ghost btn-sm" style="padding:5px;" title="Hapus Akun">
                                                        <i data-lucide="trash-2" style="width:14px;height:14px;color:var(--color-danger);"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- ========================================================================= -->
    <!-- MODAL TAMBAH PENGGUNA (TELEPORTED TO BODY)                                -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="addModalOpen" x-cloak class="modal-backdrop" style="background:rgba(15,23,42,0.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9999;" @click.self="addModalOpen = false">
            <div class="modal-box" style="max-width:620px;width:100%;border-radius:20px;padding:28px 32px;background:#ffffff;border:1.5px solid #e2e8f0;box-shadow:0 25px 50px -12px rgba(15,23,42,0.25);" @click.stop>
                
                <!-- Modal Header -->
                <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;padding-bottom:18px;border-bottom:1.5px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:44px;height:44px;border-radius:12px;background:#eff6ff;color:var(--color-primary, #2563eb);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="user-plus" style="width:22px;height:22px;"></i>
                        </div>
                        <div>
                            <h4 style="font-size:1.18rem;font-weight:700;color:#0f172a;margin:0 0 3px 0;">Tambah Pengguna Baru</h4>
                            <p style="font-size:0.82rem;color:#64748b;margin:0;">Buat akun login sistem baru untuk karyawan atau staf operasional</p>
                        </div>
                    </div>
                    <button type="button" @click="addModalOpen = false" 
                            style="width:34px;height:34px;border-radius:10px;background:#f8fafc;border:1.5px solid #e2e8f0;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.15s;"
                            onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';this.style.borderColor='#fecaca';"
                            onmouseout="this.style.background='#f8fafc';this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </button>
                </div>

                <!-- Modal Body / Form -->
                <form action="<?= Router::url('/users/store') ?>" method="POST">
                    <div style="display:flex;flex-direction:column;gap:18px;">
                        
                        <!-- Row 1: Nama & Username -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    Nama Lengkap <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="text" name="nama_lengkap" placeholder="misal: Budi Santoso" required 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>

                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    Username <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="text" name="nama_pengguna" placeholder="misal: budi_sales" required 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;font-family:monospace;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>
                        </div>

                        <!-- Row 2: Kata Sandi & Peran -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    Kata Sandi <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="password" name="password" placeholder="Minimal 4 karakter" required 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>

                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    Peran Jabatan (Role) <span style="color:#e11d48;">*</span>
                                </label>
                                <select name="peran_id" required 
                                        style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                        onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                        onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                                    <option value="">-- Pilih Peran --</option>
                                    <?php foreach ($roles as $r): 
                                        if ($r['nama_peran'] === 'developer') continue; // Proteksi: Developer tunggal
                                    ?>
                                        <option value="<?= htmlspecialchars($r['id']) ?>">
                                            <?= htmlspecialchars(ucfirst($r['nama_peran'])) ?> - <?= htmlspecialchars($r['deskripsi'] ?: 'Hak akses standar') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <!-- Row 3: Tautan Karyawan -->
                        <div>
                            <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                Tautkan ke Data Pegawai / Karyawan <span style="font-size:0.78rem;color:#64748b;font-weight:400;">(Opsional)</span>
                            </label>
                            <select name="karyawan_id" 
                                    style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                    onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                    onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                                <option value="">-- Tanpa Tautan Karyawan (Akun Umum/Admin) --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option value="<?= htmlspecialchars($emp['id']) ?>">
                                        <?= htmlspecialchars($emp['nama_karyawan']) ?> (NIK: <?= htmlspecialchars($emp['nik'] ?: '-') ?>) - Posisi: <?= htmlspecialchars($emp['posisi'] ?: 'Staf') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Row 4: WhatsApp & Telegram -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    Nomor WhatsApp <span style="font-size:0.78rem;color:#64748b;font-weight:400;">(Opsional)</span>
                                </label>
                                <input type="text" name="nomor_whatsapp" placeholder="misal: 081234567890" 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>

                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    ID Telegram <span style="font-size:0.78rem;color:#64748b;font-weight:400;">(Bot Lapangan)</span>
                                </label>
                                <input type="text" name="id_telegram" placeholder="misal: 123456789" 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;font-family:monospace;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>
                        </div>

                    </div>

                    <!-- Modal Footer Buttons -->
                    <div style="display:flex;justify-content:flex-end;align-items:center;gap:10px;padding-top:20px;margin-top:26px;border-top:1.5px solid #f1f5f9;">
                        <button type="button" @click="addModalOpen = false" 
                                style="height:42px;padding:0 20px;border-radius:10px;background:#f1f5f9;color:#475569;font-weight:600;font-size:0.88rem;border:none;cursor:pointer;transition:all 0.15s;"
                                onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
                            Batal
                        </button>
                        <button type="submit" 
                                style="height:42px;padding:0 24px;border-radius:10px;background:var(--color-primary, #2563eb);color:#ffffff;font-weight:700;font-size:0.88rem;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(37,99,235,0.25);display:inline-flex;align-items:center;gap:8px;transition:all 0.15s;"
                                onmouseover="this.style.transform='translateY(-1px)';" onmouseout="this.style.transform='none';">
                            <i data-lucide="check" style="width:16px;height:16px;"></i>
                            <span>Buat Akun Pengguna</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL EDIT PENGGUNA (TELEPORTED TO BODY)                                  -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
        <div x-show="editModalOpen" x-cloak class="modal-backdrop" style="background:rgba(15,23,42,0.55);backdrop-filter:blur(5px);display:flex;align-items:center;justify-content:center;padding:16px;z-index:9999;" @click.self="editModalOpen = false">
            <div class="modal-box" style="max-width:620px;width:100%;border-radius:20px;padding:28px 32px;background:#ffffff;border:1.5px solid #e2e8f0;box-shadow:0 25px 50px -12px rgba(15,23,42,0.25);" @click.stop>
                
                <!-- Modal Header -->
                <div class="modal-header" style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;padding-bottom:18px;border-bottom:1.5px solid #f1f5f9;">
                    <div style="display:flex;align-items:center;gap:12px;">
                        <div style="width:44px;height:44px;border-radius:12px;background:#eff6ff;color:var(--color-primary, #2563eb);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="user-cog" style="width:22px;height:22px;"></i>
                        </div>
                        <div>
                            <h4 style="font-size:1.18rem;font-weight:700;color:#0f172a;margin:0 0 3px 0;">
                                Edit Akun: <span style="color:#2563eb;" x-text="'@' + editUser.nama_pengguna"></span>
                            </h4>
                            <p style="font-size:0.82rem;color:#64748b;margin:0;" x-text="editUser.is_developer ? 'Pengaturan Profil Utama Developer (Super Admin Root)' : 'Ubah data profil, peran wewenang, dan status akses pengguna'"></p>
                        </div>
                    </div>
                    <button type="button" @click="editModalOpen = false" 
                            style="width:34px;height:34px;border-radius:10px;background:#f8fafc;border:1.5px solid #e2e8f0;color:#64748b;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.15s;"
                            onmouseover="this.style.background='#fee2e2';this.style.color='#ef4444';this.style.borderColor='#fecaca';"
                            onmouseout="this.style.background='#f8fafc';this.style.color='#64748b';this.style.borderColor='#e2e8f0';">
                        <i data-lucide="x" style="width:16px;height:16px;"></i>
                    </button>
                </div>

                <!-- Modal Body / Form -->
                <form action="<?= Router::url('/users/update') ?>" method="POST">
                    <input type="hidden" name="id" :value="editUser.id">

                    <div style="display:flex;flex-direction:column;gap:18px;">
                        
                        <!-- Row 1: Nama & Username -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    Nama Lengkap <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="text" name="nama_lengkap" x-model="editUser.nama_lengkap" required 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>

                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    Username <span style="color:#e11d48;">*</span>
                                </label>
                                <input type="text" name="nama_pengguna" x-model="editUser.nama_pengguna" required 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;font-family:monospace;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>
                        </div>

                        <!-- Row 2: Kata Sandi & Role (Immutable for Developer) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    Ganti Kata Sandi <span style="font-size:0.76rem;color:#64748b;font-weight:400;">(Kosongkan jika tetap)</span>
                                </label>
                                <input type="password" name="password" placeholder="••••••••" 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>

                            <div>
                                <!-- Jika Developer: Role Terkunci & Permanen -->
                                <template x-if="editUser.is_developer">
                                    <div>
                                        <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                            Peran Jabatan (Role)
                                        </label>
                                        <div style="height:44px;padding:0 14px;border:1.5px solid #ddd6fe;border-radius:12px;background:#f5f3ff;color:#7c3aed;font-size:0.86rem;font-weight:700;display:flex;align-items:center;justify-content:center;gap:8px;box-shadow:0 1px 2px rgba(0,0,0,0.02);">
                                            <i data-lucide="shield" style="width:16px;height:16px;flex-shrink:0;"></i>
                                            <span>Developer (Super Admin • Terkunci)</span>
                                        </div>
                                        <input type="hidden" name="peran_id" :value="editUser.peran_id">
                                    </div>
                                </template>

                                <!-- Jika Non-Developer: Pilihan Role Standar -->
                                <template x-if="!editUser.is_developer">
                                    <div>
                                        <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                            Peran Jabatan (Role) <span style="color:#e11d48;">*</span>
                                        </label>
                                        <select name="peran_id" x-model="editUser.peran_id" required 
                                                style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                                onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                                onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                                            <option value="">-- Pilih Peran --</option>
                                            <?php foreach ($roles as $r): 
                                                if ($r['nama_peran'] === 'developer') continue; // Proteksi: User biasa tidak bisa diubah jadi developer
                                            ?>
                                                <option value="<?= htmlspecialchars($r['id']) ?>">
                                                    <?= htmlspecialchars(ucfirst($r['nama_peran'])) ?> - <?= htmlspecialchars($r['deskripsi'] ?: 'Hak akses standar') ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Row 3: Tautan Karyawan -->
                        <div>
                            <!-- Jika Developer: Banner Informatif Universal Access -->
                            <template x-if="editUser.is_developer">
                                <div style="padding:14px 18px;border:1.5px solid #bfdbfe;border-radius:12px;background:#eff6ff;color:#1e40af;font-size:0.84rem;display:flex;align-items:flex-start;gap:12px;line-height:1.5;">
                                    <i data-lucide="info" style="width:18px;height:18px;flex-shrink:0;color:#2563eb;margin-top:1px;"></i>
                                    <div>
                                        <strong style="color:#1e3a8a;display:block;margin-bottom:2px;">Akses Universal Root (Bypass Penuh)</strong>
                                        Akun Developer memegang hak akses sistem tertinggi secara mandiri dan tidak memerlukan penautan ke data master karyawan.
                                    </div>
                                </div>
                            </template>

                            <!-- Jika Non-Developer: Dropdown Karyawan -->
                            <template x-if="!editUser.is_developer">
                                <div>
                                    <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                        Tautkan ke Data Pegawai / Karyawan <span style="font-size:0.78rem;color:#64748b;font-weight:400;">(Opsional)</span>
                                    </label>
                                    <select name="karyawan_id" x-model="editUser.karyawan_id" 
                                            style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;cursor:pointer;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                            onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                            onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                                        <option value="">-- Tanpa Tautan Karyawan (Akun Umum/Admin) --</option>
                                        <?php foreach ($employees as $emp): ?>
                                            <option value="<?= htmlspecialchars($emp['id']) ?>">
                                                <?= htmlspecialchars($emp['nama_karyawan']) ?> (NIK: <?= htmlspecialchars($emp['nik'] ?: '-') ?>) - Posisi: <?= htmlspecialchars($emp['posisi'] ?: 'Staf') ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </template>
                        </div>

                        <!-- Row 4: WhatsApp & Telegram -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:16px;">
                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    Nomor WhatsApp <span style="font-size:0.78rem;color:#64748b;font-weight:400;">(Opsional)</span>
                                </label>
                                <input type="text" name="nomor_whatsapp" x-model="editUser.nomor_whatsapp" placeholder="misal: 081234567890" 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>

                            <div>
                                <label style="display:block;font-size:0.85rem;font-weight:700;color:#1e293b;margin-bottom:7px;">
                                    ID Telegram <span style="font-size:0.78rem;color:#64748b;font-weight:400;">(Bot Lapangan)</span>
                                </label>
                                <input type="text" name="id_telegram" x-model="editUser.id_telegram" placeholder="misal: 123456789" 
                                       style="width:100%;height:44px;padding:0 14px;border:1.5px solid #e2e8f0;border-radius:12px;background:#ffffff;font-size:0.88rem;color:#0f172a;font-family:monospace;outline:none;box-shadow:0 1px 2px rgba(0,0,0,0.02);transition:all 0.15s;"
                                       onfocus="this.style.borderColor='#2563eb';this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.12)';"
                                       onblur="this.style.borderColor='#e2e8f0';this.style.boxShadow='0 1px 2px rgba(0,0,0,0.02)';">
                            </div>
                        </div>

                        <!-- Row 5: Status Akun Aktif (Hanya untuk Non-Developer) -->
                        <div>
                            <template x-if="editUser.is_developer">
                                <input type="hidden" name="status_aktif" value="1">
                            </template>

                            <template x-if="!editUser.is_developer">
                                <div style="padding:14px 18px;border:1.5px solid #e2e8f0;border-radius:12px;background:#f8fafc;transition:all 0.15s;">
                                    <label style="display:flex;align-items:center;gap:12px;cursor:pointer;user-select:none;margin:0;">
                                        <input type="checkbox" name="status_aktif" value="1" x-model="editUser.status_aktif" 
                                               style="width:18px;height:18px;accent-color:#2563eb;cursor:pointer;flex-shrink:0;">
                                        <div>
                                            <div style="font-size:0.88rem;font-weight:700;color:#0f172a;margin-bottom:2px;">Status Akun Aktif</div>
                                            <div style="font-size:0.78rem;color:#64748b;">Hilangkan centang untuk memblokir atau menonaktifkan akses login akun ini secara instan</div>
                                        </div>
                                    </label>
                                </div>
                            </template>
                        </div>

                    </div>

                    <!-- Modal Footer Buttons -->
                    <div style="display:flex;justify-content:flex-end;align-items:center;gap:10px;padding-top:20px;margin-top:26px;border-top:1.5px solid #f1f5f9;">
                        <button type="button" @click="editModalOpen = false" 
                                style="height:42px;padding:0 20px;border-radius:10px;background:#f1f5f9;color:#475569;font-weight:600;font-size:0.88rem;border:none;cursor:pointer;transition:all 0.15s;"
                                onmouseover="this.style.background='#e2e8f0';" onmouseout="this.style.background='#f1f5f9';">
                            Batal
                        </button>
                        <button type="submit" 
                                style="height:42px;padding:0 24px;border-radius:10px;background:var(--color-primary, #2563eb);color:#ffffff;font-weight:700;font-size:0.88rem;border:none;cursor:pointer;box-shadow:0 3px 10px rgba(37,99,235,0.25);display:inline-flex;align-items:center;gap:8px;transition:all 0.15s;"
                                onmouseover="this.style.transform='translateY(-1px)';" onmouseout="this.style.transform='none';">
                            <i data-lucide="check" style="width:16px;height:16px;"></i>
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </template>

</div>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

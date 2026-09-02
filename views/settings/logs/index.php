<?php
use App\Core\Router;
use App\Helpers\Format;
ob_start();
?>

<div class="space-y-5" x-data="activityLogApp()">

    <!-- ========================================================================= -->
    <!-- 1. TOP HEADER                                                             -->
    <!-- ========================================================================= -->
    <div class="page-header bg-card p-4 rounded-xl border border-hairline shadow-sm">
        <div class="page-header-body">
            <a href="<?= Router::url('/settings') ?>" class="page-back-btn" title="Kembali ke Pengaturan">
                <i data-lucide="arrow-left"></i>
            </a>
            <div class="page-header-icon" style="background:rgba(14,165,233,0.12);color:#0284c7;">
                <i data-lucide="activity"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background:#0284c7;"></span>
                    <span>Audit Trail &amp; Keamanan</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl"><?= htmlspecialchars($pageTitle ?? 'Log Aktivitas Pengguna') ?></h1>
                <p class="page-subtitle text-xs sm:text-sm"><?= htmlspecialchars($pageSubtitle ?? 'Pantau jejak audit transaksi, perubahan harga, dan void faktur') ?></p>
            </div>
        </div>
        <div class="page-header-actions">
            <span class="badge" style="background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;padding:6px 12px;font-weight:700;font-size:12px;border-radius:10px;">
                <i data-lucide="database" style="width:14px;height:14px;"></i>
                <span><?= number_format($totalLogs, 0, ',', '.') ?> Total Rekaman Log</span>
            </span>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. FILTER & SEARCH TOOLBAR                                                -->
    <!-- ========================================================================= -->
    <div class="card p-4 shadow-sm" style="border:1px solid var(--color-hairline);border-radius:16px;">
        <form method="GET" action="<?= Router::url('/settings/activity-logs') ?>" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                
                <!-- Search Input -->
                <div class="lg:col-span-2">
                    <label class="form-label" style="font-size:11.5px;font-weight:700;margin-bottom:4px;">Cari Aktivitas / Pengguna / IP</label>
                    <div style="position:relative;">
                        <i data-lucide="search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:#94a3b8;"></i>
                        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Ketik kata kunci pencarian..." 
                               class="form-input" style="padding-left:36px;height:38px;font-size:12.5px;">
                    </div>
                </div>

                <!-- Kategori Dropdown -->
                <div>
                    <label class="form-label" style="font-size:11.5px;font-weight:700;margin-bottom:4px;">Kategori</label>
                    <select name="kategori" class="form-input" style="height:38px;font-size:12.5px;">
                        <option value="">-- Semua Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= ($kategori === $cat) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($cat)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Jenis Aksi Dropdown -->
                <div>
                    <label class="form-label" style="font-size:11.5px;font-weight:700;margin-bottom:4px;">Jenis Aksi</label>
                    <select name="jenis_aksi" class="form-input" style="height:38px;font-size:12.5px;">
                        <option value="">-- Semua Aksi --</option>
                        <?php foreach ($actions as $act): ?>
                        <option value="<?= htmlspecialchars($act) ?>" <?= ($jenisAksi === $act) ? 'selected' : '' ?>>
                            <?= htmlspecialchars(strtoupper($act)) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Filter Actions Button -->
                <div class="flex items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-1" style="height:38px;font-size:12.5px;font-weight:700;">
                        <i data-lucide="filter"></i>
                        <span>Filter</span>
                    </button>
                    <a href="<?= Router::url('/settings/activity-logs') ?>" class="btn btn-secondary" style="height:38px;font-size:12.5px;" title="Reset Filter">
                        <i data-lucide="rotate-ccw"></i>
                    </a>
                </div>

            </div>

            <!-- Date Range Filter Row -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 pt-2 border-t border-hairline">
                <div>
                    <label class="form-label" style="font-size:11px;font-weight:600;margin-bottom:3px;">Dari Tanggal</label>
                    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-input" style="height:34px;font-size:12px;">
                </div>
                <div>
                    <label class="form-label" style="font-size:11px;font-weight:600;margin-bottom:3px;">Sampai Tanggal</label>
                    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-input" style="height:34px;font-size:12px;">
                </div>
            </div>
        </form>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. TABEL AUDIT TRAIL LOG                                                  -->
    <!-- ========================================================================= -->
    <div class="card p-0 shadow-sm overflow-hidden" style="border:1px solid var(--color-hairline);border-radius:16px;">
        <div class="table-scroll">
            <table class="data-table" style="font-size:12.5px;">
                <thead>
                    <tr style="background:#f8fafc;border-bottom:1.5px solid var(--color-hairline);">
                        <th style="width:150px;">Waktu (WIB)</th>
                        <th style="min-width:160px;">Pengguna &amp; Peran</th>
                        <th style="width:120px;text-align:center;">Kategori</th>
                        <th style="width:100px;text-align:center;">Aksi</th>
                        <th style="min-width:280px;">Deskripsi Aktivitas</th>
                        <th style="width:140px;">IP Address</th>
                        <th style="width:80px;text-align:center;">Detail</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" style="padding:48px 20px;text-align:center;color:var(--color-ink-mute);">
                            <div style="display:flex;flex-direction:column;align-items:center;gap:8px;">
                                <i data-lucide="file-text" style="width:36px;height:36px;opacity:0.4;"></i>
                                <span style="font-weight:700;font-size:14px;">Tidak ada rekaman log aktivitas yang sesuai.</span>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $jenis = strtoupper($log['jenis_aksi'] ?? 'ACTION');
                        $badgeStyle = 'background:#f1f5f9;color:#475569;border:1px solid #cbd5e1;';
                        if ($jenis === 'CREATE' || $jenis === 'INSERT') {
                            $badgeStyle = 'background:#ecfdf5;color:#047857;border:1px solid #a7f3d0;';
                        } elseif ($jenis === 'UPDATE' || $jenis === 'EDIT') {
                            $badgeStyle = 'background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;';
                        } elseif ($jenis === 'DELETE' || $jenis === 'CANCEL' || $jenis === 'VOID') {
                            $badgeStyle = 'background:#fef2f2;color:#b91c1c;border:1px solid #fecaca;';
                        } elseif ($jenis === 'APPROVE') {
                            $badgeStyle = 'background:#faf5ff;color:#7e22ce;border:1px solid #e9d5ff;';
                        } elseif ($jenis === 'WASTE') {
                            $badgeStyle = 'background:#fffbeb;color:#b45309;border:1px solid #fde68a;';
                        }
                    ?>
                    <tr style="border-bottom:1px solid var(--color-hairline);">
                        <!-- Waktu -->
                        <td class="cell-nowrap">
                            <div class="font-mono font-bold" style="font-size:12px;color:var(--color-ink);">
                                <?= date('d/m/Y', strtotime($log['waktu_kejadian'])) ?>
                            </div>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:1px;">
                                <?= date('H:i:s', strtotime($log['waktu_kejadian'])) ?> WIB
                            </div>
                        </td>

                        <!-- Pengguna & Peran -->
                        <td>
                            <div style="font-weight:700;color:var(--color-ink);">
                                <?= htmlspecialchars($log['nama_aktor'] ?: 'Sistem / Anonim') ?>
                            </div>
                            <div style="margin-top:2px;">
                                <span class="badge badge-mono" style="font-size:10px;padding:1px 6px;">
                                    💼 <?= htmlspecialchars($log['peran_aktor'] ?: 'Staf') ?>
                                </span>
                            </div>
                        </td>

                        <!-- Kategori -->
                        <td class="cell-center cell-nowrap">
                            <span class="badge" style="background:#f8fafc;color:#334155;border:1px solid #e2e8f0;font-size:11px;font-weight:600;padding:3px 8px;border-radius:6px;">
                                <?= htmlspecialchars(ucfirst($log['kategori_aktivitas'] ?: 'Umum')) ?>
                            </span>
                        </td>

                        <!-- Aksi -->
                        <td class="cell-center cell-nowrap">
                            <span class="badge" style="<?= $badgeStyle ?>font-size:11px;font-weight:800;padding:3px 8px;border-radius:6px;">
                                <?= htmlspecialchars($jenis) ?>
                            </span>
                        </td>

                        <!-- Deskripsi Aktivitas -->
                        <td>
                            <div style="font-weight:600;color:var(--color-ink);line-height:1.4;">
                                <?= htmlspecialchars($log['deskripsi_aktivitas'] ?? '-') ?>
                            </div>
                            <?php if (!empty($log['tabel_terdampak'])): ?>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;" class="font-mono">
                                Target: <?= htmlspecialchars($log['tabel_terdampak']) ?> <?= !empty($log['id_referensi']) ? '#' . substr($log['id_referensi'], 0, 8) . '...' : '' ?>
                            </div>
                            <?php endif; ?>
                        </td>

                        <!-- IP Address -->
                        <td class="cell-nowrap">
                            <div class="font-mono" style="font-size:11.5px;color:var(--color-ink-secondary);">
                                <?= htmlspecialchars($log['ip_address'] ?: '127.0.0.1') ?>
                            </div>
                            <div style="font-size:10px;color:var(--color-ink-mute);max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($log['user_agent'] ?? '') ?>">
                                <?= htmlspecialchars($log['user_agent'] ?? '-') ?>
                            </div>
                        </td>

                        <!-- Detail Modal Trigger -->
                        <td class="cell-center cell-nowrap">
                            <?php if (!empty($log['data_sebelum']) || !empty($log['data_sesudah'])): ?>
                            <button type="button" @click="openDiffModal(<?= htmlspecialchars(json_encode($log)) ?>)" 
                                    class="btn btn-secondary btn-xs" style="padding:4px 8px;border-radius:6px;font-size:11px;font-weight:700;" title="Lihat Perubahan Data">
                                <i data-lucide="eye" style="width:12px;height:12px;"></i>
                                <span>Data</span>
                            </button>
                            <?php else: ?>
                            <span style="color:var(--color-ink-mute);font-size:11px;">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Controls -->
        <?php if ($totalPages > 1): ?>
        <div style="padding:12px 16px;background:#f8fafc;border-top:1px solid var(--color-hairline);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
            <div style="font-size:12px;color:var(--color-ink-mute);">
                Menampilkan Halaman <strong><?= $currentPage ?></strong> dari <strong><?= $totalPages ?></strong> (Total <?= number_format($totalLogs, 0, ',', '.') ?> log)
            </div>
            <div style="display:flex;gap:4px;">
                <?php if ($currentPage > 1): ?>
                <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['page' => $currentPage - 1]))) ?>" class="btn btn-secondary btn-sm">
                    &laquo; Sebelumnya
                </a>
                <?php endif; ?>
                <?php if ($currentPage < $totalPages): ?>
                <a href="<?= Router::url('/settings/activity-logs?' . http_build_query(array_merge($_GET, ['page' => $currentPage + 1]))) ?>" class="btn btn-secondary btn-sm">
                    Selanjutnya &raquo;
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- ========================================================================= -->
    <!-- 4. MODAL DETAIL DATA PERUBAHAN (BEFORE / AFTER)                           -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showDiffModal" x-cloak class="modal-backdrop" @click.self="showDiffModal = false">
        <div class="modal-box modal-box-lg" style="max-width:720px;padding:24px;" @click.stop>
            <div class="modal-header">
                <div>
                    <div class="modal-title font-bold text-base">Detail Payload Perubahan Data</div>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="selectedLog?.deskripsi_aktivitas"></div>
                </div>
                <button @click="showDiffModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div class="space-y-4" style="margin-top:16px;">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    
                    <!-- Data Sebelum -->
                    <div style="padding:14px;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
                        <div style="font-size:11.5px;font-weight:800;color:#ef4444;text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                            <i data-lucide="history" style="width:14px;height:14px;"></i>
                            <span>Data Sebelum (Before)</span>
                        </div>
                        <pre style="font-size:11px;font-family:monospace;background:#ffffff;padding:10px;border-radius:8px;border:1px solid #e2e8f0;overflow:auto;max-height:240px;color:#334155;white-space:pre-wrap;" x-text="formatJson(selectedLog?.data_sebelum) || 'Tidak ada data sebelum'"></pre>
                    </div>

                    <!-- Data Sesudah -->
                    <div style="padding:14px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;">
                        <div style="font-size:11.5px;font-weight:800;color:#16a34a;text-transform:uppercase;margin-bottom:8px;display:flex;align-items:center;gap:6px;">
                            <i data-lucide="check-circle" style="width:14px;height:14px;"></i>
                            <span>Data Sesudah (After)</span>
                        </div>
                        <pre style="font-size:11px;font-family:monospace;background:#ffffff;padding:10px;border-radius:8px;border:1px solid #bbf7d0;overflow:auto;max-height:240px;color:#15803d;white-space:pre-wrap;" x-text="formatJson(selectedLog?.data_sesudah) || 'Tidak ada data sesudah'"></pre>
                    </div>

                </div>

                <div style="display:flex;justify-content:flex-end;">
                    <button type="button" @click="showDiffModal = false" class="btn btn-secondary" style="font-weight:700;">Tutup</button>
                </div>
            </div>

        </div>
    </div>
    </template>

</div>

<script>
function activityLogApp() {
    return {
        showDiffModal: false,
        selectedLog: null,

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        openDiffModal(log) {
            this.selectedLog = log;
            this.showDiffModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        formatJson(raw) {
            if (!raw) return null;
            if (typeof raw === 'object') {
                return JSON.stringify(raw, null, 2);
            }
            try {
                const parsed = JSON.parse(raw);
                return JSON.stringify(parsed, null, 2);
            } catch (e) {
                return raw;
            }
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

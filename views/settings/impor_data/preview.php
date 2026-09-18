<?php
declare(strict_types=1);

/**
 * KEREN SNACK ERP — Modul Impor & Sinkronisasi Data
 * Halaman Pratinjau & Verifikasi Mutasi Data Diff (preview.php)
 */

use App\Helpers\Format;
use App\Helpers\CSRF;
use App\Core\Router;
use App\Core\Auth;

require_once __DIR__ . '/shared.php';

ob_start();

$activeKey = $syncType ?? 'customers';
$typeLabel = $activeHandler ? $activeHandler->getEntityLabel() : ($entityMeta[$activeKey]['badge'] ?? 'Master Data');
$curCfg    = $entityColumnsConfig[$activeKey] ?? [
    'code_key'   => 'kode_pelanggan',
    'code_label' => 'Kode',
    'name_key'   => 'nama_toko',
    'name_label' => 'Nama Toko',
    'columns'    => [
        ['key' => 'display_grup', 'label' => 'Grup / Kategori'],
        ['key' => 'keterangan', 'label' => 'Keterangan'],
        ['key' => 'status_aktif', 'label' => 'Status', 'type' => 'boolean']
    ]
];

// Pengurutan baris: ERROR -> FATAL -> INSERT -> UPDATE -> DELETE -> Alfabetis Nama
$actionOrder = ['ERROR' => 0, 'FATAL' => 1, 'INSERT' => 2, 'UPDATE' => 3, 'DELETE' => 4];
if (is_array($previewData)) {
    usort($previewData, function($a, $b) use ($actionOrder) {
        $aKey = ($a['action'] === 'ERROR') ? 'ERROR' : (!empty($a['is_fatal']) ? 'FATAL' : ($a['action'] ?? ''));
        $bKey = ($b['action'] === 'ERROR') ? 'ERROR' : (!empty($b['is_fatal']) ? 'FATAL' : ($b['action'] ?? ''));
        $aOrd = $actionOrder[$aKey] ?? 9;
        $bOrd = $actionOrder[$bKey] ?? 9;
        if ($aOrd !== $bOrd) return $aOrd - $bOrd;
        $aName = $a['data']['nama_toko'] ?? ($a['data']['nama_item'] ?? ($a['data']['nama_pemasok'] ?? ($a['data']['nama_lengkap'] ?? ($a['data']['nama_grup'] ?? ($a['data']['nama_wilayah'] ?? ($a['data']['nama_level'] ?? ($a['data']['nama_kelompok'] ?? '')))))));
        $bName = $b['data']['nama_toko'] ?? ($b['data']['nama_item'] ?? ($b['data']['nama_pemasok'] ?? ($b['data']['nama_lengkap'] ?? ($b['data']['nama_grup'] ?? ($b['data']['nama_wilayah'] ?? ($b['data']['nama_level'] ?? ($b['data']['nama_kelompok'] ?? '')))))));
        return strcasecmp((string)$aName, (string)$bName);
    });
}

// Rekapitulasi Mutasi
$cnt_insert = 0;
$cnt_update = 0;
$cnt_delete = 0;
$cnt_fatal  = 0;
$cnt_error  = 0;

if (is_array($previewData)) {
    foreach ($previewData as $row) {
        $act = $row['action'] ?? '';
        $isFatal = !empty($row['is_fatal']);
        if ($act === 'ERROR') $cnt_error++;
        elseif ($isFatal) $cnt_fatal++;
        elseif ($act === 'INSERT') $cnt_insert++;
        elseif ($act === 'UPDATE') $cnt_update++;
        elseif ($act === 'DELETE') $cnt_delete++;
    }
}

$uploadedFilename = $_SESSION['ks_sync_filename'] ?? 'Berkas Excel';
$totalRows = is_array($previewData) ? count($previewData) : 0;
?>

<style>
/* =============================================================================
   SCOPED STYLES — Modul Pratinjau Impor Data (Nol Bentrok CSS Global)
   ============================================================================= */
#importPreviewApp {
    display: flex;
    flex-direction: column;
    gap: 18px;
    max-width: var(--container-max, 1280px);
    margin: 0 auto;
    padding-bottom: 40px;
}

/* ── Filter Badges Bar ── */
.import-filter-btn {
    cursor: pointer;
    user-select: none;
    transition: all 0.15s ease-in-out;
    padding: 5px 12px;
    border-radius: var(--rounded-full);
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    border: 1.5px solid transparent;
}
.import-filter-btn:hover {
    transform: translateY(-1px);
    filter: brightness(0.96);
}
.import-filter-btn.is-active {
    box-shadow: 0 0 0 2px var(--color-primary);
}

/* ── Diff Row Highlight Colors ── */
.import-row-insert { background-color: rgba(16, 185, 129, 0.05) !important; }
.import-row-update { background-color: rgba(245, 158, 11, 0.05) !important; }
.import-row-delete { background-color: rgba(239, 68, 68, 0.05) !important; }
.import-row-fatal  { background-color: rgba(239, 68, 68, 0.12) !important; border-left: 4px solid var(--color-danger) !important; }
.import-row-error  { background-color: rgba(219, 39, 119, 0.12) !important; border-left: 4px solid #db2777 !important; }

.dark .import-row-insert { background-color: rgba(16, 185, 129, 0.08) !important; }
.dark .import-row-update { background-color: rgba(245, 158, 11, 0.08) !important; }
.dark .import-row-delete { background-color: rgba(239, 68, 68, 0.08) !important; }
.dark .import-row-fatal  { background-color: rgba(239, 68, 68, 0.18) !important; }
.dark .import-row-error  { background-color: rgba(219, 39, 119, 0.18) !important; }

/* ── Diff Inline Values ── */
.import-diff-cell {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.import-diff-old {
    font-size: 11.5px;
    color: var(--color-danger);
    text-decoration: line-through;
    text-decoration-thickness: 1.5px;
    background-color: var(--color-danger-soft);
    padding: 1.5px 6px;
    border-radius: var(--rounded-xs);
    border: 1px solid rgba(239, 68, 68, 0.25);
    opacity: 0.85;
}
.import-diff-arrow {
    width: 12px;
    height: 12px;
    color: var(--color-ink-mute);
    flex-shrink: 0;
}
.import-diff-new {
    font-size: 12px;
    font-weight: 700;
    color: var(--color-success);
    background-color: var(--color-success-soft);
    padding: 1.5px 6px;
    border-radius: var(--rounded-xs);
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.import-fatal-reason {
    font-size: 11px;
    color: var(--color-danger);
    line-height: 1.35;
    margin-top: 4px;
    background-color: var(--color-canvas);
    padding: 4px 8px;
    border-radius: var(--rounded-xs);
    border: 1px solid rgba(239, 68, 68, 0.3);
    display: block;
}

.import-row-hidden {
    display: none !important;
}
</style>

<div id="importPreviewApp">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (STANDAR ERP)                                              -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-blue">
                <i data-lucide="git-compare"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color: var(--color-primary);"></span>
                    <span>Pratinjau Sinkronisasi &bull; <?= htmlspecialchars($typeLabel) ?></span>
                </div>
                <h1 class="page-title">Pratinjau Mutasi Data — <?= htmlspecialchars($typeLabel) ?></h1>
                <p class="page-subtitle">Verifikasi dan tinjau perbedaan data (Diff) sebelum diterapkan secara permanen ke database</p>
            </div>
        </div>
        <div class="page-header-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <!-- Badge Kebijakan Mode -->
            <?php if ($syncMode === 'full_sync'): ?>
                <span class="badge badge-warning" style="padding: 6px 12px; font-size: 12px; font-weight: 700;">
                    <i data-lucide="alert-triangle" style="width: 14px; height: 14px;"></i>
                    <span>Sinkronisasi Penuh</span>
                </span>
            <?php else: ?>
                <span class="badge badge-success" style="padding: 6px 12px; font-size: 12px; font-weight: 700;">
                    <i data-lucide="shield-check" style="width: 14px; height: 14px;"></i>
                    <span>Mode Aman (Upsert)</span>
                </span>
            <?php endif; ?>

            <!-- Tombol Batal & Unggah Ulang -->
            <a href="<?= Router::url('/settings/impor-data/cancel') ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i>
                <span>Unggah Berkas Lain</span>
            </a>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. KONDISI JIKA TIDAK ADA PERBEDAAN (SUDAH SINKRON)                       -->
    <!-- ========================================================================= -->
    <?php if (empty($previewData)): ?>
        <div class="card" style="padding: 48px 24px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
            <div style="width: 56px; height: 56px; border-radius: var(--rounded-full); background: rgba(16, 185, 129, 0.12); color: var(--color-success); display: flex; align-items: center; justify-content: center; margin-bottom: 14px;">
                <i data-lucide="check-circle-2" style="width: 28px; height: 28px;"></i>
            </div>
            <h3 style="font-size: 18px; font-weight: 800; color: var(--color-ink); margin-bottom: 6px;">Data Sudah Sinkron Sempurna</h3>
            <p style="font-size: 13px; color: var(--color-ink-mute); max-width: 480px; margin-bottom: 20px;">
                Tidak ada perbedaan antara berkas Excel yang Anda unggah dengan data di database. Semua baris, kode, dan nilai atribut sama persis.
            </p>
            <a href="<?= Router::url('/settings/impor-data/cancel') ?>" class="btn btn-secondary" style="font-weight: 700;">
                <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                <span>Kembali ke Halaman Impor</span>
            </a>
        </div>
    <?php else: ?>

        <!-- ===================================================================== -->
        <!-- 3. BANNER PERINGATAN VALIDASI & KONFLIK IDENTITAS                     -->
        <!-- ===================================================================== -->
        <?php if ($cnt_error > 0): ?>
            <div style="border-left: 4px solid var(--color-danger); border-radius: var(--rounded-md); padding: 16px 18px; background-color: var(--color-danger-soft); border: 1px solid rgba(239, 68, 68, 0.3); border-left-width: 5px; display: flex; gap: 14px; align-items: flex-start;">
                <div style="width: 38px; height: 38px; border-radius: var(--rounded-full); background: rgba(239, 68, 68, 0.18); color: var(--color-danger); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="x-circle" style="width: 20px; height: 20px;"></i>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-size: 14px; font-weight: 800; color: var(--color-danger); margin-bottom: 2px;">
                        Ditemukan <?= $cnt_error ?> Baris Bermasalah (Validasi Gagal)
                    </div>
                    <p style="font-size: 12px; color: var(--color-ink); margin: 0 0 6px 0; line-height: 1.45;">
                        Sinkronisasi ditahan untuk mencegah inkonsistensi data. Ditemukan baris dengan kolom wajib yang kosong, format angka tidak valid, atau relasi master yang belum terdaftar. Tombol simpan dikunci secara aman.
                    </p>
                    <div style="font-size: 11.5px; font-weight: 700; color: var(--color-danger);">
                        Solusi: Periksa baris bertanda <span class="badge badge-danger">ERROR</span> di tabel bawah, perbaiki berkas Excel Anda, lalu klik "Unggah Berkas Lain".
                    </div>
                </div>
            </div>
        <?php elseif ($cnt_fatal > 0): ?>
            <div style="border-left: 4px solid var(--color-warning); border-radius: var(--rounded-md); padding: 16px 18px; background-color: var(--color-warning-soft); border: 1px solid rgba(245, 158, 11, 0.3); border-left-width: 5px; display: flex; gap: 14px; align-items: flex-start;">
                <div style="width: 38px; height: 38px; border-radius: var(--rounded-full); background: rgba(245, 158, 11, 0.18); color: var(--color-warning); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="shield-alert" style="width: 20px; height: 20px;"></i>
                </div>
                <div style="min-width: 0; flex: 1;">
                    <div style="font-size: 14px; font-weight: 800; color: var(--color-warning); margin-bottom: 2px;">
                        Terdeteksi <?= $cnt_fatal ?> Konflik Identitas Master
                    </div>
                    <p style="font-size: 12px; color: var(--color-ink); margin: 0; line-height: 1.45;">
                        Kode pada berkas Excel Anda terdaftar dengan nama yang berbeda di sistem. Demi keamanan, data lama di database dipertahankan dan sistem akan membuatkan kode/ID baru secara aman saat dikonfirmasi.
                    </p>
                </div>
            </div>
        <?php endif; ?>

        <!-- ===================================================================== -->
        <!-- 4. KARTU TABEL PRATINJAU MUTASI (DIFF TABLE)                          -->
        <!-- ===================================================================== -->
        <div class="card" style="padding: 0; overflow: hidden;">
            
            <!-- Toolbar: Filter Mutasi & Instant Live Search -->
            <div style="display: flex; flex-direction: column; sm:flex-row; gap: 12px; align-items: stretch; sm:items-center; justify-content: space-between; padding: 14px 18px; border-bottom: 1px solid var(--color-hairline); background-color: var(--color-canvas-soft);">
                
                <!-- Filter Badges -->
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                    <button type="button" class="import-filter-btn is-active" data-filter="ALL" onclick="filterPreviewAction('ALL', this)" style="background: var(--color-canvas); color: var(--color-ink); border-color: var(--color-hairline);">
                        Semua: <?= $totalRows ?>
                    </button>

                    <?php if ($cnt_insert > 0): ?>
                        <button type="button" class="import-filter-btn" data-filter="INSERT" onclick="filterPreviewAction('INSERT', this)" style="background: rgba(16, 185, 129, 0.12); color: var(--color-success); border-color: rgba(16, 185, 129, 0.3);">
                            + INSERT: <?= $cnt_insert ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($cnt_update > 0): ?>
                        <button type="button" class="import-filter-btn" data-filter="UPDATE" onclick="filterPreviewAction('UPDATE', this)" style="background: rgba(245, 158, 11, 0.12); color: var(--color-warning); border-color: rgba(245, 158, 11, 0.3);">
                            ~ UPDATE: <?= $cnt_update ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($cnt_delete > 0): ?>
                        <button type="button" class="import-filter-btn" data-filter="DELETE" onclick="filterPreviewAction('DELETE', this)" style="background: rgba(239, 68, 68, 0.12); color: var(--color-danger); border-color: rgba(239, 68, 68, 0.3);">
                            &minus; DELETE: <?= $cnt_delete ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($cnt_fatal > 0): ?>
                        <button type="button" class="import-filter-btn" data-filter="FATAL" onclick="filterPreviewAction('FATAL', this)" style="background: rgba(239, 68, 68, 0.2); color: var(--color-danger); border-color: var(--color-danger);">
                            ! FATAL: <?= $cnt_fatal ?>
                        </button>
                    <?php endif; ?>

                    <?php if ($cnt_error > 0): ?>
                        <button type="button" class="import-filter-btn" data-filter="ERROR" onclick="filterPreviewAction('ERROR', this)" style="background: rgba(219, 39, 119, 0.2); color: #db2777; border-color: #db2777;">
                            &times; ERROR: <?= $cnt_error ?>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Instant Search Input -->
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="form-input-icon" style="width: 100%; max-width: 280px;">
                        <i data-lucide="search" class="icon-left" style="color: var(--color-ink-mute); width: 14px; height: 14px;"></i>
                        <input type="text" 
                               id="previewSearchInput" 
                               oninput="handlePreviewSearch(this.value)" 
                               placeholder="Cari SKU, nama, kode..." 
                               class="form-input" 
                               style="height: 34px; font-size: 12.5px; padding-left: 32px;">
                    </div>
                    <span id="previewSearchCount" style="font-size: 11px; color: var(--color-ink-mute); white-space: nowrap;">
                        <?= $totalRows ?> baris
                    </span>
                </div>

            </div>

            <!-- Table Wrapper -->
            <div class="table-wrapper custom-scrollbar" style="max-height: 560px; overflow-y: auto;">
                <table class="table" id="previewDataTable" style="font-size: 12.5px;">
                    <thead style="position: sticky; top: 0; z-index: 10; background-color: var(--color-canvas-soft);">
                        <tr>
                            <th style="width: 80px; text-align: center; white-space: nowrap;">Aksi</th>
                            <th style="width: 130px; text-align: center; white-space: nowrap;"><?= htmlspecialchars($curCfg['code_label']) ?></th>
                            <th style="min-width: 220px; white-space: nowrap;"><?= htmlspecialchars($curCfg['name_label']) ?></th>
                            <?php foreach ($curCfg['columns'] as $col): ?>
                                <th style="white-space: nowrap;"><?= htmlspecialchars($col['label']) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody id="previewTbody">
                    <?php foreach ($previewData as $row):
                        $is_fatal = !empty($row['is_fatal']);
                        $act      = $row['action'] ?? '';
                        $rowCls   = match(true) {
                            $act === 'ERROR'    => 'import-row-error',
                            $is_fatal           => 'import-row-fatal',
                            $act === 'INSERT'   => 'import-row-insert',
                            $act === 'UPDATE'   => 'import-row-update',
                            $act === 'DELETE'   => 'import-row-delete',
                            default             => '',
                        };
                        $actBadgeCls = match(true) {
                            $act === 'ERROR'    => 'badge badge-danger',
                            $is_fatal           => 'badge badge-danger',
                            $act === 'INSERT'   => 'badge badge-success',
                            $act === 'UPDATE'   => 'badge badge-warning',
                            $act === 'DELETE'   => 'badge badge-danger',
                            default             => 'badge badge-secondary',
                        };
                        $actLabel = $act === 'ERROR' ? 'ERROR' : ($is_fatal ? 'FATAL' : $act);
                        $d  = $row['data'] ?? [];
                        $od = $row['old_data'] ?? [];

                        $rowCode = ks_get_row_val($d, $curCfg['code_key']) ?? (ks_get_row_val($od, $curCfg['code_key']) ?? '—');
                        $rowName = ks_get_row_val($d, $curCfg['name_key']) ?? (ks_get_row_val($od, $curCfg['name_key']) ?? '—');
                        $oldName = ks_get_row_val($od, $curCfg['name_key']) ?? null;

                        $searchTokens = strtolower(implode(' ', array_filter([
                            (string)$rowCode, (string)$rowName, $act, $actLabel,
                            (string)($row['error_msg'] ?? ''), (string)($row['fatal_reason'] ?? ''),
                        ])));
                    ?>
                        <tr class="<?= $rowCls ?>" data-action="<?= $actLabel ?>" data-search="<?= htmlspecialchars($searchTokens) ?>">
                            <!-- Aksi Status -->
                            <td style="text-align: center;">
                                <span class="<?= $actBadgeCls ?>" style="font-size: 10px; font-weight: 800; letter-spacing: 0.3px;">
                                    <?= $actLabel ?>
                                </span>
                            </td>

                            <!-- Kode Identitas -->
                            <td style="text-align: center; font-family: var(--font-mono); font-weight: 700; color: <?= $is_fatal ? 'var(--color-danger)' : 'var(--color-ink)' ?>;">
                                <?= htmlspecialchars((string)$rowCode) ?>
                            </td>

                            <!-- Nama Entitas & Deteksi Perubahan Nama -->
                            <td>
                                <?php if ($act === 'ERROR'): ?>
                                    <span style="font-weight: 700; color: var(--color-danger);"><?= htmlspecialchars((string)$rowName) ?></span>
                                    <span class="import-fatal-reason"><?= htmlspecialchars($row['error_msg'] ?? 'Kesalahan format atribut') ?></span>
                                <?php elseif ($is_fatal): ?>
                                    <span style="font-weight: 700; color: var(--color-danger);"><?= htmlspecialchars((string)$rowName) ?></span>
                                    <span class="import-fatal-reason"><?= htmlspecialchars($row['fatal_reason'] ?? 'Konflik identitas master') ?></span>
                                <?php elseif ($act === 'UPDATE' && $oldName !== null && trim((string)$rowName) !== trim((string)$oldName)): ?>
                                    <div class="import-diff-cell">
                                        <span class="import-diff-old"><?= htmlspecialchars((string)$oldName) ?></span>
                                        <i data-lucide="arrow-right" class="import-diff-arrow"></i>
                                        <span class="import-diff-new"><?= htmlspecialchars((string)$rowName) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span style="font-weight: 600; color: var(--color-ink);"><?= htmlspecialchars((string)$rowName) ?></span>
                                <?php endif; ?>
                            </td>

                            <!-- Kolom Atribut Dinamis -->
                            <?php foreach ($curCfg['columns'] as $col): 
                                $cKey  = $col['key'];
                                $cType = $col['type'] ?? 'text';
                                $vNew  = ks_get_row_val($d, $cKey);
                                $vOld  = ks_get_row_val($od, $cKey);

                                $strOld = is_bool($vOld) ? ($vOld ? '1' : '0') : trim((string)$vOld);
                                $strNew = is_bool($vNew) ? ($vNew ? '1' : '0') : trim((string)$vNew);
                                $isChanged = ($act === 'UPDATE' && $vOld !== null && $strOld !== $strNew);
                            ?>
                                <td>
                                    <?php if ($isChanged): ?>
                                        <div class="import-diff-cell">
                                            <span class="import-diff-old"><?= rm_format_cell_value($vOld, $cType) ?></span>
                                            <i data-lucide="arrow-right" class="import-diff-arrow"></i>
                                            <span class="import-diff-new"><?= rm_format_cell_value($vNew, $cType) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <?= rm_format_cell_value($vNew ?? $vOld, $cType) ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>

                        <!-- Row Placeholder Jika Hasil Filter Kosong -->
                        <tr id="previewNoResultRow" style="display: none;">
                            <td colspan="<?= 3 + count($curCfg['columns']) ?>" style="text-align: center; padding: 36px 20px; color: var(--color-ink-mute);">
                                <i data-lucide="inbox" style="width: 32px; height: 32px; margin: 0 auto 8px auto; opacity: 0.5;"></i>
                                <div style="font-size: 13px; font-weight: 600;">Tidak ada baris data yang cocok dengan filter atau pencarian</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Footer Aksi & Konfirmasi -->
            <div style="display: flex; flex-direction: column; sm:flex-row; gap: 14px; align-items: stretch; sm:items-center; justify-content: space-between; padding: 16px 20px; border-top: 1px solid var(--color-hairline); background-color: var(--color-canvas-soft);">
                
                <!-- Info Berkas -->
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div style="width: 32px; height: 32px; border-radius: var(--rounded-xs); background: rgba(37, 99, 235, 0.12); color: var(--color-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <i data-lucide="file-spreadsheet" style="width: 16px; height: 16px;"></i>
                    </div>
                    <div>
                        <div style="font-size: 12.5px; font-weight: 700; color: var(--color-ink);"><?= htmlspecialchars($uploadedFilename) ?></div>
                        <div style="font-size: 11px; color: var(--color-ink-mute);">
                            Total <?= $totalRows ?> baris mutasi terdeteksi
                        </div>
                    </div>
                </div>

                <!-- Tombol Aksi Batal & Konfirmasi -->
                <div style="display: flex; align-items: center; gap: 10px; justify-content: flex-end;">
                    <a href="<?= Router::url('/settings/impor-data/cancel') ?>" class="btn btn-secondary" style="font-weight: 700;">
                        Batal &amp; Unggah Ulang
                    </a>

                    <button type="button" 
                            id="btnTriggerConfirmModal"
                            class="btn btn-primary" 
                            style="font-weight: 700; display: flex; align-items: center; gap: 6px;"
                            onclick="openImportConfirmModal()" 
                            <?= $cnt_error > 0 ? 'disabled' : '' ?>>
                        <i data-lucide="<?= $cnt_error > 0 ? 'lock' : 'check-circle' ?>" style="width: 16px; height: 16px;"></i>
                        <span><?= $cnt_error > 0 ? 'Perbaiki File Dulu' : 'Konfirmasi &amp; Terapkan' ?></span>
                    </button>
                </div>
            </div>

        </div>

    <?php endif; ?>

</div>

<!-- ========================================================================= -->
<!-- 5. HIDDEN CONFIRMATION FORM & NATIVE ERP CONFIRM MODAL                    -->
<!-- ========================================================================= -->
<form action="<?= Router::url('/settings/impor-data/confirm') ?>" method="post" id="syncConfirmForm" style="display: none;">
    <?= CSRF::field() ?>
    <input type="hidden" name="action" value="confirm">
</form>

<div id="importConfirmModal" class="modal-backdrop" style="display: none;" aria-modal="true" role="dialog">
    <div class="modal-box" style="max-width: 440px;" onclick="event.stopPropagation()">
        
        <!-- Header Modal -->
        <div class="modal-header" style="display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="width: 34px; height: 34px; border-radius: var(--rounded-xs); background: rgba(37, 99, 235, 0.14); color: var(--color-primary); display: flex; align-items: center; justify-content: center;">
                    <i data-lucide="check-circle-2" style="width: 18px; height: 18px;"></i>
                </div>
                <h3 class="modal-title" style="font-size: 15px; font-weight: 800;">Terapkan Sinkronisasi?</h3>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" onclick="closeImportConfirmModal()" title="Tutup">
                <i data-lucide="x" style="width: 16px; height: 16px;"></i>
            </button>
        </div>

        <!-- Body Modal -->
        <div class="modal-body" style="padding: 20px;">
            <p style="font-size: 13px; color: var(--color-ink); margin-bottom: 14px; line-height: 1.45;">
                Apakah Anda yakin ingin menerapkan perubahan data <strong><?= htmlspecialchars($typeLabel) ?></strong> ke database?
            </p>

            <!-- Rekap Mutasi Card -->
            <div style="border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); background: var(--color-canvas-soft); overflow: hidden; margin-bottom: 14px;">
                <?php if ($cnt_insert > 0): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; border-bottom: 1px solid var(--color-hairline);">
                        <span style="font-size: 12px; font-weight: 600; color: var(--color-success);">Data Baru (Insert)</span>
                        <span class="badge badge-success">+<?= $cnt_insert ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($cnt_update > 0): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; border-bottom: 1px solid var(--color-hairline);">
                        <span style="font-size: 12px; font-weight: 600; color: var(--color-warning);">Diperbarui (Update)</span>
                        <span class="badge badge-warning">~<?= $cnt_update ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($cnt_delete > 0): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px; border-bottom: 1px solid var(--color-hairline);">
                        <span style="font-size: 12px; font-weight: 600; color: var(--color-danger);">Dihapus / Nonaktif (Delete)</span>
                        <span class="badge badge-danger">-<?= $cnt_delete ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($cnt_fatal > 0): ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 14px;">
                        <span style="font-size: 12px; font-weight: 700; color: var(--color-danger);">Konflik ID (Generate Baru)</span>
                        <span class="badge badge-danger">!<?= $cnt_fatal ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div style="font-size: 11.5px; color: var(--color-ink-mute); line-height: 1.4;">
                <i data-lucide="lock" style="width: 13px; height: 13px; display: inline-block; vertical-align: -2px; color: var(--color-success);"></i>
                Semua operasi dijalankan dalam satu transaksi PostgreSQL terproteksi (atomic rollback).
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="modal-footer" style="padding: 14px 20px; display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid var(--color-hairline); background-color: var(--color-canvas-soft);">
            <button type="button" class="btn btn-secondary" onclick="closeImportConfirmModal()" style="font-weight: 700;">
                Batal
            </button>
            <button type="button" class="btn btn-primary" onclick="executeSyncConfirm()" style="font-weight: 700; display: flex; align-items: center; gap: 6px;">
                <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                <span>Terapkan Sekarang</span>
            </button>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- 6. JAVASCRIPT PRATINJAU & FILTERING CEPAT                                 -->
<!-- ========================================================================= -->
<script>
let currentActiveFilter = 'ALL';
let currentSearchQuery  = '';

function filterPreviewAction(action, btn) {
    currentActiveFilter = action;
    document.querySelectorAll('.import-filter-btn').forEach(b => b.classList.remove('is-active'));
    if (btn) btn.classList.add('is-active');
    applyPreviewTableFilters();
}

function handlePreviewSearch(val) {
    currentSearchQuery = (val || '').toLowerCase().trim();
    applyPreviewTableFilters();
}

function applyPreviewTableFilters() {
    const tbody = document.getElementById('previewTbody');
    if (!tbody) return;

    const rows = tbody.querySelectorAll('tr[data-action]');
    let visibleCount = 0;

    rows.forEach(row => {
        const rowAction = row.getAttribute('data-action') || '';
        const rowSearch = row.getAttribute('data-search') || '';

        const matchesAction = (currentActiveFilter === 'ALL' || rowAction === currentActiveFilter);
        const matchesSearch = (!currentSearchQuery || rowSearch.indexOf(currentSearchQuery) !== -1);

        if (matchesAction && matchesSearch) {
            row.classList.remove('import-row-hidden');
            visibleCount++;
        } else {
            row.classList.add('import-row-hidden');
        }
    });

    const noResultRow = document.getElementById('previewNoResultRow');
    if (noResultRow) {
        noResultRow.style.display = (visibleCount === 0) ? '' : 'none';
    }

    const countText = document.getElementById('previewSearchCount');
    if (countText) {
        countText.textContent = visibleCount + ' baris';
    }
}

// Modal Konfirmasi Manajemen
function openImportConfirmModal() {
    const modal = document.getElementById('importConfirmModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeImportConfirmModal() {
    const modal = document.getElementById('importConfirmModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
}

function executeSyncConfirm() {
    closeImportConfirmModal();
    if (window.AppAction && typeof window.AppAction.show === 'function') {
        window.AppAction.show('Menerapkan Sinkronisasi Data...', 'Menjalankan transaksi database secara atomic...');
    }
    const form = document.getElementById('syncConfirmForm');
    if (form) {
        form.submit();
    }
}

window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { closeImportConfirmModal(); }
});

document.addEventListener('DOMContentLoaded', () => {
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

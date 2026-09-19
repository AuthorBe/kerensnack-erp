<?php
declare(strict_types=1);

/**
 * KEREN SNACK ERP — Modul Impor & Sinkronisasi Data
 * Halaman Utama: Pemilihan Entitas, Download Template & Unggah Berkas (index.php)
 */

use App\Helpers\Format;
use App\Helpers\CSRF;
use App\Core\Router;
use App\Core\Auth;

require_once __DIR__ . '/shared.php';

ob_start();

$selectedKey = $syncType ?? 'territories';
if (!isset($entityMeta[$selectedKey])) {
    $selectedKey = 'territories';
}
$activeMeta = $entityMeta[$selectedKey];
$totalMasterRows = array_sum($entityStats ?? []);
?>

<style>
/* =============================================================================
   SCOPED STYLES — Modul Impor Data (Nol Bentrok CSS Global)
   ============================================================================= */
#importModuleApp {
    display: flex;
    flex-direction: column;
    gap: 20px;
    max-width: var(--container-max, 1280px);
    margin: 0 auto;
    padding-bottom: 32px;
}

/* ── Workflow Two-Column Grid ── */
.import-workflow-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
    align-items: start;
}
@media (min-width: 1024px) {
    .import-workflow-grid {
        grid-template-columns: 1.18fr 0.92fr;
    }
}

/* ── Step Header Pill ── */
.import-step-pill {
    width: 26px;
    height: 26px;
    border-radius: var(--rounded-xs, 4px);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 13px;
    font-weight: 800;
    line-height: 1;
}

/* ── 10 Entity Grid & Pills ── */
.import-entity-grid {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 10px;
}
@media (min-width: 580px) {
    .import-entity-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}
@media (min-width: 1200px) {
    .import-entity-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

.import-entity-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    border-radius: var(--rounded-md, 8px);
    border: 1px solid var(--color-hairline);
    background-color: var(--color-canvas);
    cursor: pointer;
    transition: all 0.18s ease-in-out;
    position: relative;
    user-select: none;
}
.import-entity-card:hover {
    border-color: var(--color-primary);
    transform: translateY(-1.5px);
    box-shadow: var(--shadow-1);
}
.import-entity-card.is-active {
    border-color: var(--color-primary);
    background-color: var(--color-primary-soft, rgba(37, 99, 235, 0.06));
    box-shadow: 0 0 0 1.5px var(--color-primary);
}
.dark .import-entity-card.is-active {
    background-color: rgba(138, 180, 248, 0.1);
    border-color: var(--color-primary);
}

.import-entity-check {
    position: absolute;
    top: -6px;
    right: -6px;
    width: 20px;
    height: 20px;
    border-radius: var(--rounded-full);
    background-color: var(--color-primary);
    color: #ffffff;
    display: none;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
    z-index: 2;
}
.import-entity-card.is-active .import-entity-check {
    display: flex;
}

/* ── Download Action Boxes ── */
.import-dl-card {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: var(--rounded-md, 8px);
    border: 1px solid var(--color-hairline);
    background-color: var(--color-canvas);
    text-decoration: none;
    color: var(--color-ink);
    transition: all 0.18s ease-in-out;
}
.import-dl-card:hover {
    transform: translateY(-1.5px);
    box-shadow: var(--shadow-1);
}
.import-dl-card.is-template:hover {
    border-color: var(--color-success);
}
.import-dl-card.is-current:hover {
    border-color: var(--color-primary);
}

/* ── Mode Selection Radio Cards ── */
.import-mode-card {
    display: flex;
    gap: 12px;
    padding: 14px;
    border-radius: var(--rounded-md, 8px);
    border: 1.5px solid var(--color-hairline);
    background-color: var(--color-canvas);
    cursor: pointer;
    transition: all 0.18s ease-in-out;
    position: relative;
    user-select: none;
}
.import-mode-card:hover {
    border-color: var(--color-hairline-strong);
}
.import-mode-card.is-selected-safe {
    border-color: var(--color-success);
    background-color: var(--color-success-soft, rgba(16, 185, 129, 0.06));
    box-shadow: 0 0 0 1px var(--color-success);
}
.import-mode-card.is-selected-sync {
    border-color: var(--color-warning);
    background-color: var(--color-warning-soft, rgba(245, 158, 11, 0.06));
    box-shadow: 0 0 0 1px var(--color-warning);
}

/* ── Dropzone Box ── */
.import-dropzone {
    border: 2px dashed var(--color-hairline-strong);
    border-radius: var(--rounded-lg, 12px);
    padding: 28px 18px;
    text-align: center;
    background-color: var(--color-canvas-soft);
    transition: all 0.2s ease-in-out;
    position: relative;
    cursor: pointer;
}
.import-dropzone:hover {
    border-color: var(--color-primary);
    background-color: var(--color-primary-soft, rgba(37, 99, 235, 0.04));
}
.import-dropzone.dragover {
    border-color: var(--color-success);
    background-color: var(--color-success-soft, rgba(16, 185, 129, 0.08));
    transform: scale(1.01);
}
.import-dropzone input[type="file"] {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer;
    width: 100%;
    height: 100%;
}
</style>

<div id="importModuleApp">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (STANDAR ERP)                                              -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-emerald">
                <i data-lucide="file-spreadsheet"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color: var(--color-success);"></span>
                    <span>Pengaturan Sistem &bull; Master Data Engine</span>
                </div>
                <h1 class="page-title"><?= htmlspecialchars($pageTitle ?? 'Impor & Sinkronisasi Data') ?></h1>
                <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle ?? 'Pusat Pembaruan Massal Data Master via Excel / CSV sebagai Sumber Kebenaran') ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
            <a href="<?= Router::url('/settings') ?>" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="arrow-left" style="width: 15px; height: 15px;"></i>
                <span>Kembali</span>
            </a>
            <button type="button" onclick="openImportGuideModal()" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                <i data-lucide="help-circle" style="width: 15px; height: 15px;"></i>
                <span>Panduan Teknis</span>
            </button>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. TOP METRICS / KPI STAT CARDS                                           -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Entitas Master -->
        <div class="stat-card" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px;">
            <div class="stat-card-icon" style="background: rgba(37, 99, 235, 0.12); color: var(--color-primary); width: 42px; height: 42px; border-radius: var(--rounded-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="database" style="width: 20px; height: 20px;"></i>
            </div>
            <div style="min-width: 0;">
                <div class="stat-card-label" style="font-size: 11px; margin-bottom: 2px;">Kategori Terhubung</div>
                <div class="stat-card-value" style="font-size: 20px; font-weight: 800; line-height: 1.1;">
                    10 <span style="font-size: 12px; font-weight: 600; color: var(--color-ink-mute);">Master</span>
                </div>
                <div class="stat-card-footer" style="font-size: 11px; color: var(--color-ink-mute-2); margin-top: 2px;">Pelanggan, Produk, Vendor, SDM</div>
            </div>
        </div>

        <!-- Card 2: Total Basis Data Saat Ini -->
        <div class="stat-card" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px;">
            <div class="stat-card-icon" style="background: rgba(16, 185, 129, 0.12); color: var(--color-success); width: 42px; height: 42px; border-radius: var(--rounded-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="layers" style="width: 20px; height: 20px;"></i>
            </div>
            <div style="min-width: 0;">
                <div class="stat-card-label" style="font-size: 11px; margin-bottom: 2px;">Total Baris di Database</div>
                <div class="stat-card-value" style="font-size: 20px; font-weight: 800; line-height: 1.1; color: var(--color-success);">
                    <?= number_format($totalMasterRows, 0, ',', '.') ?> <span style="font-size: 12px; font-weight: 600; color: var(--color-ink-mute);">Baris</span>
                </div>
                <div class="stat-card-footer" style="font-size: 11px; color: var(--color-ink-mute-2); margin-top: 2px;">Siap disinkronkan &amp; diekspor</div>
            </div>
        </div>

        <!-- Card 3: Proteksi Integritas Transaksi -->
        <div class="stat-card" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px;">
            <div class="stat-card-icon" style="background: rgba(245, 158, 11, 0.12); color: var(--color-warning); width: 42px; height: 42px; border-radius: var(--rounded-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="shield-check" style="width: 20px; height: 20px;"></i>
            </div>
            <div style="min-width: 0;">
                <div class="stat-card-label" style="font-size: 11px; margin-bottom: 2px;">Integritas &amp; Keamanan</div>
                <div class="stat-card-value" style="font-size: 16px; font-weight: 800; line-height: 1.2;">
                    Atomic Transaction
                </div>
                <div class="stat-card-footer" style="font-size: 11px; color: var(--color-ink-mute-2); margin-top: 2px;">Rollback otomatis bila ada kendala</div>
            </div>
        </div>

        <!-- Card 4: Format Berkas Valid -->
        <div class="stat-card" style="display: flex; align-items: center; gap: 14px; padding: 14px 18px;">
            <div class="stat-card-icon" style="background: rgba(139, 92, 246, 0.12); color: #8b5cf6; width: 42px; height: 42px; border-radius: var(--rounded-md); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i data-lucide="check-circle-2" style="width: 20px; height: 20px;"></i>
            </div>
            <div style="min-width: 0;">
                <div class="stat-card-label" style="font-size: 11px; margin-bottom: 2px;">Format Berkas Diizinkan</div>
                <div class="stat-card-value" style="font-size: 18px; font-weight: 800; line-height: 1.1; font-family: var(--font-mono);">
                    .XLSX &bull; .CSV
                </div>
                <div class="stat-card-footer" style="font-size: 11px; color: var(--color-ink-mute-2); margin-top: 2px;">SmartReader auto-detect header</div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. WORKFLOW 2 LANGKAH INTERAKTIF (RESPONSIF & BEBAS BENTROK)               -->
    <!-- ========================================================================= -->
    <div class="import-workflow-grid">

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!-- KOLOM KIRI: LANGKAH 1 — PILIH MASTER & UNDUH TEMPLATE             -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <!-- Header Kartu -->
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--color-hairline); background-color: var(--color-canvas-soft);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="import-step-pill" style="background: rgba(16, 185, 129, 0.16); color: var(--color-success);">1</span>
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--color-ink);">Pilih Master Data &amp; Unduh Format</div>
                        <div style="font-size: 11px; color: var(--color-ink-mute);">Pilih kategori untuk melihat informasi dan tautan berkas Excel</div>
                    </div>
                </div>
                <span class="badge badge-secondary" style="font-size: 10.5px; font-weight: 700;">Langkah 1/2</span>
            </div>

            <!-- Body Kartu -->
            <div style="padding: 20px; display: flex; flex-direction: column; gap: 18px;">
                
                <!-- 10 Master Entity Grid -->
                <div class="import-entity-grid" id="importEntityGrid">
                    <?php foreach ($handlers as $key => $h):
                        $meta = $entityMeta[$key] ?? [
                            'icon' => 'folder', 'badge' => $h->getEntityLabel(), 'category' => 'Master Data',
                            'color' => '#3b82f6', 'bg' => 'rgba(59,130,246,0.12)', 'desc' => ''
                        ];
                        $rowCount = $entityStats[$key] ?? 0;
                        $isActive = ($selectedKey === $key);
                    ?>
                        <div class="import-entity-card <?= $isActive ? 'is-active' : '' ?>"
                             data-key="<?= htmlspecialchars($key) ?>"
                             data-title="<?= htmlspecialchars($h->getEntityLabel()) ?>"
                             data-count="<?= $rowCount ?>"
                             data-desc="<?= htmlspecialchars($meta['desc']) ?>"
                             onclick="selectImportEntity('<?= htmlspecialchars($key) ?>')">
                            
                            <div class="import-entity-check">
                                <i data-lucide="check" style="width: 12px; height: 12px; stroke-width: 3;"></i>
                            </div>

                            <div style="width: 36px; height: 36px; border-radius: var(--rounded-xs); background: <?= $meta['bg'] ?>; color: <?= $meta['color'] ?>; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i data-lucide="<?= $meta['icon'] ?>" style="width: 18px; height: 18px;"></i>
                            </div>

                            <div style="min-width: 0; flex: 1;">
                                <div style="font-size: 13px; font-weight: 700; color: var(--color-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= htmlspecialchars($h->getEntityLabel()) ?>
                                </div>
                                <div style="font-size: 11px; font-family: var(--font-mono); color: var(--color-ink-mute); margin-top: 1px;">
                                    <?= number_format($rowCount, 0, ',', '.') ?> data
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Entity Detail & Download Box -->
                <div style="border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); padding: 16px; background-color: var(--color-canvas-soft);">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i data-lucide="folder-check" style="width: 16px; height: 16px; color: var(--color-primary);"></i>
                            <span id="importDetailTitle" style="font-size: 14px; font-weight: 800; color: var(--color-ink);">
                                <?= htmlspecialchars($handlers[$selectedKey]->getEntityLabel() ?? 'Wilayah & Rute Distribusi') ?>
                            </span>
                        </div>
                        <span class="badge badge-primary" id="importDetailCountBadge" style="font-size: 11px;">
                            <i data-lucide="database" style="width: 12px; height: 12px;"></i>
                            <span id="importDetailCountText"><?= number_format($entityStats[$selectedKey] ?? 0, 0, ',', '.') ?> Baris di DB</span>
                        </span>
                    </div>

                    <p id="importDetailDesc" style="font-size: 12px; color: var(--color-ink-mute); margin: 0 0 14px 0; line-height: 1.45;">
                        <?= htmlspecialchars($activeMeta['desc'] ?? '') ?>
                    </p>

                    <!-- 2 Tombol Download -->
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px; padding-top: 12px; border-top: 1px solid var(--color-hairline);">
                        <!-- Download Template Kosong -->
                        <a href="#" id="btnDownloadTemplateEmpty" class="import-dl-card is-template no-loader" data-no-loader="true" onclick="handleImportDownload(event, 'empty')">
                            <div style="width: 36px; height: 36px; border-radius: var(--rounded-xs); background: rgba(16, 185, 129, 0.12); color: var(--color-success); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i data-lucide="file-plus-2" style="width: 18px; height: 18px;"></i>
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-size: 12.5px; font-weight: 700; color: var(--color-ink);">Template Kosong</div>
                                <div style="font-size: 11px; color: var(--color-ink-mute);">Format baku entri baru</div>
                            </div>
                        </a>

                        <!-- Download Data Terkini -->
                        <a href="#" id="btnDownloadTemplateCurrent" class="import-dl-card is-current no-loader" data-no-loader="true" onclick="handleImportDownload(event, 'current_data')">
                            <div style="width: 36px; height: 36px; border-radius: var(--rounded-xs); background: rgba(37, 99, 235, 0.12); color: var(--color-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i data-lucide="download-cloud" style="width: 18px; height: 18px;"></i>
                            </div>
                            <div style="min-width: 0;">
                                <div style="font-size: 12.5px; font-weight: 700; color: var(--color-ink);">Ekspor Data Terkini</div>
                                <div style="font-size: 11px; color: var(--color-ink-mute);">Unduh untuk diedit massal</div>
                            </div>
                        </a>
                    </div>
                </div>

            </div>
        </div>

        <!-- ───────────────────────────────────────────────────────────────── -->
        <!-- KOLOM KANAN: LANGKAH 2 — KONFIGURASI MODE & UNGGAH BERKAS         -->
        <!-- ───────────────────────────────────────────────────────────────── -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <!-- Header Kartu -->
            <div class="card-header" style="display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 14px 20px; border-bottom: 1px solid var(--color-hairline); background-color: var(--color-canvas-soft);">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="import-step-pill" style="background: rgba(37, 99, 235, 0.16); color: var(--color-primary);">2</span>
                    <div>
                        <div style="font-size: 14px; font-weight: 700; color: var(--color-ink);">Konfigurasi &amp; Unggah Berkas</div>
                        <div style="font-size: 11px; color: var(--color-ink-mute);">Pilih kebijakan sinkronisasi lalu mulai pratinjau diff</div>
                    </div>
                </div>
                <span class="badge badge-secondary" style="font-size: 10.5px; font-weight: 700;">Langkah 2/2</span>
            </div>

            <!-- Form Body -->
            <form action="<?= Router::url('/settings/impor-data/preview') ?>" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  id="formUploadSync" 
                  onsubmit="handleUploadSyncSubmit(event)" 
                  style="padding: 20px; display: flex; flex-direction: column; gap: 18px;">
                
                <?= CSRF::field() ?>
                <input type="hidden" name="tipe_data" id="hiddenTipeData" value="<?= htmlspecialchars($selectedKey) ?>">
                <input type="hidden" name="mode_sinkronisasi" id="hiddenSyncMode" value="<?= htmlspecialchars($syncMode ?? 'update_insert') ?>">

                <!-- Mode Kebijakan -->
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <label style="font-size: 12.5px; font-weight: 700; color: var(--color-ink);">Pilih Kebijakan Sinkronisasi</label>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <!-- Mode Aman (Upsert) -->
                        <div class="import-mode-card <?= ($syncMode === 'update_insert') ? 'is-selected-safe' : '' ?>"
                             id="modeCardSafe" 
                             onclick="setImportSyncMode('update_insert')">
                            <div style="width: 36px; height: 36px; border-radius: var(--rounded-xs); background: rgba(16, 185, 129, 0.14); color: var(--color-success); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i data-lucide="shield-check" style="width: 18px; height: 18px;"></i>
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 2px;">
                                    <span style="font-size: 13px; font-weight: 800; color: var(--color-ink);">Mode Aman (Upsert)</span>
                                    <span class="badge badge-success" style="font-size: 10px; font-weight: 800;">Rekomendasi</span>
                                </div>
                                <div style="font-size: 11.5px; color: var(--color-ink-mute); line-height: 1.4;">
                                    Menambah data baru &amp; memperbarui yang ada. Data di database tidak akan pernah dihapus.
                                </div>
                            </div>
                        </div>

                        <!-- Mode Penuh (Full Sync) -->
                        <div class="import-mode-card <?= ($syncMode === 'full_sync') ? 'is-selected-sync' : '' ?>"
                             id="modeCardSync" 
                             onclick="setImportSyncMode('full_sync')">
                            <div style="width: 36px; height: 36px; border-radius: var(--rounded-xs); background: rgba(245, 158, 11, 0.14); color: var(--color-warning); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <i data-lucide="alert-triangle" style="width: 18px; height: 18px;"></i>
                            </div>
                            <div style="min-width: 0; flex: 1;">
                                <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 2px;">
                                    <span style="font-size: 13px; font-weight: 800; color: var(--color-ink);">Sinkronisasi Penuh</span>
                                    <span class="badge badge-warning" style="font-size: 10px; font-weight: 800;">Single Source</span>
                                </div>
                                <div style="font-size: 11.5px; color: var(--color-ink-mute); line-height: 1.4;">
                                    Excel jadi patokan mutlak. Data di DB yang hilang di berkas akan dinonaktifkan/dihapus aman.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dropzone Area -->
                <div style="display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <label style="font-size: 12.5px; font-weight: 700; color: var(--color-ink);">
                            Berkas Spreadsheet <span style="color: var(--color-danger); font-weight: 800;">*</span>
                        </label>
                        <span style="font-size: 11px; color: var(--color-ink-mute);">Maksimal 15 MB</span>
                    </div>

                    <div class="import-dropzone" id="importDropzoneBox">
                        <!-- State Kosong / Belum Ada Berkas -->
                        <div id="importDropzoneEmpty" style="pointer-events: none;">
                            <div style="width: 44px; height: 44px; border-radius: var(--rounded-md); background: var(--color-canvas); border: 1px solid var(--color-hairline); color: var(--color-primary); display: flex; align-items: center; justify-content: center; margin: 0 auto 10px auto;">
                                <i data-lucide="upload-cloud" style="width: 22px; height: 22px;"></i>
                            </div>
                            <div style="font-size: 13.5px; font-weight: 700; color: var(--color-ink); margin-bottom: 2px;">
                                Seret &amp; Lepaskan Berkas di Sini
                            </div>
                            <div style="font-size: 11.5px; color: var(--color-ink-mute);">
                                atau <span style="color: var(--color-primary); font-weight: 600; text-decoration: underline;">klik untuk memilih berkas</span>
                            </div>
                            <div style="font-size: 11px; color: var(--color-ink-mute-2); margin-top: 6px; font-family: var(--font-mono);">
                                Format .xlsx, .xls, atau .csv
                            </div>
                        </div>

                        <!-- State Berkas Terpilih -->
                        <div id="importDropzoneSelected" style="display: none; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 14px; background-color: var(--color-canvas); border: 1px solid var(--color-success); border-radius: var(--rounded-md); text-align: left; pointer-events: auto;">
                            <div style="display: flex; align-items: center; gap: 12px; min-width: 0;">
                                <div style="width: 36px; height: 36px; border-radius: var(--rounded-xs); background: rgba(16, 185, 129, 0.14); color: var(--color-success); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i data-lucide="file-check-2" style="width: 20px; height: 20px;"></i>
                                </div>
                                <div style="min-width: 0;">
                                    <div id="importSelectedFileName" style="font-size: 13px; font-weight: 700; color: var(--color-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        file.xlsx
                                    </div>
                                    <div id="importSelectedFileSize" style="font-size: 11px; color: var(--color-ink-mute); font-family: var(--font-mono); margin-top: 1px;">
                                        0 KB
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="clearImportSelectedFile(event)" title="Hapus Berkas" style="color: var(--color-danger); padding: 6px;">
                                <i data-lucide="trash-2" style="width: 16px; height: 16px;"></i>
                            </button>
                        </div>

                        <!-- Hidden Native File Input -->
                        <input type="file" name="file_impor" id="file_impor" accept=".xlsx,.xls,.csv" required onchange="handleImportFileSelected(this)">
                    </div>
                </div>

                <!-- Tombol Submit CTA -->
                <button type="submit" id="btnSubmitImportPreview" class="btn btn-primary btn-lg btn-full" style="height: 44px; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 700;" disabled>
                    <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                    <span>Mulai Analisis &amp; Pratinjau Mutasi Data</span>
                </button>
            </form>
        </div>

    </div>

</div>

<!-- ========================================================================= -->
<!-- 4. MODAL PANDUAN TEKNIS SINKRONISASI (NATIVE ERP MODAL)                    -->
<!-- ========================================================================= -->
<div id="importGuideModal" class="modal-backdrop" style="display: none;" aria-modal="true" role="dialog">
    <div class="modal-box modal-box-lg" onclick="event.stopPropagation()">
        
        <!-- Header Modal -->
        <div class="modal-header" style="padding: 18px 24px; margin-bottom: 0; border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; background: var(--color-canvas);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 38px; height: 38px; border-radius: var(--rounded-xs); background: rgba(16, 185, 129, 0.14); color: var(--color-success); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="book-open" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 class="modal-title" style="font-size: 15px; font-weight: 800; color: var(--color-ink); margin: 0; line-height: 1.3;">Panduan Teknis Sinkronisasi Master</h3>
                    <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 2px;">Pelajari alur kerja, perbedaan mode kebijakan, dan mesin SmartReader</div>
                </div>
            </div>
        </div>

        <!-- Tab Navigasi Modal -->
        <div class="modal-tab-nav" style="border-bottom: 1px solid var(--color-hairline);">
            <button type="button" class="modal-tab-btn is-active" data-guide-tab="roadmap" onclick="switchImportGuideTab('roadmap')">
                <i data-lucide="compass" style="width: 14px; height: 14px;"></i>
                <span>Urutan Setup (Roadmap 4 Fase)</span>
            </button>
            <button type="button" class="modal-tab-btn" data-guide-tab="workflow" onclick="switchImportGuideTab('workflow')">
                <i data-lucide="git-branch" style="width: 14px; height: 14px;"></i>
                <span>Alur Kerja 2-Langkah</span>
            </button>
            <button type="button" class="modal-tab-btn" data-guide-tab="modes" onclick="switchImportGuideTab('modes')">
                <i data-lucide="shield" style="width: 14px; height: 14px;"></i>
                <span>Mode Aman vs Penuh</span>
            </button>
            <button type="button" class="modal-tab-btn" data-guide-tab="smart_engine" onclick="switchImportGuideTab('smart_engine')">
                <i data-lucide="sparkles" style="width: 14px; height: 14px;"></i>
                <span>SmartReader Engine</span>
            </button>
        </div>

        <!-- Body Modal (Scrollable Content) -->
        <div class="modal-body custom-scrollbar" style="padding: 20px; max-height: 60vh; overflow-y: auto;">
            
            <!-- TAB 1: ROADMAP URUTAN SETUP (4 FASE) -->
            <div id="guideTab_roadmap" class="guide-content-pane" style="display: flex; flex-direction: column; gap: 16px;">
                <div style="padding: 12px 16px; border-radius: var(--rounded-md); background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.2); display: flex; gap: 10px; align-items: flex-start;">
                    <i data-lucide="compass" style="width: 18px; height: 18px; color: var(--color-primary); flex-shrink: 0; margin-top: 1px;"></i>
                    <div style="font-size: 12.5px; color: var(--color-ink); line-height: 1.45;">
                        <strong>Peta Jalan (Roadmap) Setup Master Data:</strong> Untuk mencegah penolakan sistem (seperti toko ditolak karena wilayah belum ada, atau produk ditolak karena grup kemasan belum ada), ikuti urutan impor dalam 4 fase terstruktur di bawah ini.
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <!-- FASE 1 -->
                    <div style="border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); background: var(--color-canvas); overflow: hidden;">
                        <div style="padding: 10px 14px; background: rgba(16, 185, 129, 0.08); border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="badge badge-success" style="font-size: 10px; font-weight: 800;">FASE 1</span>
                                <span style="font-size: 13px; font-weight: 800; color: var(--color-ink);">Master Pondasi Independen (Zero Dependency)</span>
                            </div>
                            <span style="font-size: 11px; color: var(--color-success); font-weight: 700;">Wajib Pertama</span>
                        </div>
                        <div style="padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: var(--color-ink);">
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">1.</span>
                                <div><strong>Wilayah &amp; Rute Distribusi:</strong> Menentukan zona logistik dan rute pengantaran toko.</div>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">2.</span>
                                <div><strong>Grup Pelanggan:</strong> Segmentasi tier mitra (Grosir, Ritel, Konsinyasi) dan default level harga (1-30). Toko tidak bisa dibuat tanpa grup ini.</div>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">3.</span>
                                <div><strong>Grup Kemasan Produk:</strong> Ukuran gramasi, barcode universal grup, dan rasio bal ke pcs. Diperlukan sebelum mengisi harga dan produk jadi.</div>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">4.</span>
                                <div><strong>Kelompok Upah Borongan:</strong> Standar tarif upah kemas per bungkus (contoh: Kelompok 600).</div>
                            </div>
                        </div>
                    </div>

                    <!-- FASE 2 -->
                    <div style="border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); background: var(--color-canvas); overflow: hidden;">
                        <div style="padding: 10px 14px; background: rgba(37, 99, 235, 0.08); border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="badge badge-primary" style="font-size: 10px; font-weight: 800;">FASE 2</span>
                                <span style="font-size: 13px; font-weight: 800; color: var(--color-ink);">Sumber Daya, Vendor &amp; Matriks Harga</span>
                            </div>
                            <span style="font-size: 11px; color: var(--color-primary); font-weight: 700;">Langkah Kedua</span>
                        </div>
                        <div style="padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: var(--color-ink);">
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">5.</span>
                                <div><strong>Data Karyawan &amp; Akun:</strong> Mendaftarkan staf internal (khususnya posisi <code>sales</code> dan <code>driver</code>) agar bisa menjadi Sales Pembina saat toko diimpor.</div>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">6.</span>
                                <div><strong>Pemasok Vendor:</strong> Daftar vendor bahan mentah, plastik, bumbu, dan karton.</div>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">7.</span>
                                <div><strong>Matriks 30 Level Harga:</strong> Menentukan nominal harga per pcs untuk Level 1-30 pada setiap Grup Produk yang telah dibuat di Fase 1.</div>
                            </div>
                        </div>
                    </div>

                    <!-- FASE 3 -->
                    <div style="border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); background: var(--color-canvas); overflow: hidden;">
                        <div style="padding: 10px 14px; background: rgba(245, 158, 11, 0.08); border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="badge badge-warning" style="font-size: 10px; font-weight: 800;">FASE 3</span>
                                <span style="font-size: 13px; font-weight: 800; color: var(--color-ink);">Katalog Inventori &amp; Produksi</span>
                            </div>
                            <span style="font-size: 11px; color: var(--color-warning); font-weight: 700;">Langkah Ketiga</span>
                        </div>
                        <div style="padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: var(--color-ink);">
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">8.</span>
                                <div><strong>Bahan Baku &amp; Kemas:</strong> Singkong curah, bumbu tabur, plastik sablon (dapat ditautkan ke Pemasok Utama dari Fase 2).</div>
                            </div>
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">9.</span>
                                <div><strong>Barang Jadi Siap Jual:</strong> Varian snack SKU siap jual (wajib mencantumkan Grup Produk dan Kelompok Upah Borongan yang sah).</div>
                            </div>
                        </div>
                    </div>

                    <!-- FASE 4 -->
                    <div style="border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); background: var(--color-canvas); overflow: hidden;">
                        <div style="padding: 10px 14px; background: rgba(139, 92, 246, 0.08); border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <span class="badge badge-secondary" style="font-size: 10px; font-weight: 800; background: rgba(139, 92, 246, 0.15); color: #8b5cf6;">FASE 4</span>
                                <span style="font-size: 13px; font-weight: 800; color: var(--color-ink);">Jaringan Toko Pelanggan &amp; Outlet (Puncak Relasi)</span>
                            </div>
                            <span style="font-size: 11px; color: #8b5cf6; font-weight: 700;">Langkah Puncak</span>
                        </div>
                        <div style="padding: 12px 14px; display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: var(--color-ink);">
                            <div style="display: flex; align-items: flex-start; gap: 8px;">
                                <span style="font-weight: 700; color: var(--color-primary); font-family: var(--font-mono); width: 18px;">10.</span>
                                <div><strong>Toko Pelanggan:</strong> Diimpor paling akhir karena mengikat 3 relasi sekaligus: <em>Wilayah (Fase 1)</em>, <em>Grup Pelanggan (Fase 1)</em>, dan <em>Sales Pembina (Fase 2)</em>.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tips Anti Gagal -->
                <div style="padding: 12px 16px; border-radius: var(--rounded-md); background: rgba(245, 158, 11, 0.08); border: 1px solid rgba(245, 158, 11, 0.25); display: flex; gap: 10px; align-items: flex-start;">
                    <i data-lucide="shield-alert" style="width: 18px; height: 18px; color: var(--color-warning); flex-shrink: 0; margin-top: 1px;"></i>
                    <div style="font-size: 12px; color: var(--color-ink); line-height: 1.45;">
                        <strong>Tips Penting:</strong> Mengapa sistem menolak data jika urutan terbalik? Hal ini untuk mencegah munculnya data rusak (*orphan data*) pada modul Kasir POS dan Konsinyasi. Pastikan data induk (Grup, Wilayah, Karyawan) sudah ada di database sebelum mengunggah Toko atau Barang Jadi.
                    </div>
                </div>
            </div>

            <!-- TAB 2: ALUR KERJA -->
            <div id="guideTab_workflow" class="guide-content-pane" style="display: none; flex-direction: column; gap: 14px;">
                <div style="padding: 12px 16px; border-radius: var(--rounded-md); background: rgba(37, 99, 235, 0.08); border: 1px solid rgba(37, 99, 235, 0.2); display: flex; gap: 10px; align-items: flex-start;">
                    <i data-lucide="info" style="width: 18px; height: 18px; color: var(--color-primary); flex-shrink: 0; margin-top: 1px;"></i>
                    <div style="font-size: 12.5px; color: var(--color-ink); line-height: 1.45;">
                        <strong>Siklus Pratinjau Terisolasi:</strong> Berkas yang Anda unggah hanya akan diuji coba pada mesin komparasi (Diff Engine). <strong>Database tidak akan diubah sedikit pun</strong> sampai Anda memeriksa pratinjau dan menekan tombol Konfirmasi.
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div style="display: flex; gap: 12px; padding: 12px; border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); background: var(--color-canvas);">
                        <div class="import-step-pill" style="background: rgba(37, 99, 235, 0.15); color: var(--color-primary); flex-shrink: 0;">1</div>
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: var(--color-ink);">Pilih Master Data &amp; Unduh Format</div>
                            <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 2px;">
                                Gunakan <strong>Template Kosong</strong> untuk memasukkan baris data baru, atau <strong>Ekspor Data Terkini</strong> jika Anda ingin mengedit data yang sudah ada di sistem.
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; padding: 12px; border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); background: var(--color-canvas);">
                        <div class="import-step-pill" style="background: rgba(16, 185, 129, 0.15); color: var(--color-success); flex-shrink: 0;">2</div>
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: var(--color-ink);">Buka &amp; Isi Data di Excel / Google Sheets</div>
                            <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 2px;">
                                Masukkan data sesuai petunjuk kolom. Mesin cerdas kami otomatis membaca angka nominal (contoh: <code>Rp15.000</code> atau <code>15000</code> otomatis dikonversi).
                            </div>
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; padding: 12px; border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); background: var(--color-canvas);">
                        <div class="import-step-pill" style="background: rgba(245, 158, 11, 0.15); color: var(--color-warning); flex-shrink: 0;">3</div>
                        <div>
                            <div style="font-size: 13px; font-weight: 700; color: var(--color-ink);">Unggah &amp; Periksa Tabel Perbandingan (Diff)</div>
                            <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 2px;">
                                Sistem akan menandai mutasi dengan label hijau (INSERT), kuning (UPDATE), dan merah (DELETE). Jika semua valid, konfirmasi untuk menyimpan.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: MODE AMAN VS PENUH -->
            <div id="guideTab_modes" class="guide-content-pane" style="display: none; flex-direction: column; gap: 14px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 14px;">
                    <div style="padding: 16px; border-radius: var(--rounded-md); border: 1px solid rgba(16, 185, 129, 0.3); background: rgba(16, 185, 129, 0.05);">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <i data-lucide="shield-check" style="width: 18px; height: 18px; color: var(--color-success);"></i>
                            <span style="font-size: 13.5px; font-weight: 800; color: var(--color-success);">Mode Aman (Upsert Saja)</span>
                        </div>
                        <p style="font-size: 12px; color: var(--color-ink); line-height: 1.45; margin: 0;">
                            Sangat cocok untuk penambahan data harian dan update berkala. Sistem hanya memasukkan data baru dan menimpa data yang berubah. Data di database yang tidak tercantum di file Excel Anda <strong>dijamin tetap aman dan tidak akan dihapus</strong>.
                        </p>
                    </div>

                    <div style="padding: 16px; border-radius: var(--rounded-md); border: 1px solid rgba(245, 158, 11, 0.3); background: rgba(245, 158, 11, 0.05);">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 6px;">
                            <i data-lucide="alert-triangle" style="width: 18px; height: 18px; color: var(--color-warning);"></i>
                            <span style="font-size: 13.5px; font-weight: 800; color: var(--color-warning);">Sinkronisasi Penuh (Single Truth)</span>
                        </div>
                        <p style="font-size: 12px; color: var(--color-ink); line-height: 1.45; margin: 0;">
                            Digunakan saat audit menyeluruh atau pembersihan katalog. Berkas Excel menjadi acuan mutlak. Entri di database yang sudah tidak ada di file Excel akan dihapus (atau otomatis dinonaktifkan bila memiliki riwayat transaksi agar laporan keuangan tidak rusak).
                        </p>
                    </div>
                </div>
            </div>

            <!-- TAB 3: SMART READER -->
            <div id="guideTab_smart_engine" class="guide-content-pane" style="display: none; flex-direction: column; gap: 10px;">
                <div style="display: flex; align-items: flex-start; gap: 10px; padding: 12px; border-radius: var(--rounded-md); background: var(--color-canvas); border: 1px solid var(--color-hairline);">
                    <i data-lucide="sparkles" style="width: 18px; height: 18px; color: var(--color-primary); flex-shrink: 0; margin-top: 1px;"></i>
                    <div style="font-size: 12px; color: var(--color-ink); line-height: 1.4;">
                        <strong>Pencocokan Header Fleksibel:</strong> Nama kolom tidak harus kaku. Variasi seperti "Nama Toko", "Toko", "Customer" otomatis dikenali oleh sistem.
                    </div>
                </div>

                <div style="display: flex; align-items: flex-start; gap: 10px; padding: 12px; border-radius: var(--rounded-md); background: var(--color-canvas); border: 1px solid var(--color-hairline);">
                    <i data-lucide="link-2" style="width: 18px; height: 18px; color: var(--color-success); flex-shrink: 0; margin-top: 1px;"></i>
                    <div style="font-size: 12px; color: var(--color-ink); line-height: 1.4;">
                        <strong>Auto-Resolve Relasi Master:</strong> Anda cukup menuliskan nama grup (misal: "Kemasan Bal") atau nama wilayah di Excel; sistem otomatis mengaitkannya ke ID yang sah di database.
                    </div>
                </div>

                <div style="display: flex; align-items: flex-start; gap: 10px; padding: 12px; border-radius: var(--rounded-md); background: var(--color-canvas); border: 1px solid var(--color-hairline);">
                    <i data-lucide="shield-alert" style="width: 18px; height: 18px; color: var(--color-warning); flex-shrink: 0; margin-top: 1px;"></i>
                    <div style="font-size: 12px; color: var(--color-ink); line-height: 1.4;">
                        <strong>Sensor Proteksi Hapus:</strong> Jika suatu produk atau toko pernah memiliki riwayat nota/surat jalan/stok, sistem tidak akan menghapusnya secara fisik melainkan mengubah statusnya menjadi <code>Nonaktif</code> untuk menjaga rekam jejak audit.
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer Modal -->
        <div class="modal-footer" style="padding: 14px 20px; display: flex; justify-content: flex-end; border-top: 1px solid var(--color-hairline); background-color: var(--color-canvas-soft);">
            <button type="button" class="btn btn-primary" onclick="closeImportGuideModal()" style="font-weight: 700;">
                Saya Mengerti
            </button>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- 5. JAVASCRIPT LOGIKA KONTROL INTERAKTIF                                   -->
<!-- ========================================================================= -->
<script>
// State Management
let currentSelectedEntity = '<?= htmlspecialchars($selectedKey) ?>';
let currentSyncMode       = '<?= htmlspecialchars($syncMode ?? 'update_insert') ?>';

function selectImportEntity(key) {
    currentSelectedEntity = key;
    const hiddenTypeInput = document.getElementById('hiddenTipeData');
    if (hiddenTypeInput) hiddenTypeInput.value = key;

    document.querySelectorAll('.import-entity-card').forEach(card => {
        if (card.getAttribute('data-key') === key) {
            card.classList.add('is-active');
            
            const titleEl = document.getElementById('importDetailTitle');
            const countTextEl = document.getElementById('importDetailCountText');
            const descEl  = document.getElementById('importDetailDesc');
            
            if (titleEl) titleEl.textContent = card.getAttribute('data-title');
            if (countTextEl) {
                const countVal = Number(card.getAttribute('data-count') || 0).toLocaleString('id-ID');
                countTextEl.textContent = countVal + ' Baris di DB';
            }
            if (descEl) descEl.textContent = card.getAttribute('data-desc');
        } else {
            card.classList.remove('is-active');
        }
    });

    updateImportDownloadLinks();
}

function setImportSyncMode(mode) {
    currentSyncMode = mode;
    const hiddenModeInput = document.getElementById('hiddenSyncMode');
    if (hiddenModeInput) hiddenModeInput.value = mode;

    const safeCard = document.getElementById('modeCardSafe');
    const syncCard = document.getElementById('modeCardSync');

    if (mode === 'update_insert') {
        if (safeCard) safeCard.classList.add('is-selected-safe');
        if (syncCard) syncCard.classList.remove('is-selected-sync');
    } else {
        if (syncCard) syncCard.classList.add('is-selected-sync');
        if (safeCard) safeCard.classList.remove('is-selected-safe');
    }
}

function updateImportDownloadLinks() {
    const emptyUrl = '<?= Router::url('/settings/impor-data/download-template') ?>?tipe=' + encodeURIComponent(currentSelectedEntity) + '&mode=empty';
    const currUrl  = '<?= Router::url('/settings/impor-data/download-template') ?>?tipe=' + encodeURIComponent(currentSelectedEntity) + '&mode=current_data';

    const btnEmpty = document.getElementById('btnDownloadTemplateEmpty');
    const btnCurr  = document.getElementById('btnDownloadTemplateCurrent');

    if (btnEmpty) btnEmpty.href = emptyUrl;
    if (btnCurr) btnCurr.href = currUrl;
}

let isDownloadingTemplate = false;

async function handleImportDownload(e, mode) {
    if (e) {
        if (e.ctrlKey || e.metaKey || e.shiftKey || (e.button && e.button !== 0)) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
    }

    if (isDownloadingTemplate) return;

    // Pastikan skeleton screen ditutup jika ada yang terpanggil
    if (typeof AppSkeleton !== 'undefined' && typeof AppSkeleton.hide === 'function') {
        AppSkeleton.hide();
    }

    const isCurrent = (mode === 'current_data');
    const actionTitle = isCurrent ? 'Mengekspor Data Terkini...' : 'Menyiapkan Template Kosong...';
    const actionSubtext = isCurrent 
        ? 'Sedang mengambil data sistem dan menyusun berkas Excel...' 
        : 'Sedang membuat format berkas template Excel master data...';

    // Tampilkan Action Popup resmi aplikasi
    if (typeof AppAction !== 'undefined' && typeof AppAction.show === 'function') {
        AppAction.show(actionTitle, actionSubtext);
    }

    const url = '<?= Router::url('/settings/impor-data/download-template') ?>?tipe=' + encodeURIComponent(currentSelectedEntity) + '&mode=' + encodeURIComponent(mode);

    isDownloadingTemplate = true;

    try {
        const response = await fetch(url);

        if (!response.ok) {
            throw new Error('Gagal mengunduh berkas dari server (HTTP ' + response.status + ')');
        }

        const contentType = response.headers.get('Content-Type') || '';
        if (contentType.includes('text/html')) {
            if (typeof AppAction !== 'undefined' && typeof AppAction.hide === 'function') {
                AppAction.hide();
            }
            window.location.href = url;
            return;
        }

        let filename = (mode === 'empty')
            ? ('Template_Impor_' + currentSelectedEntity + '.xlsx')
            : ('Data_Terkini_' + currentSelectedEntity + '.xlsx');

        const disposition = response.headers.get('Content-Disposition');
        if (disposition && disposition.includes('filename=')) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
            if (matches && matches[1]) {
                filename = matches[1].replace(/['"]/g, '').trim();
            }
        }

        const blob = await response.blob();
        const blobUrl = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.style.display = 'none';
        link.href = blobUrl;
        link.download = filename;
        document.body.appendChild(link);
        link.click();

        setTimeout(() => {
            window.URL.revokeObjectURL(blobUrl);
            link.remove();
        }, 1000);

        if (typeof AppAction !== 'undefined' && typeof AppAction.success === 'function') {
            const successTitle = isCurrent ? 'Data Berhasil Diekspor! ✨' : 'Template Berhasil Diunduh! ✨';
            await AppAction.success(successTitle, 'Berkas Excel telah siap di perangkat Anda', 1200);
        } else if (typeof AppAction !== 'undefined' && typeof AppAction.hide === 'function') {
            AppAction.hide();
        }
    } catch (err) {
        console.error('Download template error:', err);
        if (typeof AppAction !== 'undefined' && typeof AppAction.error === 'function') {
            await AppAction.error('Gagal Mengunduh!', err.message || 'Terjadi gangguan sistem', 2200);
        } else if (typeof AppAction !== 'undefined' && typeof AppAction.hide === 'function') {
            AppAction.hide();
        }
    } finally {
        isDownloadingTemplate = false;
    }
}

// Drag & Drop Handling
const dropzone = document.getElementById('importDropzoneBox');
const fileInput = document.getElementById('file_impor');

if (dropzone && fileInput) {
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzone.addEventListener(eventName, (e) => {
            e.preventDefault(); e.stopPropagation();
            dropzone.classList.remove('dragover');
        }, false);
    });

    dropzone.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            fileInput.files = files;
            handleImportFileSelected(fileInput);
        }
    });
}

function handleImportFileSelected(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const emptyState = document.getElementById('importDropzoneEmpty');
        const selectedState = document.getElementById('importDropzoneSelected');
        const fileNameEl = document.getElementById('importSelectedFileName');
        const fileSizeEl = document.getElementById('importSelectedFileSize');
        const btnSubmit = document.getElementById('btnSubmitImportPreview');

        if (emptyState) emptyState.style.display = 'none';
        if (selectedState) selectedState.style.display = 'flex';
        if (fileNameEl) fileNameEl.textContent = file.name;
        if (fileSizeEl) fileSizeEl.textContent = formatBytes(file.size);
        if (btnSubmit) btnSubmit.disabled = false;

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

function clearImportSelectedFile(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    const input = document.getElementById('file_impor');
    if (input) input.value = '';
    
    const emptyState = document.getElementById('importDropzoneEmpty');
    const selectedState = document.getElementById('importDropzoneSelected');
    const btnSubmit = document.getElementById('btnSubmitImportPreview');

    if (emptyState) emptyState.style.display = 'block';
    if (selectedState) selectedState.style.display = 'none';
    if (btnSubmit) btnSubmit.disabled = true;
}

function formatBytes(bytes, decimals = 2) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const dm = decimals < 0 ? 0 : decimals;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
}

function handleUploadSyncSubmit(e) {
    if (window.AppAction && typeof window.AppAction.show === 'function') {
        window.AppAction.show('Membaca & Menganalisis Berkas...', 'Mempersiapkan pratinjau perbandingan data...');
    }
    const btn = document.getElementById('btnSubmitImportPreview');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i data-lucide="loader-2" style="width: 18px; height: 18px; animation: spin 1s linear infinite;"></i> Membaca Berkas...';
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

// Modal Management
function openImportGuideModal(defaultTab = 'roadmap') {
    const modal = document.getElementById('importGuideModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
    switchImportGuideTab(defaultTab);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeImportGuideModal() {
    const modal = document.getElementById('importGuideModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
}

function switchImportGuideTab(tabKey) {
    document.querySelectorAll('.modal-tab-btn').forEach(btn => {
        if (btn.getAttribute('data-guide-tab') === tabKey) {
            btn.classList.add('is-active');
        } else {
            btn.classList.remove('is-active');
        }
    });

    ['roadmap', 'workflow', 'modes', 'smart_engine'].forEach(k => {
        const pane = document.getElementById('guideTab_' + k);
        if (pane) {
            pane.style.display = (k === tabKey) ? 'flex' : 'none';
        }
    });
}

window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { closeImportGuideModal(); }
});

document.addEventListener('DOMContentLoaded', () => {
    updateImportDownloadLinks();
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>
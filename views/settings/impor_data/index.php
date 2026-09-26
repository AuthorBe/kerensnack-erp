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

$selectedKey = $syncType ?? null;
if ($selectedKey !== null && !isset($entityMeta[$selectedKey])) {
    $selectedKey = null;
}
$activeMeta = $selectedKey ? ($entityMeta[$selectedKey] ?? null) : null;
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

/* ── Territory Lookup Help Banner ── */
.import-territory-help {
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 14px;
    border-radius: var(--rounded-xs, 6px);
    background: rgba(37, 99, 235, 0.08);
    border: 1px solid rgba(37, 99, 235, 0.2);
    margin-bottom: 14px;
}
.import-territory-help-content {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 12px;
    color: var(--color-ink);
    min-width: 0;
    line-height: 1.4;
}
.import-territory-help-icon {
    width: 16px;
    height: 16px;
    color: var(--color-primary);
    flex-shrink: 0;
    margin-top: 1px;
}
.import-territory-help-btn {
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 5px;
}

@media (max-width: 640px) {
    .import-territory-help {
        flex-direction: column !important;
        align-items: stretch !important;
        gap: 10px !important;
    }
    .import-territory-help-btn {
        width: 100% !important;
        padding: 7px 12px !important;
        font-size: 12px !important;
    }
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
            <button type="button" onclick="openTerritoryLookupModal()" class="btn btn-secondary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; font-weight: 700;">
                <i data-lucide="map-pin" style="width: 15px; height: 15px; color: var(--color-primary);"></i>
                <span>Kamus Wilayah / Rute</span>
            </button>
            <a href="<?= Router::url('/guide#bab-11-impor-master') ?>" target="_blank" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px; text-decoration: none;" title="Buka Panduan Teknis Sinkronisasi Master di Tab Baru">
                <i data-lucide="book-open" style="width: 15px; height: 15px;"></i>
                <span>Panduan Teknis</span>
            </a>
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
                    <?= count($handlers ?? []) ?> <span style="font-size: 12px; font-weight: 600; color: var(--color-ink-mute);">Master</span>
                </div>
                <div class="stat-card-footer" style="font-size: 11px; color: var(--color-ink-mute-2); margin-top: 2px;">Merek, Pelanggan, Produk, Vendor, SDM</div>
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
                        if (!Auth::can($h->getRequiredPermission())) {
                            continue;
                        }
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
                <div id="importDetailBox" style="border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); padding: 16px; background-color: var(--color-canvas-soft);">
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; margin-bottom: 8px;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <i id="importDetailIcon" data-lucide="<?= $selectedKey ? 'folder-check' : 'mouse-pointer-click' ?>" style="width: 16px; height: 16px; color: var(--color-primary);"></i>
                            <span id="importDetailTitle" style="font-size: 14px; font-weight: 800; color: var(--color-ink);">
                                <?= $selectedKey ? htmlspecialchars($handlers[$selectedKey]->getEntityLabel()) : 'Pilih Salah Satu Master Data' ?>
                            </span>
                        </div>
                        <span class="badge badge-primary" id="importDetailCountBadge" style="font-size: 11px; <?= $selectedKey ? '' : 'display: none;' ?>">
                            <i data-lucide="database" style="width: 12px; height: 12px;"></i>
                            <span id="importDetailCountText"><?= $selectedKey ? number_format($entityStats[$selectedKey] ?? 0, 0, ',', '.') . ' Baris di DB' : '' ?></span>
                        </span>
                    </div>

                    <p id="importDetailDesc" style="font-size: 12px; color: var(--color-ink-mute); margin: 0 0 14px 0; line-height: 1.45;">
                        <?= $selectedKey ? htmlspecialchars($activeMeta['desc'] ?? '') : 'Klik salah satu kartu kategori master data di atas untuk melihat detail format, mengunduh template Excel, dan mengunggah berkas.' ?>
                    </p>

                    <!-- Banner Pintas Kamus Wilayah (Khusus Toko & Pemasok) -->
                    <div id="territoryLookupHelpBox" class="import-territory-help" style="display: <?= in_array($selectedKey, ['customers', 'suppliers', 'territories'], true) ? 'flex' : 'none' ?>;">
                        <div class="import-territory-help-content">
                            <i data-lucide="map-pin" class="import-territory-help-icon"></i>
                            <span style="line-height: 1.35;">Kolom <strong>Wilayah/Kota</strong> wajib terdaftar di Master Wilayah. Cari &amp; salin nama wilayah yang valid di sini.</span>
                        </div>
                        <button type="button" onclick="openTerritoryLookupModal()" class="btn btn-primary btn-xs import-territory-help-btn">
                            <i data-lucide="search" style="width: 12px; height: 12px;"></i>
                            <span>Cari Wilayah</span>
                        </button>
                    </div>

                    <!-- 2 Tombol Download -->
                    <div id="importDownloadButtonsWrap" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 10px; padding-top: 12px; border-top: 1px solid var(--color-hairline); <?= $selectedKey ? '' : 'opacity: 0.55; pointer-events: none;' ?>">
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
                <input type="hidden" name="tipe_data" id="hiddenTipeData" value="<?= htmlspecialchars($selectedKey ?? '') ?>">
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
<!-- 5. MODAL KAMUS & PENCARIAN REFERENSI WILAYAH (MULTI-FIELD LOOKUP)         -->
<!-- ========================================================================= -->
<div id="importTerritoryLookupModal" class="modal-backdrop" style="display: none;" aria-modal="true" role="dialog" onclick="if(event.target === this) closeTerritoryLookupModal()">
    <div class="modal-box modal-box-lg" style="max-width: 840px; width: 95%; display: flex; flex-direction: column; max-height: 88vh; padding: 0; overflow: hidden;" onclick="event.stopPropagation()">
        
        <!-- Header Modal -->
        <div class="modal-header" style="padding: 16px 22px; margin-bottom: 0; border-bottom: 1px solid var(--color-hairline); display: flex; align-items: center; justify-content: space-between; background: var(--color-canvas);">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 38px; height: 38px; border-radius: var(--rounded-xs); background: rgba(37, 99, 235, 0.12); color: var(--color-primary); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i data-lucide="map-pin" style="width: 20px; height: 20px;"></i>
                </div>
                <div>
                    <h3 class="modal-title" style="font-size: 15px; font-weight: 800; color: var(--color-ink); margin: 0; line-height: 1.3;">Kamus Referensi Master Wilayah &amp; Rute</h3>
                    <div style="font-size: 11.5px; color: var(--color-ink-mute); margin-top: 2px;">Cari nama wilayah, kode rute, kota, atau kecamatan yang valid di database untuk disalin ke Excel</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div style="padding: 14px 20px; border-bottom: 1px solid var(--color-hairline); background-color: var(--color-canvas-soft); display: flex; flex-direction: column; gap: 10px;">
            <div style="display: flex; gap: 10px; align-items: center;">
                <div class="form-input-icon" style="flex: 1;">
                    <i data-lucide="search" class="icon-left" style="color: var(--color-ink-mute); width: 15px; height: 15px;"></i>
                    <input type="text" 
                           id="territorySearchInput" 
                           oninput="handleTerritorySearch(this.value)" 
                           placeholder="Ketik nama wilayah, kode rute, provinsi, kota, atau kecamatan..." 
                           class="form-input" 
                           style="height: 38px; font-size: 13px; padding-left: 36px;">
                </div>
                <button type="button" class="btn btn-secondary btn-sm" onclick="clearTerritorySearch()" style="height: 38px; font-weight: 600;">
                    Reset
                </button>
            </div>
            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 11.5px; color: var(--color-ink-mute); flex-wrap: wrap; gap: 6px;">
                <span id="territorySearchCount" style="font-weight: 700; color: var(--color-ink);">Menampilkan <?= count($activeTerritories ?? []) ?> wilayah terdaftar</span>
                <span style="font-style: italic;">Klik tombol "Salin" untuk menyalin teks yang sah langsung ke clipboard</span>
            </div>
        </div>

        <!-- Body Modal (Scrollable List) -->
        <div class="modal-body custom-scrollbar" style="padding: 16px 20px; overflow-y: auto; flex: 1; min-height: 280px; max-height: calc(88vh - 210px);">
            <div id="territoryResultsList" style="display: flex; flex-direction: column; gap: 8px;">
                <!-- Rendered dynamically by JavaScript -->
            </div>
            
            <div id="territoryNoResults" style="display: none; text-align: center; padding: 40px 20px; color: var(--color-ink-mute);">
                <i data-lucide="map-pin-off" style="width: 36px; height: 36px; margin: 0 auto 10px auto; opacity: 0.5;"></i>
                <div style="font-size: 14px; font-weight: 700; color: var(--color-ink); margin-bottom: 4px;">Wilayah Tidak Ditemukan</div>
                <div style="font-size: 12px; max-width: 420px; margin: 0 auto 16px auto; line-height: 1.4;">
                    Wilayah yang Anda cari belum terdaftar di database. Silakan daftarkan wilayah baru di menu Master Wilayah terlebih dahulu.
                </div>
                <a href="<?= Router::url('/customers?tab=territories') ?>" class="btn btn-primary btn-sm" style="display: inline-flex; align-items: center; gap: 6px;">
                    <i data-lucide="plus-circle" style="width: 14px; height: 14px;"></i>
                    <span>Daftarkan di Master Wilayah</span>
                </a>
            </div>
        </div>

        <!-- Footer Modal -->
        <div class="modal-footer" style="padding: 12px 20px; border-top: 1px solid var(--color-hairline); background-color: var(--color-canvas); display: flex; align-items: center; justify-content: space-between;">
            <a href="<?= Router::url('/customers?tab=territories') ?>" class="text-xs text-blue-600 dark:text-blue-400 font-semibold inline-flex items-center gap-1.5 hover:underline">
                <i data-lucide="map-pin" style="width: 13px; height: 13px;"></i> Kelola Master Wilayah Lengkap
            </a>
            <button type="button" class="btn btn-secondary btn-sm" onclick="closeTerritoryLookupModal()">Tutup</button>
        </div>

    </div>
</div>

<!-- ========================================================================= -->
<!-- 6. JAVASCRIPT LOGIKA KONTROL INTERAKTIF                                   -->
<!-- ========================================================================= -->
<script>
// State Management
let currentSelectedEntity = '<?= htmlspecialchars($selectedKey ?? '') ?>';
let currentSyncMode       = '<?= htmlspecialchars($syncMode ?? 'update_insert') ?>';
const ALL_TERRITORIES     = <?= json_encode($activeTerritories ?? [], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;

function selectImportEntity(key) {
    currentSelectedEntity = key;
    const hiddenTypeInput = document.getElementById('hiddenTipeData');
    if (hiddenTypeInput) hiddenTypeInput.value = key;

    const dlWrap = document.getElementById('importDownloadButtonsWrap');
    if (dlWrap) {
        dlWrap.style.opacity = '1';
        dlWrap.style.pointerEvents = 'auto';
    }

    const terrHelp = document.getElementById('territoryLookupHelpBox');
    if (terrHelp) {
        terrHelp.style.display = (key === 'customers' || key === 'suppliers' || key === 'territories') ? 'flex' : 'none';
    }

    const countBadge = document.getElementById('importDetailCountBadge');
    if (countBadge) countBadge.style.display = 'inline-flex';

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

    const fileInput = document.getElementById('file_impor');
    const btnSubmit = document.getElementById('btnSubmitImportPreview');
    if (fileInput && fileInput.files && fileInput.files[0] && btnSubmit) {
        btnSubmit.disabled = false;
    }

    updateImportDownloadLinks();
    if (typeof lucide !== 'undefined') lucide.createIcons();
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
    if (!currentSelectedEntity) return;

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

    if (!currentSelectedEntity) {
        if (typeof AppAction !== 'undefined' && typeof AppAction.error === 'function') {
            AppAction.error('Pilih Kategori!', 'Silakan pilih salah satu kategori master data pada Langkah 1 terlebih dahulu.', 2000);
        } else {
            alert('Silakan pilih salah satu kategori master data pada Langkah 1 terlebih dahulu.');
        }
        return;
    }

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

        const cleanEntity = (currentSelectedEntity || '').replace(/[-_]+/g, ' ').trim();
        let filename = (mode === 'empty')
            ? ('Template Impor ' + cleanEntity + '.xlsx')
            : ('Data Terkini ' + cleanEntity + '.xlsx');

        const disposition = response.headers.get('Content-Disposition');
        if (disposition && disposition.includes('filename=')) {
            const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
            if (matches && matches[1]) {
                let parsed = decodeURIComponent(matches[1].replace(/['"]/g, '').trim());
                filename = parsed;
            }
        }
        // Pastikan nama berkas terbebas dari strip (-) dan underscore (_)
        const dotIdx = filename.lastIndexOf('.');
        if (dotIdx !== -1) {
            const ext = filename.substring(dotIdx);
            const base = filename.substring(0, dotIdx).replace(/[-_]+/g, ' ').replace(/\s+/g, ' ').trim();
            filename = base + ext;
        } else {
            filename = filename.replace(/[-_]+/g, ' ').replace(/\s+/g, ' ').trim();
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
        if (btnSubmit) btnSubmit.disabled = !currentSelectedEntity;

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
    if (!currentSelectedEntity) {
        if (e) {
            e.preventDefault();
            e.stopPropagation();
        }
        if (window.AppAction && typeof window.AppAction.error === 'function') {
            window.AppAction.error('Pilih Kategori!', 'Silakan pilih salah satu kategori master data pada Langkah 1 terlebih dahulu.', 2200);
        } else {
            alert('Silakan pilih salah satu kategori master data pada Langkah 1 terlebih dahulu.');
        }
        return false;
    }

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

// Territory Lookup Modal Functions
function openTerritoryLookupModal() {
    const modal = document.getElementById('importTerritoryLookupModal');
    if (!modal) return;
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
    renderTerritoriesList(ALL_TERRITORIES);
    const searchInput = document.getElementById('territorySearchInput');
    if (searchInput) {
        searchInput.value = '';
        setTimeout(() => searchInput.focus(), 80);
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeTerritoryLookupModal() {
    const modal = document.getElementById('importTerritoryLookupModal');
    if (!modal) return;
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
}

function renderTerritoriesList(list) {
    const listEl = document.getElementById('territoryResultsList');
    const emptyEl = document.getElementById('territoryNoResults');
    const countEl = document.getElementById('territorySearchCount');
    if (!listEl) return;

    if (countEl) {
        countEl.textContent = 'Menampilkan ' + list.length + ' dari ' + ALL_TERRITORIES.length + ' wilayah terdaftar';
    }

    if (list.length === 0) {
        listEl.innerHTML = '';
        if (emptyEl) emptyEl.style.display = 'block';
        if (typeof lucide !== 'undefined') lucide.createIcons();
        return;
    }

    if (emptyEl) emptyEl.style.display = 'none';

    let html = '';
    list.forEach(t => {
        const subList = t.sub_wilayah ? t.sub_wilayah.split(',').map(s => s.trim()).filter(Boolean) : [];
        const subHtml = subList.length > 0 
            ? subList.map(s => '<span class="badge badge-secondary" style="font-size: 10px; padding: 2px 6px;">' + escapeHtml(s) + '</span>').join(' ')
            : '<span class="text-slate-400 italic text-[11px]">—</span>';

        html += `
        <div style="display: flex; flex-direction: column; sm:flex-row; gap: 10px; align-items: stretch; sm:items-center; justify-content: space-between; padding: 11px 14px; border: 1px solid var(--color-hairline); border-radius: var(--rounded-md); background: var(--color-canvas); transition: all 0.15s ease-in-out;">
            <div style="min-width: 0; flex: 1;">
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 3px;">
                    <span class="badge badge-primary font-mono" style="font-size: 11px; font-weight: 800; letter-spacing: 0.3px;">${escapeHtml(t.kode_rute)}</span>
                    <span style="font-size: 13.5px; font-weight: 800; color: var(--color-ink);">${escapeHtml(t.nama_wilayah)}</span>
                    <span style="font-size: 11.5px; color: var(--color-ink-mute); font-weight: 600;">&bull; ${escapeHtml(t.kota_kabupaten)}, ${escapeHtml(t.provinsi)}</span>
                </div>
                <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap; margin-top: 4px;">
                    <span style="font-size: 11px; color: var(--color-ink-mute); font-weight: 700;">Cakupan Area:</span>
                    ${subHtml}
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                <button type="button" class="btn btn-secondary btn-xs" onclick="copyTerritoryText('${escapeJs(t.nama_wilayah)}', this)" style="font-weight: 700; white-space: nowrap; height: 28px; padding: 0 10px;">
                    <i data-lucide="copy" style="width: 12px; height: 12px;"></i> Salin Nama
                </button>
                <button type="button" class="btn btn-ghost btn-xs" onclick="copyTerritoryText('${escapeJs(t.kode_rute)}', this)" title="Salin Kode Rute" style="font-weight: 600; white-space: nowrap; height: 28px; padding: 0 8px; color: var(--color-ink-mute);">
                    Salin Kode
                </button>
            </div>
        </div>`;
    });

    listEl.innerHTML = html;
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function handleTerritorySearch(query) {
    const q = (query || '').toLowerCase().trim();
    if (!q) {
        renderTerritoriesList(ALL_TERRITORIES);
        return;
    }

    const filtered = ALL_TERRITORIES.filter(t => {
        const fullStr = [
            t.kode_rute || '',
            t.nama_wilayah || '',
            t.provinsi || '',
            t.kota_kabupaten || '',
            t.sub_wilayah || ''
        ].join(' ').toLowerCase();
        return fullStr.includes(q);
    });

    renderTerritoriesList(filtered);
}

function clearTerritorySearch() {
    const input = document.getElementById('territorySearchInput');
    if (input) input.value = '';
    renderTerritoriesList(ALL_TERRITORIES);
}

function copyTerritoryText(text, btn) {
    if (!navigator.clipboard) {
        const temp = document.createElement('input');
        temp.value = text;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
    } else {
        navigator.clipboard.writeText(text);
    }

    const origHtml = btn.innerHTML;
    btn.innerHTML = '<i data-lucide="check" style="width: 12px; height: 12px; color: var(--color-success);"></i> Tersalin!';
    btn.classList.add('btn-success');
    if (typeof lucide !== 'undefined') lucide.createIcons();

    setTimeout(() => {
        btn.innerHTML = origHtml;
        btn.classList.remove('btn-success');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }, 1500);
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function escapeJs(str) {
    if (!str) return '';
    return String(str).replace(/\\/g, '\\\\').replace(/'/g, "\\'").replace(/"/g, '\\"');
}

window.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') { 
        closeImportGuideModal();
        closeTerritoryLookupModal();
    }
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
<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="{ activeTab: 'overview', rejectShipmentModal: false, rejectTarget: { id: '', no: '', toko: '' } }" class="space-y-6 pb-20">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-amber">
                <i data-lucide="crown"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f59e0b;"></span>
                    <span>Owner Command Center</span>
                    <span style="display:inline-flex;align-items:center;gap:4px;background:rgba(16,185,129,0.12);color:var(--color-success);padding:2px 7px;border-radius:20px;font-size:9px;font-weight:700;letter-spacing:0.04em;border:1px solid rgba(16,185,129,0.2);">
                        <span style="width:5px;height:5px;border-radius:50%;background:var(--color-success);animation:pulse 1.5s infinite;flex-shrink:0;"></span>
                        LIVE GATEKEEPER
                    </span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Owner Command Center' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Pusat Kendali Eksekutif, Approval Pengiriman Konsinyasi & Analisis Laba-Rugi' ?></p>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB SWITCHER: OVERVIEW BISNIS vs KONSINYASI COMMAND CENTER                 -->
    <!-- ========================================================================= -->
    <div class="no-scrollbar" style="display:flex;gap:8px;padding-bottom:4px;overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <button type="button" 
                @click="activeTab = 'overview'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="activeTab === 'overview' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="layout-dashboard" style="width:15px;height:15px;"></i>
            <span>Ringkasan Eksekutif &amp; Arus Kas</span>
        </button>

        <button type="button" 
                @click="activeTab = 'consignment'; $nextTick(() => { if (window.lucide) lucide.createIcons(); })"
                :class="activeTab === 'consignment' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="store" style="width:15px;height:15px;"></i>
            <span>Konsinyasi Hub &amp; Approval (C1–C6)</span>
            <?php if (!empty($pendingConsignmentShipments)): ?>
            <span class="badge badge-danger text-[10px] px-1.5 py-0.2 animate-pulse"><?= count($pendingConsignmentShipments) ?> Baru</span>
            <?php endif; ?>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB 1: OVERVIEW BISNIS UMUM                                               -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'overview'" x-cloak class="space-y-5">
        
        <!-- KPI STAT CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Omzet Hari Ini -->
            <div class="stat-card" style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="stat-card-label">Omzet Penjualan Hari Ini</div>
                    <div class="stat-card-icon" style="background:rgba(16,185,129,0.1);color:var(--color-success);">
                        <i data-lucide="trending-up"></i>
                    </div>
                </div>
                <div class="stat-card-value"><?= Format::rupiah($omzetToday) ?></div>
                <div class="stat-card-footer">
                    <span class="live-dot"></span>
                    <span>POS Kasir &amp; Order Realtime</span>
                </div>
            </div>

            <!-- Saldo Kas -->
            <div class="stat-card" style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="stat-card-label">Total Kas &amp; Rekening Bank</div>
                    <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                        <i data-lucide="wallet"></i>
                    </div>
                </div>
                <div class="stat-card-value" style="color:#3b82f6;"><?= Format::rupiah($totalSaldoKas) ?></div>
                <div class="stat-card-footer">
                    <i data-lucide="building-2" style="width:12px;height:12px;"></i>
                    <span>Kas Toko &amp; Bank Operasional</span>
                </div>
            </div>

            <!-- Piutang Toko Total -->
            <div class="stat-card" style="display:flex;flex-direction:column;gap:12px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <div class="stat-card-label">Total Piutang Berjalan</div>
                    <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                        <i data-lucide="clock"></i>
                    </div>
                </div>
                <div class="stat-card-value" style="color:#f59e0b;"><?= Format::rupiah($totalPiutang) ?></div>
                <div class="stat-card-footer">
                    <i data-lucide="route" style="width:12px;height:12px;"></i>
                    <span>Gabungan Konsinyasi &amp; Grosir</span>
                </div>
            </div>
        </div>

        <!-- TWO-PANE: APPROVAL PENGELUARAN & LOG STREAM -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <!-- APPROVAL BIAYA -->
            <div class="card p-4 space-y-4" style="border-radius:14px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="shield-check" style="width:16px;height:16px;"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:13px;color:var(--color-ink);">Approval Biaya Operasional</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Bensin, Uang Jalan &amp; Kasbon</div>
                        </div>
                    </div>
                    <span class="badge badge-warning font-mono" style="font-size:10px;"><?= count($pendingDrafts) ?> Draft</span>
                </div>

                <?php if (empty($pendingDrafts)): ?>
                <div class="p-6 text-center" style="font-size:12px;color:var(--color-ink-mute);">
                    🎉 Tidak ada pengajuan biaya operasional yang tertunda.
                </div>
                <?php else: ?>
                <div class="space-y-3 max-h-[380px] overflow-y-auto pr-1">
                    <?php foreach ($pendingDrafts as $pd): ?>
                    <div class="p-3 rounded-xl space-y-2 text-xs" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-start">
                            <div>
                                <span class="font-black text-sm" style="color:var(--color-warning);"><?= Format::rupiah((float)$pd['nominal']) ?></span>
                                <div style="font-weight:700;color:var(--color-ink);margin-top:2px;"><?= htmlspecialchars($pd['kategori_beban'] ?? 'Operasional') ?></div>
                            </div>
                            <span class="font-mono" style="font-size:10px;color:var(--color-ink-mute);"><?= date('d M H:i', strtotime($pd['dibuat_pada'])) ?></span>
                        </div>
                        <p class="p-2 rounded-lg italic text-[11px]" style="background:var(--color-canvas);border:1px solid var(--color-hairline);color:var(--color-ink-secondary);">
                            "<?= htmlspecialchars($pd['keterangan_mentah'] ?? 'Tanpa catatan') ?>"
                        </p>
                        <div class="flex gap-2 pt-1">
                            <form action="<?= Router::url('/owner/reject-draft') ?>" method="POST" class="flex-1">
                                <?= \App\Helpers\CSRF::field() ?>
                                <input type="hidden" name="draft_id" value="<?= htmlspecialchars($pd['id']) ?>">
                                <button type="submit" class="btn btn-secondary w-full py-1 text-[11px] font-bold" style="color:var(--color-danger);">Tolak</button>
                            </form>
                            <form action="<?= Router::url('/owner/approve-draft') ?>" method="POST" class="flex-1">
                                <?= \App\Helpers\CSRF::field() ?>
                                <input type="hidden" name="draft_id" value="<?= htmlspecialchars($pd['id']) ?>">
                                <button type="submit" class="btn btn-primary w-full py-1 text-[11px] font-bold" style="background:var(--color-success);border-color:var(--color-success);">Setujui</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- LIVE ACTIVITY STREAM -->
            <div class="lg:col-span-2 card p-4 space-y-4" style="border-radius:14px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="activity" style="width:16px;height:16px;"></i>
                        </div>
                        <div>
                            <div style="font-weight:800;font-size:13px;color:var(--color-ink);">Live AI Activity Stream</div>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Forensik Audit Log Realtime Seluruh Sistem</div>
                        </div>
                    </div>
                    <span class="badge badge-success font-mono" style="font-size:10px;">Realtime</span>
                </div>

                <div class="space-y-2.5 max-h-[380px] overflow-y-auto pr-1">
                    <?php foreach ($activityLogs as $log): ?>
                    <div class="flex items-start gap-3 p-2.5 rounded-xl text-xs" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div style="width:28px;height:28px;border-radius:8px;background:var(--color-canvas);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid var(--color-hairline);">
                            <i data-lucide="terminal" style="width:14px;height:14px;"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center justify-between">
                                <span style="font-weight:700;color:var(--color-ink);"><?= htmlspecialchars($log['nama_aktor'] ?? 'Sistem') ?></span>
                                <span class="font-mono" style="font-size:10px;color:var(--color-ink-mute);"><?= date('H:i:s d/m', strtotime($log['waktu_kejadian'])) ?></span>
                            </div>
                            <p style="font-size:11.5px;color:var(--color-ink-secondary);margin-top:2px;"><?= htmlspecialchars($log['deskripsi_aktivitas']) ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB 2: KONSINYASI COMMAND CENTER (C1 s/d C6)                               -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'consignment'" x-cloak class="space-y-5">

        <!-- C2: GATEKEEPER APPROVAL PENGIRIMAN KONSINYASI (SANGAT KRUSIAL) -->
        <div class="card p-4 sm:p-5 space-y-4" style="border-radius:18px;border:1.5px solid rgba(245,158,11,0.35);background:var(--color-canvas);box-shadow:var(--shadow-1);">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 pb-3 border-b" style="border-color:var(--color-hairline);">
                <div class="flex items-center gap-3">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(245,158,11,0.25);">
                        <i data-lucide="truck" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <h2 style="font-size:15px;font-weight:900;color:var(--color-ink);">C2: Gatekeeper Approval Pengiriman Konsinyasi</h2>
                        <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:1px;">Barang titipan baru TIDAK BOLEH keluar gudang sebelum Owner memberikan persetujuan resmi.</p>
                    </div>
                </div>
                <span class="badge badge-warning font-mono font-bold text-xs" style="padding:4px 10px;align-self:flex-start;">
                    <?= count($pendingConsignmentShipments) ?> Pengajuan Menunggu
                </span>
            </div>

            <?php if (empty($pendingConsignmentShipments)): ?>
            <div class="p-6 text-center text-xs space-y-1.5" style="color:var(--color-ink-mute);">
                <div style="width:40px;height:40px;border-radius:50%;background:rgba(16,185,129,0.1);color:var(--color-success);display:flex;align-items:center;justify-content:center;margin:0 auto 6px;">
                    <i data-lucide="check-circle" style="width:22px;height:22px;"></i>
                </div>
                <div style="font-weight:700;color:var(--color-ink);">Semua Pengajuan Pengiriman Telah Diproses</div>
                <div>Tidak ada antrean kiriman baru yang tertunda. Gudang &amp; Logistik berjalan lancar.</div>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach ($pendingConsignmentShipments as $ship): ?>
                <div class="p-4 rounded-xl space-y-3 text-xs flex flex-col justify-between" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div>
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="font-mono text-[11px] font-bold" style="color:var(--color-warning);"><?= htmlspecialchars($ship['nomor_surat_jalan']) ?></span>
                                <h3 style="font-size:14px;font-weight:800;color:var(--color-ink);margin-top:2px;"><?= htmlspecialchars($ship['nama_toko']) ?></h3>
                                <div style="font-size:11px;color:var(--color-ink-mute);">Sales Pengaju: <strong style="color:var(--color-ink);"><?= htmlspecialchars($ship['nama_sales']) ?></strong></div>
                            </div>
                            <span class="font-mono text-[10px]" style="color:var(--color-ink-mute);"><?= date('d M H:i', strtotime($ship['dibuat_pada'])) ?></span>
                        </div>

                        <!-- Item Requested vs Ready Warehouse Stock -->
                        <div class="mt-3 p-3 rounded-lg space-y-1.5" style="background:var(--color-canvas);border:1px solid var(--color-hairline);">
                            <div style="font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:0.04em;color:var(--color-ink-mute);">Rincian Barang yang Diajukan:</div>
                            <?php foreach ($ship['items'] as $item): ?>
                            <div class="flex items-center justify-between text-xs py-0.5">
                                <span style="font-weight:600;color:var(--color-ink);"><?= htmlspecialchars($item['nama_item']) ?></span>
                                <div class="flex items-center gap-2">
                                    <span class="font-black" style="color:var(--color-primary);"><?= (int)$item['kuantitas_satuan_dasar'] ?> pcs</span>
                                    <span style="font-size:10.5px;color:var(--color-ink-mute);">(Ready: <?= (int)$item['stok_fisik_saat_ini'] ?> pcs)</span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (!empty($ship['catatan'])): ?>
                        <div class="text-[11px] italic mt-2" style="color:var(--color-ink-mute);">
                            Catatan: "<?= htmlspecialchars($ship['catatan']) ?>"
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2 pt-2 border-t" style="border-color:var(--color-hairline);">
                        <button type="button" 
                                @click="rejectTarget = { id: '<?= htmlspecialchars($ship['surat_jalan_id']) ?>', no: '<?= htmlspecialchars($ship['nomor_surat_jalan']) ?>', toko: '<?= htmlspecialchars($ship['nama_toko']) ?>' }; rejectShipmentModal = true"
                                class="btn btn-secondary flex-1 py-2 text-xs font-bold" style="color:var(--color-danger);">
                            Tolak Pengiriman
                        </button>
                        <form action="<?= Router::url('/owner/consignment/approve-delivery') ?>" method="POST" class="flex-1">
                            <?= \App\Helpers\CSRF::field() ?>
                            <input type="hidden" name="surat_jalan_id" value="<?= htmlspecialchars($ship['surat_jalan_id']) ?>">
                            <button type="submit" class="btn btn-primary w-full py-2 text-xs font-black" style="background:var(--color-success);border-color:var(--color-success);">
                                <i data-lucide="check" class="w-3.5 h-3.5"></i>
                                <span>Setujui &amp; Berangkatkan</span>
                            </button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- MODAL TOLAK PENGIRIMAN KONSINYASI -->
        <div x-show="rejectShipmentModal" x-cloak class="modal-backdrop" @keydown.escape.window="rejectShipmentModal = false">
            <div class="modal-box space-y-4" @click.outside="rejectShipmentModal = false">
                <div class="modal-header">
                    <div class="flex items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(239,68,68,0.12);color:var(--color-danger);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <i data-lucide="x-circle" class="w-4 h-4"></i>
                        </div>
                        <h3 class="modal-title" style="color:var(--color-ink);font-weight:800;font-size:15px;">Tolak Pengiriman Konsinyasi</h3>
                    </div>
                    <button type="button" @click="rejectShipmentModal = false" class="modal-close-btn" aria-label="Tutup">
                        <i data-lucide="x" class="w-4 h-4"></i>
                    </button>
                </div>

                <form action="<?= Router::url('/owner/consignment/reject-delivery') ?>" method="POST" class="space-y-4">
                    <?= \App\Helpers\CSRF::field() ?>
                    <input type="hidden" name="surat_jalan_id" :value="rejectTarget.id">

                    <p style="font-size:12.5px;color:var(--color-ink-secondary);line-height:1.5;">
                        Apakah Anda yakin ingin menolak pengiriman <strong style="color:var(--color-warning);" x-text="rejectTarget.no"></strong> untuk toko <strong style="color:var(--color-ink);" x-text="rejectTarget.toko"></strong>?
                    </p>

                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:var(--color-ink);margin-bottom:4px;">Alasan Penolakan *</label>
                        <textarea name="alasan" rows="3" class="form-input" style="width:100%;font-size:12px;" placeholder="Misal: Stok gudang sedang diprioritaskan untuk order ritel / toko belum melunasi piutang sebelumnya..." required></textarea>
                    </div>

                    <div style="display:flex;gap:8px;padding-top:8px;">
                        <button type="button" @click="rejectShipmentModal = false" class="btn btn-secondary" style="flex:1;font-weight:700;">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger-solid" style="flex:1;font-weight:800;background:var(--color-danger);border-color:var(--color-danger);color:#fff;">
                            Ya, Tolak Pengiriman
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- 2-COLUMN GRID: C1 & C3 (OMZET & KOMISI) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            
            <!-- C1: REKAP OMZET KONSINYASI vs DIRECT ORDER -->
            <div class="card p-4 sm:p-5 space-y-4" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="pie-chart" style="width:16px;height:16px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">C1: Rekap Omzet Konsinyasi (Bulan Ini)</h3>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Perbandingan Konsinyasi vs Direct Orders</div>
                        </div>
                    </div>
                    <span class="font-black text-xs" style="color:var(--color-success);"><?= Format::rupiah((float)$omzetComparison['omzet_konsinyasi']) ?></span>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between items-center p-2 rounded-lg" style="background:var(--color-canvas-soft);">
                        <span style="color:var(--color-ink-mute);">Omzet Konsinyasi (Rak Toko):</span>
                        <span class="font-bold" style="color:var(--color-warning);"><?= Format::rupiah((float)$omzetComparison['omzet_konsinyasi']) ?></span>
                    </div>
                    <div class="flex justify-between items-center p-2 rounded-lg" style="background:var(--color-canvas-soft);">
                        <span style="color:var(--color-ink-mute);">Omzet Direct Order (POS Kasir / Grosir):</span>
                        <span class="font-bold" style="color:var(--color-primary);"><?= Format::rupiah((float)$omzetComparison['omzet_direct']) ?></span>
                    </div>
                </div>

                <!-- TOP 5 TOKO KONSINYASI -->
                <div class="pt-2 border-t space-y-2" style="border-color:var(--color-hairline);">
                    <div style="font-size:11px;font-weight:800;text-transform:uppercase;color:var(--color-ink-mute);">Top 5 Toko Penyumbang Omzet:</div>
                    <div class="space-y-1.5 text-xs">
                        <?php foreach ($topStores as $ts): ?>
                        <div class="flex items-center justify-between p-2.5 rounded-lg" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                            <div>
                                <div style="font-weight:700;color:var(--color-ink);"><?= htmlspecialchars($ts['nama_toko']) ?></div>
                                <div style="font-size:10px;color:var(--color-ink-mute);"><?= $ts['total_kunjungan'] ?> kali kunjungan bulan ini</div>
                            </div>
                            <span class="font-black" style="color:var(--color-success);"><?= Format::rupiah((float)$ts['total_omzet']) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- C3: REKAP KOMISI SALES -->
            <div class="card p-4 sm:p-5 space-y-4" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-3 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2.5">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(37,99,235,0.12);color:var(--color-primary);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="award" style="width:16px;height:16px;"></i>
                        </div>
                        <div>
                            <h3 style="font-size:13.5px;font-weight:800;color:var(--color-ink);">C3: Rekap Performa &amp; Komisi Sales</h3>
                            <div style="font-size:11px;color:var(--color-ink-mute);">Berdasarkan Toko Binaan Tetap Sales</div>
                        </div>
                    </div>
                </div>

                <div class="space-y-2.5 text-xs">
                    <?php if (empty($salesCommissions)): ?>
                    <div class="p-6 text-center" style="color:var(--color-ink-mute);">Belum ada data toko binaan yang di-assign.</div>
                    <?php endif; ?>

                    <?php foreach ($salesCommissions as $sc): ?>
                    <div class="p-3 rounded-xl space-y-1.5" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex items-center justify-between">
                            <div style="font-weight:800;font-size:13px;color:var(--color-ink);"><?= htmlspecialchars($sc['nama_karyawan']) ?></div>
                            <span class="font-black text-sm" style="color:var(--color-ink);"><?= Format::rupiah((float)$sc['total_omzet_laku']) ?></span>
                        </div>
                        <div class="flex items-center justify-between text-[11px]">
                            <span style="color:var(--color-ink-mute);"><?= (int)$sc['total_toko_binaan'] ?> Toko Binaan</span>
                            <span class="font-bold" style="color:var(--color-success);">Komisi (<?= (float)$sc['persentase_komisi'] ?>%): <?= Format::rupiah((float)$sc['estimasi_komisi_rp']) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

        <!-- 3-COLUMN GRID: C4 (AGING PIUTANG), C5 (KERUGIAN RUSAK), C6 (WARNING OVERDUE) -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            
            <!-- C4: OUTSTANDING PIUTANG & AGING -->
            <div class="card p-4 space-y-3 text-xs" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center gap-2 pb-2 border-b" style="border-color:var(--color-hairline);">
                    <div style="width:28px;height:28px;border-radius:8px;background:rgba(239,68,68,0.12);color:var(--color-danger);display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="receipt" style="width:14px;height:14px;"></i>
                    </div>
                    <div style="font-weight:800;color:var(--color-ink);">C4: Aging Piutang Konsinyasi</div>
                </div>

                <div class="p-3 rounded-xl space-y-2" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div class="flex justify-between items-center">
                        <span style="color:var(--color-ink-mute);">&lt; 14 Hari (Aman):</span>
                        <span class="font-bold" style="color:var(--color-success);"><?= Format::rupiah((float)$agingSummary['aging_under_14']) ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color:var(--color-ink-mute);">14–30 Hari (Perhatian):</span>
                        <span class="font-bold" style="color:var(--color-warning);"><?= Format::rupiah((float)$agingSummary['aging_14_to_30']) ?></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span style="color:var(--color-ink-mute);">&gt; 30 Hari (Kritis/Macet):</span>
                        <span class="font-black" style="color:var(--color-danger);"><?= Format::rupiah((float)$agingSummary['aging_over_30']) ?></span>
                    </div>
                </div>

                <div class="space-y-1 pt-1">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:var(--color-ink-mute);">Top Toko Belum Melunasi:</div>
                    <?php foreach ($unpaidStoreList as $us): ?>
                    <div class="flex justify-between items-center py-1 border-b" style="border-color:var(--color-hairline);">
                        <span style="color:var(--color-ink);font-weight:600;"><?= htmlspecialchars($us['nama_toko']) ?></span>
                        <span class="font-bold" style="color:var(--color-danger);"><?= Format::rupiah((float)$us['total_sisa_tagihan']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- C5: LAPORAN KERUGIAN BARANG RUSAK (BS) -->
            <div class="card p-4 space-y-3 text-xs" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center gap-2 pb-2 border-b" style="border-color:var(--color-hairline);">
                    <div style="width:28px;height:28px;border-radius:8px;background:rgba(239,68,68,0.12);color:var(--color-danger);display:flex;align-items:center;justify-content:center;">
                        <i data-lucide="package-x" style="width:14px;height:14px;"></i>
                    </div>
                    <div style="font-weight:800;color:var(--color-ink);">C5: Kerugian Barang Rusak</div>
                </div>

                <div class="p-3 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                    <div style="font-size:11px;color:var(--color-ink-mute);">Total Beban Kerugian HPP (Bulan Ini):</div>
                    <div class="text-lg font-black" style="color:var(--color-danger);"><?= Format::rupiah((float)$lossReport['total_kerugian_rusak']) ?></div>
                    <div style="font-size:10.5px;color:var(--color-ink-mute);"><?= (int)$lossReport['total_pcs_rusak'] ?> pcs barang bocor / hancur</div>
                </div>

                <div class="space-y-1.5 pt-1">
                    <div style="font-size:10px;font-weight:800;text-transform:uppercase;color:var(--color-ink-mute);">SKU Paling Sering Rusak:</div>
                    <?php if (empty($topDamagedItems)): ?>
                    <div class="text-[11px] italic" style="color:var(--color-ink-mute);">Nol laporan retur rusak bulan ini.</div>
                    <?php endif; ?>
                    <?php foreach ($topDamagedItems as $tdi): ?>
                    <div class="flex justify-between items-center py-1 border-b" style="border-color:var(--color-hairline);">
                        <span style="color:var(--color-ink);font-weight:600;"><?= htmlspecialchars($tdi['nama_item']) ?></span>
                        <span class="font-bold" style="color:var(--color-danger);"><?= (int)$tdi['total_pcs_rusak'] ?> pcs (<?= Format::rupiah((float)$tdi['total_nominal_kerugian']) ?>)</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- C6: WARNING TOKO TIDAK DIKUNJUNGI > 14 HARI -->
            <div class="card p-4 space-y-3 text-xs" style="border-radius:16px;background:var(--color-canvas);border:1px solid var(--color-hairline);">
                <div class="flex items-center justify-between pb-2 border-b" style="border-color:var(--color-hairline);">
                    <div class="flex items-center gap-2">
                        <div style="width:28px;height:28px;border-radius:8px;background:rgba(245,158,11,0.12);color:var(--color-warning);display:flex;align-items:center;justify-content:center;">
                            <i data-lucide="alert-triangle" style="width:14px;height:14px;"></i>
                        </div>
                        <div style="font-weight:800;color:var(--color-ink);">C6: Toko Overdue (&gt;14 Hari)</div>
                    </div>
                    <span class="badge badge-warning font-mono text-[10px]"><?= count($overdueStores) ?> Toko</span>
                </div>

                <div class="space-y-2 max-h-[220px] overflow-y-auto pr-1">
                    <?php if (empty($overdueStores)): ?>
                    <div class="p-6 text-center text-xs font-semibold" style="color:var(--color-success);">
                        Semua toko binaan rutin dikunjungi &le;14 hari!
                    </div>
                    <?php endif; ?>

                    <?php foreach ($overdueStores as $os): ?>
                    <div class="p-2.5 rounded-xl space-y-1" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between items-start">
                            <span style="font-weight:700;color:var(--color-ink);"><?= htmlspecialchars($os['nama_toko']) ?></span>
                            <span class="badge badge-danger text-[9.5px]">Perlu Kunjungan</span>
                        </div>
                        <div class="flex justify-between text-[10.5px]" style="color:var(--color-ink-mute);">
                            <span>Sales: <strong style="color:var(--color-ink);"><?= htmlspecialchars($os['nama_sales'] ?? '—') ?></strong></span>
                            <span style="color:var(--color-danger);font-weight:600;"><?= !empty($os['terakhir_opname']) ? date('d M Y', strtotime($os['terakhir_opname'])) : 'Belum Pernah' ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) lucide.createIcons();
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

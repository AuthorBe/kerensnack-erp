<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div class="space-y-4">

    <!-- STAT CARDS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="layers"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Grup Produk</div>
                <div class="stat-card-value"><?= count($groups) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Grup aktif</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="tags"></i>
            </div>
            <div>
                <div class="stat-card-label">Dukungan Matriks</div>
                <div class="stat-card-value" style="font-size:18px;">Dynamic</div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">&ge; 1 level harga</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="users"></i>
            </div>
            <div>
                <div class="stat-card-label">Tier Diskon Toko</div>
                <div class="stat-card-value"><?= count($customerGroups) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Tier pelanggan</div>
            </div>
        </div>

    </div>

    <!-- MATRIKS HARGA -->
    <div class="card">

        <div class="section-header">
            <div>
                <div class="section-title">Matriks Harga per Grup Produk</div>
                <div class="section-subtitle">Tingkat harga diselesaikan otomatis oleh Engine Kalkulasi Realtime.</div>
            </div>
        </div>

        <div style="display:flex;flex-direction:column;gap:12px;">
            <?php foreach ($groups as $g): ?>
            <?php $prices = $groupedPrices[$g['id']] ?? []; ?>

            <div style="padding:16px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">

                <!-- Grup Header -->
                <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:8px;margin-bottom:12px;">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="badge badge-mono"><?= htmlspecialchars($g['kode_grup']) ?></span>
                        <span style="font-size:13px;font-weight:600;color:var(--color-ink);"><?= htmlspecialchars($g['nama_grup']) ?></span>
                    </div>
                    <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-ink-mute-2);">
                        Barcode: <span style="color:var(--color-ink);font-weight:600;"><?= $g['barcode_universal'] ?: '—' ?></span>
                        &bull; 1 Bal = <?= $g['konversi_bal_ke_pcs'] ?> Pcs
                    </div>
                </div>

                <!-- Harga Grid -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                    <?php if (empty($prices)): ?>
                    <div style="grid-column:span 4;font-size:12px;font-family:var(--font-mono);color:var(--color-ink-mute-2);padding:4px 0;">
                        Harga default: HPP &times; 1.25
                    </div>
                    <?php else: ?>
                    <?php foreach ($prices as $p): ?>
                    <?php 
                        $cleanLevelName = trim(preg_replace('/^Level\s*\d+\s*[-–—:]\s*/i', '', $p['nama_level'] ?: ''));
                        if (empty($cleanLevelName)) {
                            $cleanLevelName = 'Standar';
                        }
                    ?>
                    <div style="padding:12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);box-shadow:var(--shadow-1);">
                        <div style="display:flex;align-items:baseline;justify-content:space-between;gap:8px;margin-bottom:8px;">
                            <span style="font-size:12px;font-weight:800;color:#15803d;white-space:nowrap;">Level <?= $p['level_harga'] ?></span>
                            <span style="font-size:11px;font-weight:600;color:var(--color-ink-secondary);text-align:right;white-space:nowrap;" title="<?= htmlspecialchars($p['nama_level']) ?>">
                                <?= htmlspecialchars($cleanLevelName) ?>
                            </span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-family:var(--font-mono);font-size:11.5px;margin-bottom:4px;">
                            <span style="color:var(--color-ink-mute);">Pcs:</span>
                            <strong style="color:var(--color-ink);font-weight:700;"><?= Format::rupiah($p['harga_jual_pcs']) ?></strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-family:var(--font-mono);font-size:11.5px;">
                            <span style="color:var(--color-ink-mute);">Bal:</span>
                            <strong style="color:var(--color-ink);font-weight:700;"><?= Format::rupiah($p['harga_jual_bal']) ?></strong>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
            <?php endforeach; ?>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

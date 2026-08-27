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
                <i data-lucide="store"></i>
            </div>
            <div>
                <div class="stat-card-label">Toko Konsinyasi Aktif</div>
                <div class="stat-card-value"><?= count($consignmentStores) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Mitra aktif</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="layers"></i>
            </div>
            <div>
                <div class="stat-card-label">Titipan di Rak Toko</div>
                <div class="stat-card-value"><?= count($shelfStocks) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Item rak</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:16px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="calendar-check"></i>
            </div>
            <div>
                <div class="stat-card-label">Kunjungan Opname</div>
                <div class="stat-card-value"><?= count($recentVisits) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:4px;">Riwayat</div>
            </div>
        </div>

    </div>

    <!-- TWO-PANE -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        <!-- FORM OPNAME (Kiri) -->
        <div class="card" style="display:flex;flex-direction:column;gap:16px;">

            <div style="display:flex;align-items:center;gap:12px;padding-bottom:14px;border-bottom:1px solid var(--color-hairline);">
                <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);width:32px;height:32px;">
                    <i data-lucide="clipboard-check" style="width:15px;height:15px;"></i>
                </div>
                <div>
                    <div class="section-title">Form Opname Rak Toko</div>
                    <div class="section-subtitle">Hitung sisa fisik saat kunjungan driver.</div>
                </div>
            </div>

            <form action="<?= Router::url('/consignment/opname') ?>" method="POST" style="display:flex;flex-direction:column;gap:14px;">

                <div>
                    <label class="form-label">Toko Konsinyasi</label>
                    <select name="pelanggan_id" required class="form-select">
                        <?php foreach ($consignmentStores as $cs): ?>
                        <option value="<?= $cs['id'] ?>"><?= htmlspecialchars($cs['nama_toko']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Sales-Driver</label>
                    <select name="sales_driver_id" class="form-select">
                        <?php foreach ($drivers as $d): ?>
                        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['nama_karyawan']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Produk Snack</label>
                    <select name="item_id" required class="form-select font-mono" style="font-size:12px;">
                        <?php foreach ($items as $it): ?>
                        <option value="<?= $it['id'] ?>"><?= $it['kode_sku'] ?> — <?= htmlspecialchars($it['nama_item']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;">
                    <div>
                        <label class="form-label" style="text-align:center;display:block;">Sisa di Rak</label>
                        <input type="number" name="sisa_fisik_rak" required min="0" value="0"
                               class="form-input font-mono" style="text-align:center;font-weight:700;">
                    </div>
                    <div>
                        <label class="form-label" style="text-align:center;display:block;color:var(--color-primary);">+ Tambah</label>
                        <input type="number" name="tambah_baru" required min="0" value="0"
                               class="form-input font-mono" style="text-align:center;font-weight:700;color:var(--color-primary);">
                    </div>
                    <div>
                        <label class="form-label" style="text-align:center;display:block;color:var(--color-danger);">Retur</label>
                        <input type="number" name="retur_rusak" required min="0" value="0"
                               class="form-input font-mono" style="text-align:center;font-weight:700;color:var(--color-danger);">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-full" style="justify-content:center;">
                    <i data-lucide="check"></i>
                    Proses Opname &amp; Buat Nota
                </button>
            </form>
        </div>

        <!-- TABEL SALDO RAK (Kanan) -->
        <div class="lg:col-span-2 table-wrapper" style="display:flex;flex-direction:column;">

            <div style="display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--color-hairline);">
                <div style="display:flex;align-items:center;gap:10px;">
                    <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);width:30px;height:30px;">
                        <i data-lucide="box" style="width:14px;height:14px;"></i>
                    </div>
                    <div>
                        <div class="section-title">Saldo Fisik di Rak Toko</div>
                        <div class="section-subtitle">Sinkronisasi otomatis dengan database terpusat</div>
                    </div>
                </div>
            </div>

            <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="min-width:180px;">Toko &amp; Produk</th>
                            <th style="text-align:right;">Stok di Rak</th>
                            <th class="hide-mobile" style="text-align:right;">Terakhir Opname</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($shelfStocks)): ?>
                        <tr>
                            <td colspan="3" style="text-align:center;padding:48px 16px;font-family:var(--font-mono);font-size:12px;color:var(--color-ink-mute-2);">
                                Belum ada data titipan rak toko. Gunakan form di samping untuk mulai opname.
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($shelfStocks as $st): ?>
                        <tr>
                            <td>
                                <div style="font-size:13px;font-weight:600;color:var(--color-ink);"><?= htmlspecialchars($st['nama_toko']) ?></div>
                                <div style="font-size:12px;color:var(--color-ink-secondary);margin-top:2px;"><?= htmlspecialchars($st['nama_item']) ?></div>
                                <div style="font-size:11px;font-family:var(--font-mono);color:var(--color-primary);margin-top:1px;"><?= $st['kode_sku'] ?></div>
                            </td>
                            <td style="text-align:right;">
                                <span style="font-family:var(--font-mono);font-weight:700;font-size:14px;color:var(--color-primary);"><?= $st['stok_titip_saat_ini'] ?></span>
                                <span style="font-size:11px;color:var(--color-ink-mute-2);">pcs</span>
                            </td>
                            <td class="hide-mobile" style="text-align:right;font-family:var(--font-mono);font-size:11px;color:var(--color-ink-mute);">
                                <?= Format::tanggal($st['terakhir_opname_pada'], true) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>

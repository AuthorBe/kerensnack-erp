<?php
use App\Helpers\Format;
use App\Core\Router;
ob_start();
?>

<div x-data="purchaseApp()" x-init="init()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-blue">
                <i data-lucide="receipt"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#3b82f6;"></span>
                    <span>Pengadaan Bahan Gudang</span>
                </div>
                <h1 class="page-title"><?= $pageTitle ?? 'Pembelian &amp; Faktur Vendor' ?></h1>
                <p class="page-subtitle"><?= $pageSubtitle ?? 'Penerimaan Bahan Mentah, Bumbu &amp; Kemasan dari Supplier' ?></p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
            <button type="button" @click="showGuideModal = true; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });" class="btn btn-ghost" style="font-weight:700;color:var(--color-primary);background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.25);" title="Lihat Panduan Alur Pembelian &amp; PO">
                <i data-lucide="book-open" style="width:15px;height:15px;"></i>
                <span class="hidden sm:inline">Panduan Alur</span>
            </button>
            <button @click="openAddModal('faktur')" class="btn btn-secondary" style="font-weight:700;">
                <i data-lucide="receipt"></i>
                <span>+ Catat Faktur Langsung</span>
            </button>
            <button @click="openAddModal('po')" class="btn btn-primary" style="font-weight:700;background:var(--color-primary-deep,#059669);border-color:var(--color-primary-deep,#059669);">
                <i data-lucide="shopping-cart"></i>
                <span>+ Buat PO Pembelian</span>
            </button>
        </div>
    </div>

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(62,207,142,0.1);color:var(--color-primary);">
                <i data-lucide="file-check"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Faktur Pembelian</div>
                <div class="stat-card-value"><?= count(array_filter($purchases, fn($p) => $p['status_pembayaran'] !== 'batal')) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Faktur &amp; PO aktif (non-batal)</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);color:#3b82f6;">
                <i data-lucide="coins"></i>
            </div>
            <div>
                <div class="stat-card-label">Total Belanja Bahan</div>
                <div class="stat-card-value" style="color:#3b82f6;">
                    <?= Format::rupiah(array_sum(array_map(fn($p) => $p['status_pembayaran'] !== 'batal' ? (float)$p['total_biaya'] : 0, $purchases))) ?>
                </div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Akumulasi faktur &amp; PO aktif</div>
            </div>
        </div>

        <div class="stat-card" style="display:flex;align-items:center;gap:14px;">
            <div class="stat-card-icon" style="background:rgba(245,158,11,0.1);color:#f59e0b;">
                <i data-lucide="truck"></i>
            </div>
            <div>
                <div class="stat-card-label">Vendor Terlibat</div>
                <div class="stat-card-value" style="color:#f59e0b;"><?= count($suppliers) ?></div>
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Supplier aktif</div>
            </div>
        </div>
    </div>

    <!-- MAIN CARD -->
    <div class="card" style="padding:0;overflow:hidden;">

        <!-- FILTER & ACTION BAR -->
        <div class="flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3 p-4 border-b" style="border-color:var(--color-hairline);background-color:var(--color-canvas);">
            <div class="flex flex-col sm:flex-row items-center gap-2 flex-1">
                <!-- Search Input -->
                <div class="form-input-icon flex-1 w-full sm:max-w-xs">
                    <i data-lucide="search" class="icon-left" style="color:var(--color-ink-mute);"></i>
                    <input type="text" x-model="searchQuery" placeholder="Cari nomor dokumen / vendor / driver..." class="form-input" style="height:38px;font-size:13px;">
                </div>

                <!-- Filter Tipe Dokumen -->
                <select x-model="filterType" class="form-input w-full sm:w-auto" style="height:38px;font-size:12.5px;min-width:150px;">
                    <option value="semua">Semua Tipe (PB)</option>
                    <option value="faktur">Faktur Langsung (PB)</option>
                    <option value="po">PO Pembelian (PB)</option>
                </select>

                <!-- Filter Status Bayar -->
                <select x-model="filterStatus" class="form-input w-full sm:w-auto" style="height:38px;font-size:12.5px;min-width:140px;">
                    <option value="semua">Semua Status Bayar</option>
                    <option value="lunas">Lunas</option>
                    <option value="belum_lunas">Tempo (Belum Lunas)</option>
                    <option value="batal">Dibatalkan</option>
                </select>

                <!-- Filter Vendor Pemasok -->
                <select x-model="filterSupplier" class="form-input w-full sm:w-auto" style="height:38px;font-size:12.5px;min-width:160px;">
                    <option value="semua">Semua Vendor</option>
                    <?php foreach ($suppliers as $s): ?>
                    <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_pemasok']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="font-size:12px;color:var(--color-ink-mute);display:flex;align-items:center;gap:6px;">
                <span>Menampilkan:</span>
                <strong style="color:var(--color-ink);" x-text="filteredPurchases.length + ' Dokumen'"></strong>
            </div>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="width:170px; min-width:150px;" class="cell-nowrap">No. Dokumen</th>
                        <th style="width:110px; min-width:95px;" class="cell-nowrap">Tanggal</th>
                        <th style="min-width:190px;">Vendor &amp; Logistik</th>
                        <th class="cell-right cell-nowrap" style="width:140px; min-width:120px;">Total Biaya</th>
                        <th class="cell-center cell-nowrap" style="width:120px; min-width:110px;">Status Bayar</th>
                        <th class="cell-center cell-nowrap" style="width:125px; min-width:115px;">Penerimaan</th>
                        <th class="cell-center cell-nowrap" style="width:75px;">Nota</th>
                        <th class="cell-center cell-nowrap" style="width:100px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="pb in filteredPurchases" :key="pb.id">
                        <tr>
                            <td class="cell-nowrap">
                                <button @click="openDetailModal(pb.id)" class="badge badge-mono cursor-pointer" style="font-weight:700;letter-spacing:0.3px;" title="Klik untuk lihat rincian transaksi">
                                    <span x-text="pb.nomor_faktur_pembelian"></span>
                                </button>
                                <div style="margin-top:3px;display:flex;gap:4px;flex-wrap:wrap;">
                                    <template x-if="pb.jenis_dokumen === 'po'">
                                        <span>
                                            <template x-if="pb.metode_logistik === 'diambil_driver'">
                                                <span class="badge" style="font-size:9.5px;padding:1px 6px;background:#fef3c7;color:#92400e;border:1px solid #fde68a;">&#x1F69A; PO DRIVER</span>
                                            </template>
                                            <template x-if="pb.metode_logistik !== 'diambil_driver'">
                                                <span class="badge" style="font-size:9.5px;padding:1px 6px;background:#e0e7ff;color:#3730a3;border:1px solid #c7d2fe;">&#x1F3E2; PO SUPPLIER</span>
                                            </template>
                                        </span>
                                    </template>
                                    <template x-if="pb.jenis_dokumen !== 'po'">
                                        <span class="badge badge-info" style="font-size:9.5px;padding:1px 6px;">FAKTUR LANGSUNG</span>
                                    </template>
                                </div>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="(pb.total_items || 0) + ' jenis item'"></div>
                            </td>
                            <td class="cell-nowrap" style="font-family:var(--font-mono);font-size:12px;">
                                <span x-text="pb.tanggal_pembelian"></span>
                                <template x-if="pb.jenis_dokumen === 'po' && pb.tanggal_jadwal_belanja && pb.tanggal_jadwal_belanja !== pb.tanggal_pembelian">
                                    <div style="font-size:10px;color:var(--color-ink-mute);margin-top:2px;">
                                        Jadwal: <span x-text="pb.tanggal_jadwal_belanja"></span>
                                    </div>
                                </template>
                            </td>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);" x-text="pb.nama_pemasok || '-'"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
                                    <span x-text="pb.kode_pemasok || ''"></span>
                                    <template x-if="pb.supplier_telepon">
                                        <span x-text="'\u2022 ' + pb.supplier_telepon"></span>
                                    </template>
                                </div>
                                <template x-if="pb.nama_driver">
                                    <div style="font-size:11px;color:#2563eb;display:flex;align-items:center;gap:4px;margin-top:3px;font-weight:600;">
                                        <i data-lucide="truck" style="width:12px;height:12px;flex-shrink:0;"></i>
                                        <span x-text="'Driver: ' + pb.nama_driver"></span>
                                    </div>
                                </template>
                            </td>
                            <td class="cell-currency cell-right cell-nowrap" style="color:var(--color-primary-deep);font-weight:700;" x-text="formatRupiah(pb.total_biaya)"></td>
                            <td class="cell-center cell-nowrap">
                                <span class="badge" 
                                      :class="{
                                          'badge-primary': pb.status_pembayaran === 'lunas',
                                          'badge-warning': pb.status_pembayaran === 'belum_lunas',
                                          'badge-danger': pb.status_pembayaran === 'batal'
                                      }" 
                                      style="text-transform:capitalize;" 
                                      x-text="pb.status_pembayaran === 'belum_lunas' ? 'Tempo (Hutang)' : (pb.status_pembayaran === 'batal' ? 'Dibatalkan' : 'Lunas')">
                                </span>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <template x-if="pb.status_penerimaan === 'diterima'">
                                    <span class="badge badge-success" style="font-size:11px;font-weight:700;">Diterima Gudang</span>
                                </template>
                                <template x-if="pb.status_penerimaan === 'ditugaskan_driver'">
                                    <span class="badge" style="background:#fef3c7;color:#92400e;border:1px solid #fde68a;font-size:11px;font-weight:700;">Tugas Driver</span>
                                </template>
                                <template x-if="pb.status_penerimaan === 'menunggu_supplier'">
                                    <span class="badge" style="background:#e0e7ff;color:#3730a3;border:1px solid #c7d2fe;font-size:11px;font-weight:700;">Tunggu Supplier</span>
                                </template>
                                <template x-if="pb.status_penerimaan === 'sudah_diambil'">
                                    <span class="badge" style="background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;font-size:11px;font-weight:700;">Diambil Driver</span>
                                </template>
                                <template x-if="pb.status_penerimaan === 'kendala_batal'">
                                    <span class="badge badge-danger" style="font-size:11px;font-weight:700;">Kendala / Batal</span>
                                </template>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <template x-if="pb.url_foto_nota">
                                    <button type="button" @click="openReceiptPreview(pb.url_foto_nota, pb.nomor_faktur_pembelian)" class="btn btn-ghost btn-sm" style="padding:4px;color:#3b82f6;" title="Lihat Foto Bukti Nota">
                                        <i data-lucide="image" style="width:16px;height:16px;"></i>
                                    </button>
                                </template>
                                <template x-if="!pb.url_foto_nota">
                                    <span style="color:var(--color-ink-mute-2);font-size:12px;">-</span>
                                </template>
                            </td>
                            <td class="cell-center cell-nowrap">
                                <div style="display:inline-flex;align-items:center;gap:6px;">
                                    <template x-if="pb.status_penerimaan !== 'diterima' && pb.status_pembayaran !== 'batal' && pb.status_penerimaan !== 'kendala_batal'">
                                        <button type="button" @click="openQuickReceive(pb.id)" class="btn btn-sm" style="background:#059669;color:#ffffff;border-color:#059669;font-weight:700;font-size:11.5px;padding:4px 9px;border-radius:9px;display:inline-flex;align-items:center;gap:4px;" title="Verifikasi &amp; Terima Barang Fisik">
                                            <i data-lucide="package-check" style="width:13px;height:13px;"></i>
                                            <span>Terima</span>
                                        </button>
                                    </template>
                                    <button type="button" @click="openDetailModal(pb.id)" class="btn btn-secondary btn-sm" style="font-weight:700;font-size:12px;padding:5px 12px;border-radius:9px;display:inline-flex;align-items:center;gap:5px;">
                                        <i data-lucide="eye" style="width:14px;height:14px;color:var(--color-primary);"></i>
                                        <span>Detail</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <template x-if="filteredPurchases.length === 0">
                        <tr>
                            <td colspan="8" style="text-align:center;padding:36px;color:var(--color-ink-mute);">
                                <i data-lucide="search-x" style="width:36px;height:36px;margin:0 auto 8px auto;opacity:0.5;"></i>
                                <div style="font-weight:600;font-size:13px;">Belum ada riwayat faktur pembelian</div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL INPUT FAKTUR PEMBELIAN (BERJENJANG / PROGRESSIVE)                    -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showModal" x-cloak class="modal-backdrop" @keydown.window="handleModalKeydown($event)">
        <div class="modal-box purchase-modal-box" style="max-width:740px;">
            <div class="modal-header pb-2.5 mb-1 border-b border-hairline">
                <div>
                    <div class="modal-title" style="font-size:15px;" x-text="form.jenis_dokumen === 'po' ? 'Buat PO Pembelian Bahan (Purchase Order)' : 'Catat Faktur Pembelian Bahan Vendor (Langsung)'"></div>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;" x-text="form.jenis_dokumen === 'po' ? 'Penerbitan surat pesanan ke vendor &amp; penugasan armada logistik driver' : 'Penerimaan stok bahan baku mentah &amp; kemasan langsung masuk ke gudang'"></div>
                </div>
                <button @click="showModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">

                <!-- LANGKAH 1: VENDOR & DATA DOKUMEN -->
                <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);padding:12px 14px;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:10px;font-size:12px;font-weight:700;color:var(--color-ink);">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#3b82f6;color:#fff;font-size:11px;flex-shrink:0;">1</span>
                        <span>Informasi Pemasok &amp; Dokumen</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Vendor Pemasok *</label>
                            <select id="purchase-vendor-select" x-model="form.pemasok_id" @change="onSupplierChange()" @keydown.enter.prevent="focusAfterVendor()" class="form-input" style="font-weight:600;">
                                <option value="">-- Pilih Vendor Pemasok --</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_pemasok']) ?> (<?= htmlspecialchars($s['kode_pemasok']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Nomor Dokumen (Otomatis)</label>
                            <div class="input-group-addon">
                                <span class="addon-prefix" style="background:var(--color-canvas-soft);color:var(--color-ink-secondary);font-weight:700;">PB-</span>
                                <input type="text" x-model="form.nomor_faktur_suffix" readonly
                                       class="form-input font-mono uppercase addon-input" 
                                       style="background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink);font-weight:700;" 
                                       placeholder="<?= $suggestedPbSuffix ?>">
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:3px;display:flex;align-items:center;gap:4px;">
                                <i data-lucide="lock" style="width:12px;height:12px;color:var(--color-primary);"></i>
                                <span>Nomor dokumen berawalan <strong>PB-</strong> di-generate otomatis oleh sistem.</span>
                            </div>
                        </div>
                    </div>

                    <!-- KARTU DETAIL VENDOR TERPILIH -->
                    <template x-if="selectedSupplier">
                        <div style="margin-top:10px;padding:10px 12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:8px;font-size:11.5px;display:flex;flex-direction:column;gap:8px;color:var(--color-ink-secondary);">
                            <!-- Baris 1: Kontak & Rekening Bank -->
                            <div style="display:flex;flex-wrap:wrap;gap:8px 12px;align-items:center;">
                                <div style="display:flex;align-items:center;gap:4px;">
                                    <i data-lucide="phone" style="width:13px;height:13px;color:var(--color-primary);flex-shrink:0;"></i>
                                    <span x-text="selectedSupplier.nomor_telepon || 'Tanpa No. Telp'"></span>
                                </div>
                                <template x-if="selectedSupplier.nama_bank && selectedSupplier.nomor_rekening">
                                    <div style="display:flex;align-items:center;gap:4px;">
                                        <i data-lucide="credit-card" style="width:13px;height:13px;color:#3b82f6;flex-shrink:0;"></i>
                                        <span x-text="selectedSupplier.nama_bank + ': ' + selectedSupplier.nomor_rekening + ' (a.n ' + (selectedSupplier.atas_nama_rekening || '-') + ')'"></span>
                                    </div>
                                </template>
                                <template x-if="selectedSupplier.alamat_lengkap && selectedSupplier.alamat_lengkap !== '-'">
                                    <div style="display:flex;align-items:center;gap:4px;">
                                        <i data-lucide="map-pin" style="width:13px;height:13px;color:#f59e0b;flex-shrink:0;"></i>
                                        <span class="truncate" style="max-width:260px;" x-text="selectedSupplier.alamat_lengkap"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Baris 2: Ringkasan Jumlah Item yang Dijual Pemasok -->
                            <div style="padding-top:6px;border-top:1px dashed var(--color-hairline);display:flex;flex-wrap:wrap;align-items:center;gap:6px;font-size:11px;">
                                <div style="display:flex;align-items:center;gap:4px;font-weight:600;color:var(--color-ink);">
                                    <i data-lucide="package" style="width:13px;height:13px;color:var(--color-primary);flex-shrink:0;"></i>
                                    <span>Jumlah Item Dijual:</span>
                                </div>
                                
                                <template x-if="supplierItems.length > 0">
                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                        <span class="badge badge-primary" style="font-size:10.5px;font-weight:600;" x-text="supplierItems.length + ' Item'"></span>
                                        <span style="color:var(--color-ink-mute);font-size:10.5px;">(bahan baku / kemasan terdaftar)</span>
                                    </div>
                                </template>

                                <template x-if="supplierItems.length === 0">
                                    <div style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;">
                                        <span class="badge badge-warning" style="font-size:10px;">0 Item</span>
                                        <span style="color:var(--color-ink-mute);font-size:10.5px;">(Gunakan centang <em>"Semua Bahan"</em> di langkah 2 jika diperlukan)</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- KHUSUS MODE PO PEMBELIAN: PENGATURAN LOGISTIK & DRIVER -->
                    <template x-if="form.jenis_dokumen === 'po'">
                        <div style="margin-top:12px;padding:12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:10px;display:flex;flex-direction:column;gap:12px;">
                            <div style="display:flex;align-items:center;gap:6px;font-size:11.5px;font-weight:700;color:var(--color-ink);">
                                <i data-lucide="truck" style="width:14px;height:14px;color:var(--color-primary);"></i>
                                <span>Metode Pengambilan &amp; Logistik PO</span>
                            </div>

                            <!-- Opsi Logistik: Diambil Driver vs Diantar Supplier -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="po-radio-card cursor-pointer" 
                                       tabindex="0"
                                       :class="form.metode_logistik === 'diambil_driver' ? 'is-selected' : ''" 
                                       @click="form.metode_logistik = 'diambil_driver'"
                                       @keydown.space.prevent="form.metode_logistik = 'diambil_driver'"
                                       @keydown.enter.prevent="form.metode_logistik = 'diambil_driver'">
                                    <input type="radio" x-model="form.metode_logistik" value="diambil_driver" style="display:none;">
                                    <div class="po-radio-body">
                                        <span class="po-radio-icon">&#x1F69A;</span>
                                        <div>
                                            <div class="po-radio-title">Diambil Driver Toko</div>
                                            <div class="po-radio-desc">Masuk ke rute belanja driver di menu <em>Pengiriman Driver</em></div>
                                        </div>
                                    </div>
                                </label>

                                <label class="po-radio-card cursor-pointer" 
                                       tabindex="0"
                                       :class="form.metode_logistik === 'diantar_supplier' ? 'is-selected' : ''" 
                                       @click="form.metode_logistik = 'diantar_supplier'"
                                       @keydown.space.prevent="form.metode_logistik = 'diantar_supplier'"
                                       @keydown.enter.prevent="form.metode_logistik = 'diantar_supplier'">
                                    <input type="radio" x-model="form.metode_logistik" value="diantar_supplier" style="display:none;">
                                    <div class="po-radio-body">
                                        <span class="po-radio-icon">&#x1F3E2;</span>
                                        <div>
                                            <div class="po-radio-title">Diantar oleh Supplier</div>
                                            <div class="po-radio-desc">Supplier mengirimkan barang langsung ke gudang</div>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            <!-- Parameter Driver jika diambil_driver -->
                            <div x-show="form.metode_logistik === 'diambil_driver'">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="form-label">Pilih Driver Penanggung Jawab *</label>
                                        <select x-model="form.sales_driver_id" class="form-input" style="font-weight:600;">
                                            <option value="">-- Pilih Armada Driver --</option>
                                            <?php foreach ($drivers as $dr): ?>
                                            <option value="<?= $dr['id'] ?>">
                                                <?= htmlspecialchars($dr['nama_karyawan']) ?> <?= !empty($dr['nomor_polisi_kendaraan']) ? ' (' . htmlspecialchars($dr['nomor_polisi_kendaraan']) . ')' : '' ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Tanggal Jadwal Belanja Driver *</label>
                                        <input type="date" x-model="form.tanggal_jadwal_belanja" class="form-input font-mono">
                                    </div>
                                </div>
                            </div>

                            <!-- Metode Bayar Belanja Driver jika diambil_driver -->
                            <div x-show="form.metode_logistik === 'diambil_driver'">
                                <label class="form-label">Metode Pembayaran Saat Belanja di Lokasi *</label>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                    <label class="po-radio-card-sm cursor-pointer" 
                                           tabindex="0"
                                           :class="form.metode_bayar_belanja === 'tunai_driver' ? 'is-selected' : ''" 
                                           @click="form.metode_bayar_belanja = 'tunai_driver'"
                                           @keydown.space.prevent="form.metode_bayar_belanja = 'tunai_driver'"
                                           @keydown.enter.prevent="form.metode_bayar_belanja = 'tunai_driver'">
                                        <input type="radio" x-model="form.metode_bayar_belanja" value="tunai_driver" style="display:none;">
                                        <div class="po-radio-body-sm">
                                            <span class="po-radio-icon-sm">&#x1F4B5;</span>
                                            <div>
                                                <div class="po-radio-title-sm">Kas Tunai Driver</div>
                                                <div class="po-radio-desc-sm">Dibekali dana toko</div>
                                            </div>
                                        </div>
                                    </label>
                                    <label class="po-radio-card-sm cursor-pointer" 
                                           tabindex="0"
                                           :class="form.metode_bayar_belanja === 'transfer_kantor' ? 'is-selected' : ''" 
                                           @click="form.metode_bayar_belanja = 'transfer_kantor'"
                                           @keydown.space.prevent="form.metode_bayar_belanja = 'transfer_kantor'"
                                           @keydown.enter.prevent="form.metode_bayar_belanja = 'transfer_kantor'">
                                        <input type="radio" x-model="form.metode_bayar_belanja" value="transfer_kantor" style="display:none;">
                                        <div class="po-radio-body-sm">
                                            <span class="po-radio-icon-sm">&#x1F4B3;</span>
                                            <div>
                                                <div class="po-radio-title-sm">Transfer Kantor</div>
                                                <div class="po-radio-desc-sm">Tempo / dibayar kantor</div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <!-- Instruksi Driver -->
                            <div x-show="form.metode_logistik === 'diambil_driver'">
                                <label class="form-label">Instruksi Khusus untuk Driver (Opsional)</label>
                                <input type="text" x-model="form.instruksi_driver" class="form-input" placeholder="Contoh: Ambil setelah pengiriman toko selesai, minta nota stempel asli...">
                            </div>

                            <!-- Parameter jika diantar supplier -->
                            <div x-show="form.metode_logistik === 'diantar_supplier'">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <div>
                                        <label class="form-label">Tanggal Perkiraan Pengiriman Tiba</label>
                                        <input type="date" x-model="form.tanggal_jadwal_belanja" class="form-input font-mono">
                                    </div>
                                    <div>
                                        <label class="form-label">Status Pembayaran PO</label>
                                        <select x-model="form.status_pembayaran" class="form-input">
                                            <option value="belum_lunas">Tempo (Hutang Vendor)</option>
                                            <option value="lunas">Sudah Lunas / Transfer Dimuka</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert Edukasi PO -->
                            <div style="display:flex;align-items:flex-start;gap:8px;padding:9px 12px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.25);border-radius:8px;font-size:11px;color:#1e40af;line-height:1.45;">
                                <i data-lucide="info" style="width:15px;height:15px;flex-shrink:0;margin-top:1px;"></i>
                                <div>
                                    <strong>Ketentuan Sistem PO:</strong> Stok fisik gudang dan saldo kas <strong>TIDAK</strong> akan dipotong saat PO diterbitkan. Verifikasi fisik barang dan penambahan stok akan dilakukan saat konfirmasi penerimaan di gudang.
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- KHUSUS MODE FAKTUR LANGSUNG (IMMEDIATE) -->
                    <template x-if="form.jenis_dokumen !== 'po'">
                        <div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3" style="margin-top:10px;">
                                <div>
                                    <label class="form-label">Tanggal Pembelian</label>
                                    <input type="date" x-model="form.tanggal_pembelian" class="form-input font-mono">
                                </div>
                                <div>
                                    <label class="form-label">Status Pembayaran</label>
                                    <select x-model="form.status_pembayaran" class="form-input">
                                        <option value="lunas">Lunas (Tunai/Bank)</option>
                                        <option value="belum_lunas">Hutang (Tempo)</option>
                                    </select>
                                </div>
                                <div x-show="form.status_pembayaran === 'lunas'">
                                    <label class="form-label">Akun Kas Sumber Dana *</label>
                                    <select x-model="form.akun_kas_id" class="form-input">
                                        <?php foreach ($cashAccounts as $ca): ?>
                                        <option value="<?= $ca['id'] ?>">
                                            <?= htmlspecialchars($ca['nama_akun']) ?> (<?= Format::rupiah((float)$ca['saldo_saat_ini']) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <!-- FOTO BUKTI NOTA FISIK -->
                            <div style="margin-top:10px;">
                                <label class="form-label">Upload Foto Nota Fisik / Surat Jalan Vendor (Opsional)</label>
                                <div style="display:flex;align-items:center;gap:10px;">
                                    <input type="file" x-ref="photoFileInput" @change="handleFileChange($event)" accept="image/*" capture="environment" class="form-input" style="padding:6px 10px;font-size:12px;flex:1;min-width:0;">
                                    <template x-if="photoPreview">
                                        <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                            <img :src="photoPreview" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid var(--color-hairline);" alt="Preview">
                                            <button type="button" @click="clearPhoto()" class="btn btn-ghost btn-sm" style="color:var(--color-danger);padding:4px;">
                                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- LANGKAH 2: RINCIAN BARANG DITERIMA -->
                <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 pb-2 mb-2 border-b border-hairline">
                        <div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--color-ink);">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#3b82f6;color:#fff;font-size:11px;flex-shrink:0;">2</span>
                            <span>Daftar Bahan Baku / Kemasan Masuk</span>
                        </div>

                        <div x-show="form.pemasok_id" class="flex items-center justify-between sm:justify-end gap-2.5 flex-wrap">
                            <!-- Toggle Tampilkan Semua Bahan (Supplier Alternatif) -->
                            <label class="flex items-center gap-1.5 cursor-pointer" style="font-size:11px;color:var(--color-ink-secondary);user-select:none;">
                                <input type="checkbox" x-model="showAllMaterials" class="form-checkbox" style="width:14px;height:14px;border-radius:4px;">
                                <span>Semua Bahan (Alternatif)</span>
                            </label>

                            <button @click="addItemRowAndFocus()" type="button" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:11.5px;display:inline-flex;align-items:center;gap:4px;" title="Tambah Baris (Alt+N)">
                                <i data-lucide="plus" style="width:13px;height:13px;"></i>
                                <span>Tambah Baris</span>
                                <span class="kbd-badge hidden sm:inline" style="font-size:9.5px;padding:0.5px 4px;">Alt+N</span>
                            </button>
                        </div>
                    </div>

                    <!-- KONDISI TERKUNCI JIKA SUPPLIER BELUM DIPILIH -->
                    <template x-if="!form.pemasok_id">
                        <div style="text-align:center;padding:32px 20px;background:var(--color-canvas);border:1px dashed var(--color-hairline);border-radius:8px;color:var(--color-ink-mute);">
                            <i data-lucide="lock" style="width:32px;height:32px;margin:0 auto 8px auto;opacity:0.4;"></i>
                            <div style="font-weight:600;font-size:13px;color:var(--color-ink);">Pilih Vendor Pemasok Terlebih Dahulu</div>
                            <div style="font-size:11.5px;margin-top:2px;">Silakan tentukan vendor pada langkah 1 di atas untuk memuat daftar bahan yang disediakan.</div>
                        </div>
                    </template>

                    <!-- TABEL INPUT ITEM (JIKA SUPPLIER SUDAH DIPILIH) -->
                    <template x-if="form.pemasok_id">
                        <div>
                            <!-- Column Header Labels (Desktop Only) -->
                            <div class="purchase-item-desktop-header">
                                <div style="flex:1;min-width:0;">Nama Bahan Baku / SKU</div>
                                <div style="width:100px;flex-shrink:0;">Kuantitas</div>
                                <div style="width:140px;flex-shrink:0;">Harga Satuan (Rp)</div>
                                <div style="width:36px;flex-shrink:0;"></div>
                            </div>

                            <div style="display:flex;flex-direction:column;gap:8px;min-height:140px;overflow:visible;">
                                <template x-for="(row, idx) in form.items" :key="idx">
                                    <div class="purchase-item-row">
                                        <!-- Mobile Card Top Bar (Item #, Subtotal, Delete) -->
                                        <div class="purchase-item-mobile-header">
                                            <div style="display:flex;align-items:center;gap:8px;">
                                                <span class="badge badge-mono" style="font-size:10.5px;font-weight:700;" x-text="'Item #' + (idx + 1)"></span>
                                                <span style="font-size:11.5px;font-weight:700;color:var(--color-primary-deep);font-family:var(--font-mono);" x-text="formatRupiah(row.subtotal || 0)"></span>
                                            </div>
                                            <button @click="removeItemRow(idx)" type="button" class="btn btn-ghost btn-sm" style="color:var(--color-danger);padding:4px;" title="Hapus Baris (Ctrl+Del)">
                                                <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                            </button>
                                        </div>

                                        <!-- Searchable Item Dropdown -->
                                        <div class="relative purchase-item-select-col" @click.outside="row.dropdownOpen = false">
                                            <label class="purchase-item-label">Bahan Baku / Kemasan *</label>
                                            <button type="button" 
                                                    :id="'item-btn-' + idx"
                                                    @click="toggleItemDropdown(row, idx)"
                                                    @keydown.enter.stop.prevent="openItemDropdown(row, idx)"
                                                    @keydown.space.stop.prevent="openItemDropdown(row, idx)"
                                                    @keydown.down.stop.prevent="openItemDropdown(row, idx)"
                                                    class="form-input flex items-center justify-between w-full text-left"
                                                    style="height:36px;font-size:12px;font-weight:600;border-radius:8px;cursor:pointer;background:var(--color-canvas);padding:0 8px;">
                                                <span class="truncate" :style="!row.item_id ? 'color:var(--color-ink-mute);font-weight:500;' : 'color:var(--color-ink);'"
                                                      x-text="getSelectedItemName(row.item_id)"></span>
                                                <i data-lucide="chevron-down" style="width:13px;height:13px;flex-shrink:0;transition:transform 0.2s;" :style="row.dropdownOpen ? 'transform:rotate(180deg)' : ''"></i>
                                            </button>

                                            <div x-show="row.dropdownOpen" x-cloak
                                                 class="dropdown-menu-searchable purchase-dropdown-menu">
                                                <div style="padding:6px 8px;border-bottom:1px solid var(--color-hairline);background:var(--color-canvas-soft);">
                                                    <div style="position:relative;display:flex;align-items:center;">
                                                        <i data-lucide="search" style="position:absolute;left:8px;width:13px;height:13px;color:var(--color-ink-mute);pointer-events:none;"></i>
                                                        <input type="text" 
                                                               :id="'item-search-' + idx"
                                                               x-model="row.search"
                                                               @input="row.activeIndex = 0; scrollItemIntoView(idx, 0)"
                                                               @keydown.down.prevent="navigateDropdownItem(row, idx, 1)"
                                                               @keydown.up.prevent="navigateDropdownItem(row, idx, -1)"
                                                               @keydown.enter.prevent.stop="selectActiveDropdownItem(row, idx)"
                                                               @keydown.escape.prevent.stop="closeItemDropdown(row, idx)"
                                                               @keydown.tab="closeItemDropdown(row, idx)"
                                                               placeholder="Cari nama barang / SKU..."
                                                               class="form-input"
                                                               style="height:30px;padding-left:26px;font-size:11.5px;border-radius:6px;width:100%;background:var(--color-canvas);">
                                                    </div>
                                                </div>
                                                <div :id="'item-opt-list-' + idx" style="max-height:180px;overflow-y:auto;" class="custom-scrollbar">
                                                    <template x-for="(it, optIdx) in getFilteredItems(row)" :key="it.id">
                                                        <div :id="'item-opt-' + idx + '-' + optIdx"
                                                             @click="selectItemRow(row, idx, it)"
                                                             class="searchable-option"
                                                             :class="{ 'is-selected': String(it.id) === String(row.item_id), 'is-active': optIdx === (row.activeIndex || 0) }"
                                                             style="padding:8px 10px;font-size:11.5px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:6px;border-bottom:1px solid var(--color-hairline-soft);">
                                                            <div style="min-width:0;flex:1;">
                                                                <div style="font-weight:700;color:var(--color-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="it.nama_item"></div>
                                                                <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="(it.tipe_item === 'bahan_mentah' ? 'Bahan Mentah' : 'Bahan Kemas') + ' \u2022 Satuan: ' + it.satuan_dasar"></div>
                                                            </div>
                                                            <span class="badge badge-mono" style="font-size:10px;" x-text="formatRupiah(it.harga_pokok_pembelian)"></span>
                                                        </div>
                                                    </template>
                                                    <template x-if="getFilteredItems(row).length === 0">
                                                        <div style="padding:16px 12px;text-align:center;font-size:11.5px;color:var(--color-ink-mute);">
                                                            <div>Tidak ada bahan terdaftar untuk vendor ini.</div>
                                                            <div style="font-size:10.5px;margin-top:3px;color:#3b82f6;">Centang "Semua Bahan" di atas jika pengadaan dari vendor cadangan.</div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Row inputs (Qty & Price) -->
                                        <div class="purchase-item-inputs-row">
                                            <!-- Qty with Unit Badge -->
                                            <div class="purchase-item-qty-col">
                                                <label class="purchase-item-label">Kuantitas</label>
                                                <div class="relative flex items-center">
                                                    <input type="number" step="any" min="0.01" 
                                                           :id="'item-qty-' + idx"
                                                           x-model.number="row.qty" 
                                                           placeholder="Qty" 
                                                           class="form-input font-mono" 
                                                           style="font-size:12px;height:36px;padding-right:38px;width:100%;" 
                                                           @input="recalcRow(idx)"
                                                           @focus="$event.target.select()"
                                                           @click="$event.target.select()"
                                                           @keydown.enter.prevent="onQtyEnter(row, idx)"
                                                           @keydown.down.prevent="moveRowField(idx, 1, 'qty')"
                                                           @keydown.up.prevent="moveRowField(idx, -1, 'qty')"
                                                           @keydown.delete.ctrl.prevent="removeItemRow(idx)"
                                                           @keydown.backspace.ctrl.prevent="removeItemRow(idx)">
                                                    <span style="position:absolute;right:8px;font-size:10.5px;font-weight:700;color:var(--color-ink-mute);pointer-events:none;" x-text="getSelectedItemUnit(row.item_id)"></span>
                                                </div>
                                            </div>

                                            <!-- Harga Satuan -->
                                            <div class="purchase-item-price-col">
                                                <label class="purchase-item-label">Harga Satuan (Rp)</label>
                                                <input type="text" 
                                                       :id="'item-price-' + idx"
                                                       x-model="row.harga_satuan" 
                                                       placeholder="Harga" 
                                                       class="form-input font-mono input-rupiah" 
                                                       style="font-size:12px;height:36px;width:100%;" 
                                                       @input="recalcRow(idx)"
                                                       @focus="$event.target.select()"
                                                       @click="$event.target.select()"
                                                       @keydown.enter.prevent="onPriceEnter(row, idx)"
                                                       @keydown.down.prevent="moveRowField(idx, 1, 'price')"
                                                       @keydown.up.prevent="moveRowField(idx, -1, 'price')"
                                                       @keydown.delete.ctrl.prevent="removeItemRow(idx)"
                                                       @keydown.backspace.ctrl.prevent="removeItemRow(idx)">
                                            </div>
                                        </div>

                                        <!-- Desktop Delete Row Button -->
                                        <button @click="removeItemRow(idx)" type="button" class="purchase-item-desktop-delete btn btn-ghost btn-sm" style="color:var(--color-danger);padding:4px;height:36px;width:36px;" title="Hapus Baris (Ctrl+Del)">
                                            <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                        </button>
                                    </div>
                                </template>

                                <template x-if="form.items.length === 0">
                                    <div style="padding:20px;text-align:center;font-size:12px;color:var(--color-ink-mute);border:1px dashed var(--color-hairline);border-radius:8px;">
                                        Belum ada barang dipilih. Klik <strong>+ Tambah Baris</strong> di atas.
                                    </div>
                                </template>
                            </div>

                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px;padding-top:10px;border-top:1px solid var(--color-hairline);">
                                <span style="font-size:12px;font-weight:600;color:var(--color-ink-secondary);">Total Faktur Masuk:</span>
                                <strong class="cell-currency" style="font-size:16px;color:var(--color-primary-deep);" x-text="formatRupiah(formTotal)"></strong>
                            </div>

                            <!-- KEYBOARD NAVIGATION HELPER BAR -->
                            <div class="purchase-keyboard-hints" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;padding:7px 12px;background:var(--color-canvas);border:1px dashed var(--color-hairline);border-radius:8px;font-size:11px;color:var(--color-ink-mute);margin-top:10px;">
                                <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                                    <span style="display:inline-flex;align-items:center;gap:4px;">
                                        <kbd class="kbd-badge">&#x2193;</kbd><kbd class="kbd-badge">&#x2191;</kbd> <span>Pilih Item / Baris</span>
                                    </span>
                                    <span style="display:inline-flex;align-items:center;gap:4px;">
                                        <kbd class="kbd-badge">Enter</kbd> <span>Pilih / Pindah Qty &amp; Harga</span>
                                    </span>
                                    <span style="display:inline-flex;align-items:center;gap:4px;">
                                        <kbd class="kbd-badge">Enter di Harga</kbd> <span>Tambah Baris Baru</span>
                                    </span>
                                    <span style="display:inline-flex;align-items:center;gap:4px;">
                                        <kbd class="kbd-badge">Ctrl</kbd>+<kbd class="kbd-badge">Enter</kbd> <span>Simpan PO</span>
                                    </span>
                                </div>
                                <div style="font-size:10.5px;color:var(--color-primary);font-weight:700;display:inline-flex;align-items:center;gap:4px;">
                                    <i data-lucide="keyboard" style="width:13px;height:13px;"></i>
                                    <span>Navigasi Keyboard Cepat Aktif</span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div>
                    <label class="form-label">Catatan / Keterangan Pengiriman</label>
                    <input type="text" x-model="form.catatan" class="form-input" placeholder="Contoh: Pengiriman via armada vendor, barang diterima dalam kondisi baik...">
                </div>

                <div class="purchase-modal-footer flex flex-col-reverse sm:flex-row justify-end gap-2 pt-2 border-t border-hairline">
                    <button type="button" @click="showModal = false" class="btn btn-secondary w-full sm:w-auto">Batal</button>
                    <button type="button" @click="submitPurchase()" :disabled="isSubmitting" class="btn btn-primary w-full sm:w-auto justify-center" style="font-weight:700;">
                        <i data-lucide="save"></i>
                        <span x-show="!isSubmitting" x-text="form.jenis_dokumen === 'po' ? 'Terbitkan PO Pembelian (Ctrl+Enter)' : 'Simpan Faktur (Ctrl+Enter)'"></span>
                        <span x-show="isSubmitting">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL DETAIL PEMBELIAN / PO (TABBED - MATCHING CUSTOMER ORDERS)          -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showDetailModal" x-cloak class="modal-backdrop" @click.self="showDetailModal = false">
        <div class="modal-box modal-box-lg" style="max-width:960px;width:94vw;padding:0;border-radius:20px;overflow:hidden;display:flex;flex-direction:column;max-height:90vh;" @click.stop>
            
            <!-- MOBILE PULL HANDLE -->
            <div class="sm:hidden w-full flex justify-center pt-3 pb-1 flex-shrink-0" style="background:var(--color-canvas);">
                <div style="width:40px;height:4px;border-radius:2px;background:var(--color-hairline-strong);"></div>
            </div>

            <!-- 1. MODAL HEADER -->
            <div style="padding:18px 24px;border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;background:var(--color-canvas);flex-shrink:0;gap:20px;">
                <div style="display:flex;align-items:center;gap:14px;min-width:0;flex:1;">
                    <div style="width:44px;height:44px;border-radius:12px;background:#eff6ff;color:#1e3a8a;border:1px solid rgba(30,58,138,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="receipt" style="width:22px;height:22px;"></i>
                    </div>
                    <div style="min-width:0;flex:1;display:flex;flex-direction:column;gap:6px;">
                        <!-- Baris 1: Nomor Faktur + Semua Badges Status Terdistribusi Rapi -->
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="font-mono font-black" style="font-size:17px;color:var(--color-ink);letter-spacing:-0.02em;" x-text="activeDetail?.purchase?.nomor_faktur_pembelian"></span>
                            
                            <!-- Badges Tipe Dokumen -->
                            <template x-if="activeDetail?.purchase?.jenis_dokumen === 'po'">
                                <span>
                                    <template x-if="activeDetail?.purchase?.metode_logistik === 'diambil_driver'">
                                        <span class="badge" style="font-size:10.5px;font-weight:700;background:#fef3c7;color:#92400e;border:1px solid #fde68a;padding:2.5px 8px;border-radius:6px;">&#x1F69A; PO DRIVER</span>
                                    </template>
                                    <template x-if="activeDetail?.purchase?.metode_logistik !== 'diambil_driver'">
                                        <span class="badge" style="font-size:10.5px;font-weight:700;background:#e0e7ff;color:#3730a3;border:1px solid #c7d2fe;padding:2.5px 8px;border-radius:6px;">&#x1F3E2; PO SUPPLIER</span>
                                    </template>
                                </span>
                            </template>
                            <template x-if="activeDetail?.purchase?.jenis_dokumen !== 'po'">
                                <span class="badge badge-info" style="font-size:10.5px;font-weight:700;padding:2.5px 8px;border-radius:6px;">FAKTUR LANGSUNG</span>
                            </template>

                            <!-- Status Bayar -->
                            <span class="badge" 
                                  :class="{
                                      'badge-primary': activeDetail?.purchase?.status_pembayaran === 'lunas',
                                      'badge-warning': activeDetail?.purchase?.status_pembayaran === 'belum_lunas',
                                      'badge-danger': activeDetail?.purchase?.status_pembayaran === 'batal'
                                  }" 
                                  style="font-size:10.5px;font-weight:700;text-transform:uppercase;padding:2.5px 8px;border-radius:6px;" 
                                  x-text="activeDetail?.purchase?.status_pembayaran === 'belum_lunas' ? 'TEMPO (HUTANG)' : (activeDetail?.purchase?.status_pembayaran === 'batal' ? 'DIBATALKAN' : 'LUNAS')">
                            </span>

                            <!-- Status Penerimaan -->
                            <template x-if="activeDetail?.purchase?.status_penerimaan === 'diterima'">
                                <span class="badge badge-success" style="font-size:10.5px;font-weight:700;padding:2.5px 8px;border-radius:6px;">DITERIMA GUDANG</span>
                            </template>
                            <template x-if="activeDetail?.purchase?.status_penerimaan === 'ditugaskan_driver'">
                                <span class="badge" style="font-size:10.5px;font-weight:700;background:#dbeafe;color:#1e40af;border:1px solid #bfdbfe;padding:2.5px 8px;border-radius:6px;">TUGAS DRIVER</span>
                            </template>
                            <template x-if="activeDetail?.purchase?.status_penerimaan === 'menunggu_supplier'">
                                <span class="badge" style="font-size:10.5px;font-weight:700;background:#e0e7ff;color:#3730a3;border:1px solid #c7d2fe;padding:2.5px 8px;border-radius:6px;">TUNGGU SUPPLIER</span>
                            </template>
                            <template x-if="activeDetail?.purchase?.status_penerimaan === 'sudah_diambil'">
                                <span class="badge" style="font-size:10.5px;font-weight:700;background:#ccfbf1;color:#0f766e;border:1px solid #99f6e4;padding:2.5px 8px;border-radius:6px;">DIAMBIL DRIVER</span>
                            </template>
                            <template x-if="activeDetail?.purchase?.status_penerimaan === 'kendala_batal'">
                                <span class="badge badge-danger" style="font-size:10.5px;font-weight:700;padding:2.5px 8px;border-radius:6px;">KENDALA / BATAL</span>
                            </template>
                        </div>

                        <!-- Baris 2: Meta Info Lega dengan Ikon Berjarak Nyaman -->
                        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;font-size:12px;color:var(--color-ink-secondary);line-height:1.4;">
                            <span style="display:inline-flex;align-items:center;gap:5px;">
                                <i data-lucide="calendar" style="width:13px;height:13px;color:var(--color-ink-mute);"></i>
                                <span class="font-mono" x-text="activeDetail?.purchase?.tanggal_pembelian"></span>
                            </span>
                            <span style="color:var(--color-hairline-strong);">&bull;</span>
                            <span style="display:inline-flex;align-items:center;gap:5px;">
                                <i data-lucide="store" style="width:13px;height:13px;color:var(--color-ink-mute);"></i>
                                <span>Pemasok: <strong style="color:var(--color-ink);" x-text="activeDetail?.purchase?.nama_pemasok || '-'"></strong></span>
                            </span>
                            <template x-if="activeDetail?.purchase?.nama_driver">
                                <span style="display:inline-flex;align-items:center;gap:12px;">
                                    <span style="color:var(--color-hairline-strong);">&bull;</span>
                                    <span style="display:inline-flex;align-items:center;gap:5px;">
                                        <i data-lucide="truck" style="width:13px;height:13px;color:var(--color-ink-mute);"></i>
                                        <span>Driver: <strong style="color:var(--color-ink);" x-text="activeDetail?.purchase?.nama_driver"></strong></span>
                                    </span>
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
                
                <div style="display:flex;align-items:center;gap:12px;flex-shrink:0;">
                    <div class="hidden sm:flex flex-col items-end justify-center" style="padding:6px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:10px;min-width:135px;">
                        <span style="font-size:10px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.04em;line-height:1.2;">Total Transaksi</span>
                        <span class="font-mono font-black" style="font-size:16px;color:var(--color-primary-deep);line-height:1.2;margin-top:2px;" x-text="formatRupiah(activeDetail?.purchase?.total_biaya)"></span>
                    </div>

                    <!-- Tombol Cepat Terima Barang di Header Modal Detail -->
                    <div style="display:flex;align-items:center;gap:10px;">
                        <template x-if="activeDetail?.purchase?.status_penerimaan !== 'diterima' && activeDetail?.purchase?.status_pembayaran !== 'batal' && activeDetail?.purchase?.status_penerimaan !== 'kendala_batal'">
                            <button type="button" @click="openReceiveModal(activeDetail)" class="btn btn-sm" style="background:#059669;color:#ffffff;border-color:#059669;font-weight:700;font-size:12px;padding:8px 16px;border-radius:10px;display:inline-flex;align-items:center;gap:6px;box-shadow:0 1px 3px rgba(5,150,105,0.25);">
                                <i data-lucide="package-check" style="width:15px;height:15px;"></i>
                                <span class="hidden md:inline">Verifikasi &amp; Terima</span>
                                <span class="md:hidden">Terima</span>
                            </button>
                        </template>

                        <button type="button" @click="showDetailModal = false" class="btn btn-ghost btn-sm" style="width:36px;height:36px;padding:0;border-radius:10px;display:flex;align-items:center;justify-content:center;color:var(--color-ink-mute);background:var(--color-canvas-soft);border:1px solid var(--color-hairline);" aria-label="Tutup">
                            <i data-lucide="x" style="width:18px;height:18px;"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 2. TAB NAVIGATION BAR -->
            <div class="modal-tab-nav custom-scrollbar">
                <button type="button" @click="activeDetailTab = 'items'" class="modal-tab-btn" :class="{ 'is-active': activeDetailTab === 'items' }">
                    <i data-lucide="package" style="width:14px;height:14px;"></i>
                    <span>Produk &amp; Bahan</span>
                    <span class="badge" style="font-size:10px;padding:1px 6px;border-radius:10px;" x-text="activeDetail?.items?.length || '0'"></span>
                </button>
                <button type="button" @click="activeDetailTab = 'shipping'" class="modal-tab-btn" :class="{ 'is-active': activeDetailTab === 'shipping' }">
                    <i data-lucide="truck" style="width:14px;height:14px;"></i>
                    <span>Logistik &amp; Rute</span>
                </button>
                <button type="button" @click="activeDetailTab = 'payment'; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });" class="modal-tab-btn" :class="{ 'is-active': activeDetailTab === 'payment' }">
                    <i data-lucide="credit-card" style="width:14px;height:14px;"></i>
                    <span>Pembayaran</span>
                    <template x-if="activeDetail?.purchase?.status_pembayaran === 'belum_lunas'">
                        <span class="badge badge-danger" style="font-size:9.5px;padding:1px 5px;border-radius:8px;">Hutang</span>
                    </template>
                </button>
                <button type="button" @click="activeDetailTab = 'actions'" class="modal-tab-btn" :class="{ 'is-active': activeDetailTab === 'actions' }">
                    <i data-lucide="file-text" style="width:14px;height:14px;"></i>
                    <span>Dokumen &amp; Aksi</span>
                </button>
                <button type="button" @click="activeDetailTab = 'activity'; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });" class="modal-tab-btn" :class="{ 'is-active': activeDetailTab === 'activity' }">
                    <i data-lucide="history" style="width:14px;height:14px;"></i>
                    <span>Log Aktivitas</span>
                    <template x-if="activeDetail?.activityLogs && activeDetail.activityLogs.length > 0">
                        <span class="badge" style="font-size:10px;padding:1px 6px;border-radius:10px;background:rgba(99,102,241,0.1);color:#6366f1;" x-text="activeDetail.activityLogs.length"></span>
                    </template>
                </button>
            </div>

            <!-- 3. TAB BODIES -->
            <div class="custom-scrollbar" style="overflow-y:auto;padding:18px 20px;flex:1;">
                
                <!-- LOADING SKELETON -->
                <template x-if="isLoadingDetail">
                    <div style="display:flex;flex-direction:column;gap:14px;padding:60px 20px;align-items:center;justify-content:center;color:var(--color-ink-mute);">
                        <i data-lucide="loader-2" class="animate-spin" style="width:32px;height:32px;margin:0 auto 8px auto;color:var(--color-primary);"></i>
                        <div>Memuat rincian transaksi pengadaan...</div>
                    </div>
                </template>

                <template x-if="!isLoadingDetail && activeDetail">
                    <div>
                        <!-- TAB 1: PRODUK & BAHAN -->
                        <div x-show="activeDetailTab === 'items'" style="display:flex;flex-direction:column;gap:24px;">
                            <!-- VENDOR CARD -->
                            <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:16px 20px;display:flex;flex-direction:column;gap:12px;">
                                <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:12px;">
                                    <div style="display:flex;align-items:flex-start;gap:12px;">
                                        <div style="width:40px;height:40px;border-radius:10px;background:#e0f2fe;color:#0369a1;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(3,105,161,0.18);">
                                            <i data-lucide="store" style="width:20px;height:20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:10.5px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Profil Vendor Pemasok</div>
                                            <div style="font-weight:800;font-size:16px;color:var(--color-ink);margin-top:2px;" x-text="activeDetail.purchase.nama_pemasok"></div>
                                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="'Kode Vendor: ' + (activeDetail.purchase.kode_pemasok || '-')"></div>
                                        </div>
                                    </div>
                                    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
                                        <template x-if="activeDetail.purchase.nomor_telepon">
                                            <a :href="'https://wa.me/' + activeDetail.purchase.nomor_telepon.replace(/[^0-9]/g, '')" target="_blank" class="btn btn-secondary btn-sm" style="font-size:12px;color:#059669;background:#ecfdf5;border-color:#a7f3d0;display:inline-flex;align-items:center;gap:6px;padding:6px 14px;border-radius:8px;font-weight:600;">
                                                <i data-lucide="message-circle" style="width:14px;height:14px;"></i>
                                                <span x-text="activeDetail.purchase.nomor_telepon"></span>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-hairline" style="font-size:12px;">
                                    <template x-if="activeDetail.purchase.alamat_lengkap && activeDetail.purchase.alamat_lengkap !== '-'">
                                        <div style="display:flex;align-items:flex-start;gap:8px;color:var(--color-ink-secondary);">
                                            <i data-lucide="map-pin" style="width:14px;height:14px;color:#f59e0b;flex-shrink:0;margin-top:2px;"></i>
                                            <span x-text="activeDetail.purchase.alamat_lengkap"></span>
                                        </div>
                                    </template>
                                    <template x-if="activeDetail.purchase.nama_bank && activeDetail.purchase.nomor_rekening">
                                        <div style="display:flex;align-items:flex-start;gap:8px;color:var(--color-ink-secondary);">
                                            <i data-lucide="credit-card" style="width:14px;height:14px;color:#3b82f6;flex-shrink:0;margin-top:2px;"></i>
                                            <span x-text="activeDetail.purchase.nama_bank + ': ' + activeDetail.purchase.nomor_rekening + ' (a.n ' + (activeDetail.purchase.atas_nama_rekening || '-') + ')'"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- TABLE RINCIAN ITEM -->
                            <div style="margin-top:6px;border:1px solid var(--color-hairline);border-radius:14px;overflow:hidden;background:var(--color-canvas);box-shadow:0 1px 3px rgba(0,0,0,0.02);">
                                <div style="padding:13px 18px;background:var(--color-canvas-soft);border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;">
                                    <div style="display:flex;align-items:center;gap:8px;">
                                        <i data-lucide="layers" style="width:16px;height:16px;color:var(--color-primary);"></i>
                                        <span style="font-size:12.5px;font-weight:700;color:var(--color-ink);">Daftar Bahan Baku &amp; Kemasan</span>
                                    </div>
                                    <span class="badge badge-mono" style="font-size:11px;padding:3px 8px;border-radius:8px;" x-text="(activeDetail.items?.length || 0) + ' Item'"></span>
                                </div>
                                <div class="overflow-x-auto custom-scrollbar">
                                    <table style="width:100%;min-width:600px;font-size:12.5px;border-collapse:collapse;">
                                        <thead>
                                            <tr style="background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);color:var(--color-ink-mute);font-size:11px;text-transform:uppercase;letter-spacing:0.04em;">
                                                <th style="width:48px;padding:12px 14px;text-align:center;">No</th>
                                                <th style="min-width:220px;padding:12px 16px;text-align:left;">Nama Bahan / SKU</th>
                                                <th style="width:130px;padding:12px 16px;text-align:right;">Kuantitas</th>
                                                <th style="width:140px;padding:12px 16px;text-align:right;">Harga Satuan</th>
                                                <th style="width:150px;padding:12px 16px;text-align:right;">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template x-for="(item, idx) in activeDetail.items" :key="item.id">
                                                <tr style="border-bottom:1px solid var(--color-hairline);transition:background 0.15s;" class="hover:bg-gray-50/50 dark:hover:bg-slate-800/30">
                                                    <td style="padding:14px 14px;text-align:center;font-size:11.5px;color:var(--color-ink-mute);vertical-align:middle;" x-text="idx + 1"></td>
                                                    <td style="padding:14px 16px;vertical-align:middle;">
                                                        <div style="font-weight:700;color:var(--color-ink);font-size:13px;" x-text="item.nama_item"></div>
                                                        <div style="display:flex;align-items:center;gap:6px;margin-top:4px;">
                                                            <span class="badge font-mono" style="font-size:10px;padding:1px 6px;border-radius:4px;background:var(--color-canvas-soft);color:var(--color-ink-secondary);border:1px solid var(--color-hairline);" x-text="item.kode_sku"></span>
                                                            <span style="font-size:11px;color:var(--color-ink-mute);" x-text="item.tipe_item === 'bahan_mentah' ? 'Bahan Mentah' : 'Bahan Kemasan'"></span>
                                                        </div>
                                                    </td>
                                                    <td style="padding:14px 16px;text-align:right;vertical-align:middle;">
                                                        <span class="font-mono font-bold" style="font-size:13px;color:var(--color-ink);" x-text="item.kuantitas"></span>
                                                        <span style="font-size:11px;color:var(--color-ink-mute);margin-left:3px;" x-text="item.satuan"></span>
                                                    </td>
                                                    <td style="padding:14px 16px;text-align:right;font-family:var(--font-mono);font-size:12.5px;color:var(--color-ink-secondary);vertical-align:middle;" x-text="formatRupiah(item.harga_satuan)"></td>
                                                    <td style="padding:14px 16px;text-align:right;font-family:var(--font-mono);font-weight:800;font-size:13.5px;color:var(--color-primary-deep);vertical-align:middle;" x-text="formatRupiah(item.subtotal)"></td>
                                                </tr>
                                            </template>
                                        </tbody>
                                        <tfoot>
                                            <tr style="background:var(--color-canvas-soft);border-top:2px solid var(--color-hairline-strong);">
                                                <td colspan="4" style="padding:14px 18px;text-align:right;font-weight:700;font-size:13px;color:var(--color-ink);">
                                                    Total Belanja Bahan:
                                                </td>
                                                <td style="padding:14px 16px;text-align:right;font-family:var(--font-mono);font-size:16px;font-weight:900;color:var(--color-primary-deep);">
                                                    <span x-text="formatRupiah(activeDetail.purchase.total_biaya)"></span>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>

                            <!-- FOTO BUKTI NOTA FISIK JIKA SUDAH ADA -->
                            <template x-if="activeDetail.purchase.url_foto_nota">
                                <div style="border:1px solid var(--color-hairline);border-radius:14px;padding:16px;background:var(--color-canvas-soft);">
                                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                                        <div style="display:flex;align-items:center;gap:6px;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;letter-spacing:0.04em;">
                                            <i data-lucide="file-check" style="width:14px;height:14px;color:var(--color-primary);"></i>
                                            <span>Foto Bukti Nota Fisik Vendor</span>
                                        </div>
                                        <button type="button" @click="openReceiptPreview(activeDetail.purchase.url_foto_nota, activeDetail.purchase.nomor_faktur_pembelian)" class="btn btn-ghost btn-sm" style="font-size:11.5px;padding:4px 10px;color:var(--color-primary);display:inline-flex;align-items:center;gap:5px;">
                                            <i data-lucide="maximize-2" style="width:13px;height:13px;"></i>
                                            <span>Perbesar Nota</span>
                                        </button>
                                    </div>
                                    <div style="cursor:pointer;display:inline-block;position:relative;border-radius:10px;overflow:hidden;" @click="openReceiptPreview(activeDetail.purchase.url_foto_nota, activeDetail.purchase.nomor_faktur_pembelian)" title="Klik untuk melihat foto nota dalam ukuran penuh">
                                        <img :src="'<?= Router::url('/') ?>' + activeDetail.purchase.url_foto_nota.replace(/^\//, '')" 
                                             style="max-height:180px;max-width:100%;border-radius:10px;border:1px solid var(--color-hairline);display:block;" 
                                             alt="Nota Vendor">
                                    </div>
                                </div>
                            </template>

                            <!-- CATATAN DOKUMEN -->
                            <template x-if="activeDetail.purchase.catatan">
                                <div style="font-size:12px;color:var(--color-ink-secondary);padding:12px 16px;background:var(--color-canvas-soft);border-radius:10px;border:1px solid var(--color-hairline);display:flex;align-items:flex-start;gap:8px;">
                                    <i data-lucide="info" style="width:16px;height:16px;color:var(--color-ink-mute);flex-shrink:0;margin-top:1px;"></i>
                                    <div>
                                        <strong style="color:var(--color-ink);">Catatan:</strong> <span x-text="activeDetail.purchase.catatan"></span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- TAB 2: LOGISTIK & RUTE -->
                        <div x-show="activeDetailTab === 'shipping'" style="display:flex;flex-direction:column;gap:24px;">
                            <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:18px 20px;display:flex;flex-direction:column;gap:14px;">
                                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;border-bottom:1px solid var(--color-hairline);padding-bottom:12px;">
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div style="width:38px;height:38px;border-radius:10px;background:#e0e7ff;color:#3730a3;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(55,48,163,0.15);">
                                            <i data-lucide="truck" style="width:18px;height:18px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-size:10.5px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Metode &amp; Armada Logistik</div>
                                            <div style="font-size:15px;font-weight:800;color:var(--color-ink);margin-top:2px;">
                                                <span x-text="activeDetail.purchase.metode_logistik === 'diambil_driver' ? '\u{1F69A} Diambil oleh Driver Toko' : '\u{1F3E2} Diantar oleh Vendor Supplier'"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="badge badge-mono" style="font-size:11px;padding:4px 10px;border-radius:8px;" x-text="activeDetail.purchase.jenis_dokumen === 'po' ? 'Tipe: Purchase Order (PO)' : 'Tipe: Faktur Langsung'"></span>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <template x-if="activeDetail.purchase.metode_logistik === 'diambil_driver'">
                                        <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:12px;padding:14px;display:flex;flex-direction:column;gap:6px;">
                                            <span style="font-size:10.5px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Driver Penanggung Jawab:</span>
                                            <div style="font-weight:800;color:var(--color-ink);font-size:14px;" x-text="activeDetail.purchase.nama_driver || 'Belum Ditugaskan'"></div>
                                            <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-top:2px;">
                                                <template x-if="activeDetail.purchase.telp_driver">
                                                    <a :href="'https://wa.me/' + activeDetail.purchase.telp_driver.replace(/[^0-9]/g, '')" target="_blank" style="color:#059669;font-weight:600;font-size:11.5px;display:inline-flex;align-items:center;gap:4px;">
                                                        <i data-lucide="phone" style="width:12px;height:12px;"></i>
                                                        <span x-text="activeDetail.purchase.telp_driver"></span>
                                                    </a>
                                                </template>
                                                <template x-if="activeDetail.purchase.nopol_driver">
                                                    <span class="badge font-mono" style="font-size:10.5px;background:var(--color-canvas-soft);color:var(--color-ink-secondary);" x-text="'Armada: ' + activeDetail.purchase.nopol_driver"></span>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:12px;padding:14px;display:flex;flex-direction:column;gap:6px;">
                                        <span style="font-size:10.5px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Jadwal Pengadaan:</span>
                                        <div class="font-mono font-bold" style="font-size:14px;color:var(--color-ink);" x-text="activeDetail.purchase.tanggal_jadwal_belanja || activeDetail.purchase.tanggal_pembelian"></div>
                                        <template x-if="activeDetail.purchase.metode_logistik === 'diambil_driver'">
                                            <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">
                                                Metode Bayar: <strong style="color:var(--color-ink);" x-text="activeDetail.purchase.metode_bayar_belanja === 'tunai_driver' ? 'Kas Tunai Driver' : 'Ditransfer Kantor / Tempo'"></strong>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- Instruksi Driver -->
                                <template x-if="activeDetail.purchase.instruksi_driver">
                                    <div style="padding:12px 14px;background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.2);border-radius:10px;font-size:12px;color:#1e40af;display:flex;align-items:flex-start;gap:8px;">
                                        <i data-lucide="message-square" style="width:16px;height:16px;flex-shrink:0;margin-top:1px;"></i>
                                        <div>
                                            <strong>Instruksi Khusus Driver:</strong>
                                            <div style="margin-top:3px;color:var(--color-ink);" x-text="activeDetail.purchase.instruksi_driver"></div>
                                        </div>
                                    </div>
                                </template>

                                <!-- Realisasi Waktu -->
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-3 border-t border-hairline">
                                    <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:10px;padding:10px 12px;">
                                        <div style="font-size:10px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.03em;">Waktu Diambil</div>
                                        <div class="font-mono font-bold" style="font-size:12px;color:var(--color-ink);margin-top:4px;" x-text="activeDetail.purchase.waktu_diambil || '-'"></div>
                                    </div>
                                    <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:10px;padding:10px 12px;">
                                        <div style="font-size:10px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.03em;">Diterima Gudang</div>
                                        <div class="font-mono font-bold" style="font-size:12px;color:var(--color-ink);margin-top:4px;" x-text="activeDetail.purchase.waktu_diterima_gudang || '-'"></div>
                                    </div>
                                    <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:10px;padding:10px 12px;">
                                        <div style="font-size:10px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.03em;">No. Nota Vendor</div>
                                        <div class="font-mono font-bold" style="font-size:12px;color:var(--color-ink);margin-top:4px;" x-text="activeDetail.purchase.nomor_nota_vendor || '-'"></div>
                                    </div>
                                    <div style="background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:10px;padding:10px 12px;">
                                        <div style="font-size:10px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.03em;">Kas Dibayar Driver</div>
                                        <div class="font-mono font-bold" style="font-size:12px;color:#059669;margin-top:4px;" x-text="activeDetail.purchase.nominal_dibayar_driver ? formatRupiah(activeDetail.purchase.nominal_dibayar_driver) : '-'"></div>
                                    </div>
                                </div>

                                <!-- Alert jika ada kendala / batal -->
                                <template x-if="activeDetail.purchase.status_penerimaan === 'kendala_batal'">
                                    <div style="padding:14px 16px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:12px;font-size:12px;color:var(--color-danger);">
                                        <div style="font-weight:700;display:flex;align-items:center;gap:6px;">
                                            <i data-lucide="alert-octagon" style="width:16px;height:16px;"></i>
                                            <span>Laporan Kendala Belanja Driver:</span>
                                        </div>
                                        <div style="margin-top:4px;color:var(--color-ink);" x-text="activeDetail.purchase.alasan_kendala || 'Tidak ada keterangan kendala.'"></div>
                                        <template x-if="activeDetail.purchase.foto_bukti_kendala">
                                            <div style="margin-top:10px;">
                                                <button type="button" @click="openReceiptPreview(activeDetail.purchase.foto_bukti_kendala, 'Bukti Kendala - ' + activeDetail.purchase.nomor_faktur_pembelian)" class="btn btn-secondary btn-sm" style="font-size:11.5px;display:inline-flex;align-items:center;gap:5px;background:#fff;">
                                                    <i data-lucide="image" style="width:13px;height:13px;"></i>
                                                    <span>Lihat Foto Bukti Kendala</span>
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- TAB 3: FINANSIAL & PEMBAYARAN -->
                        <div x-show="activeDetailTab === 'payment'" style="display:flex;flex-direction:column;gap:24px;">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:18px 20px;display:flex;flex-direction:column;justify-content:space-between;min-height:96px;">
                                    <div>
                                        <div style="font-size:11px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Total Nilai Faktur</div>
                                        <div class="font-mono font-black" style="font-size:21px;color:var(--color-primary-deep);margin-top:4px;" x-text="formatRupiah(activeDetail.purchase.total_biaya)"></div>
                                    </div>
                                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:6px;">Akumulasi seluruh bahan pengadaan</div>
                                </div>

                                <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:14px;padding:18px 20px;display:flex;flex-direction:column;justify-content:space-between;min-height:96px;">
                                    <div>
                                        <div style="font-size:11px;color:var(--color-ink-mute);font-weight:700;text-transform:uppercase;letter-spacing:0.04em;">Status Pelunasan</div>
                                        <div style="margin-top:6px;display:flex;align-items:center;">
                                            <span class="badge" 
                                                  :class="{
                                                      'badge-primary': activeDetail.purchase.status_pembayaran === 'lunas',
                                                      'badge-warning': activeDetail.purchase.status_pembayaran === 'belum_lunas',
                                                      'badge-danger': activeDetail.purchase.status_pembayaran === 'batal'
                                                  }" 
                                                  style="font-size:11.5px;font-weight:800;padding:4px 10px;border-radius:7px;letter-spacing:0.02em;"
                                                  x-text="activeDetail.purchase.status_pembayaran === 'belum_lunas' ? 'TEMPO (HUTANG VENDOR)' : (activeDetail.purchase.status_pembayaran === 'batal' ? 'DIBATALKAN' : 'LUNAS (TERBAYAR)')">
                                            </span>
                                        </div>
                                    </div>
                                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:6px;">
                                        <template x-if="activeDetail.purchase.status_pembayaran === 'belum_lunas'">
                                            <span>Kewajiban hutang dagang jatuh tempo</span>
                                        </template>
                                        <template x-if="activeDetail.purchase.status_pembayaran === 'lunas'">
                                            <span>
                                                <template x-if="activeDetail.purchase.akun_kas_nama">
                                                    <span>Sumber Kas: <strong style="color:var(--color-ink);" x-text="activeDetail.purchase.akun_kas_nama"></strong></span>
                                                </template>
                                                <template x-if="!activeDetail.purchase.akun_kas_nama">
                                                    <span>Pembayaran telah lunas diselesaikan</span>
                                                </template>
                                            </span>
                                        </template>
                                        <template x-if="activeDetail.purchase.status_pembayaran === 'batal'">
                                            <span>Faktur dibatalkan</span>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert jika hutang (dengan margin-top jelas agar tidak menempel) -->
                            <template x-if="activeDetail.purchase.status_pembayaran === 'belum_lunas'">
                                <div style="margin-top:6px;padding:18px 22px;background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);border-radius:14px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
                                    <div style="display:flex;align-items:center;gap:14px;">
                                        <div style="width:40px;height:40px;border-radius:10px;background:#fef3c7;color:#b45309;display:flex;align-items:center;justify-content:center;flex-shrink:0;border:1px solid rgba(180,83,9,0.2);">
                                            <i data-lucide="clock" style="width:20px;height:20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:700;font-size:13.5px;color:#b45309;">Faktur Berstatus Hutang Dagang (AP)</div>
                                            <div style="font-size:12px;color:#92400e;margin-top:2px;">
                                                Sisa hutang belum terbayar: <strong class="font-mono" style="font-size:13.5px;color:#78350f;" x-text="formatRupiah(activeDetail.purchase.total_biaya)"></strong>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="showDetailModal = false; openPayModal(activeDetail.purchase)" class="btn" style="background:#d97706;border-color:#d97706;color:#ffffff;font-weight:700;font-size:12px;padding:9px 18px;border-radius:8px;box-shadow:0 1px 3px rgba(217,119,6,0.3);display:inline-flex;align-items:center;gap:7px;">
                                        <i data-lucide="credit-card" style="width:15px;height:15px;"></i>
                                        <span>Catat Pelunasan Sekarang</span>
                                    </button>
                                </div>
                            </template>
                        </div>

                        <!-- TAB 4: DOKUMEN & AKSI OPERASIONAL -->
                        <div x-show="activeDetailTab === 'actions'" style="display:flex;flex-direction:column;gap:20px;">
                            <!-- BANNER KENDALA BELANJA DRIVER (JIKA ADA KENDALA) -->
                            <template x-if="activeDetail.purchase.status_penerimaan === 'kendala_batal'">
                                <div style="padding:14px 16px;background:rgba(239,68,68,0.06);border:1.5px solid rgba(239,68,68,0.3);border-radius:12px;display:flex;flex-direction:column;gap:10px;">
                                    <div class="flex items-start justify-between gap-3 flex-wrap">
                                        <div class="flex items-start gap-2.5">
                                            <div style="width:34px;height:34px;border-radius:8px;background:#fee2e2;color:#991b1b;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                                <i data-lucide="alert-triangle" style="width:18px;height:18px;"></i>
                                            </div>
                                            <div>
                                                <div style="font-weight:800;font-size:13px;color:#991b1b;">Kendala Belanja Dilaporkan Driver</div>
                                                <div style="font-size:12px;color:var(--color-ink);margin-top:2px;">
                                                    Alasan: <strong x-text="activeDetail.purchase.alasan_kendala || 'Vendor tutup / stok tidak tersedia'"></strong>
                                                </div>
                                            </div>
                                        </div>
                                        <template x-if="activeDetail.purchase.foto_bukti_kendala">
                                            <button type="button" @click="openReceiptPreview(activeDetail.purchase.foto_bukti_kendala, 'Bukti Kendala Belanja - ' + activeDetail.purchase.nomor_faktur_pembelian)" class="btn btn-secondary btn-sm" style="font-size:11.5px;color:#991b1b;border-color:#fca5a5;background:#ffffff;font-weight:700;">
                                                <i data-lucide="image" style="width:13px;height:13px;"></i>
                                                <span>Lihat Foto Kendala</span>
                                            </button>
                                        </template>
                                    </div>
                                    <div style="font-size:11.5px;color:var(--color-ink-mute);border-top:1px dashed rgba(239,68,68,0.2);padding-top:8px;">
                                        Barang belum dibeli di vendor. Silakan tindak lanjuti dokumen ini:
                                    </div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <button type="button" @click="openEditPoModal(activeDetail)" class="btn btn-primary btn-sm" style="background:#2563eb;border-color:#2563eb;font-weight:700;font-size:12px;padding:6px 14px;border-radius:8px;display:inline-flex;align-items:center;gap:5px;">
                                            <i data-lucide="calendar-clock" style="width:14px;height:14px;"></i>
                                            <span>Jadwalkan Ulang / Ganti Driver</span>
                                        </button>
                                        <button type="button" @click="showDetailModal = false; openCancelModal(activeDetail.purchase)" class="btn btn-secondary btn-sm" style="color:#991b1b;border-color:#fca5a5;font-weight:700;font-size:12px;padding:6px 14px;border-radius:8px;display:inline-flex;align-items:center;gap:5px;">
                                            <i data-lucide="ban" style="width:14px;height:14px;"></i>
                                            <span>Batalkan PO Resmi</span>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <div style="font-size:12px;color:var(--color-ink-mute);margin-bottom:2px;">
                                Pilih aksi operasional untuk memproses atau mencetak dokumen ini:
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <!-- Aksi 1: Cetak Dokumen PDF -->
                                <a :href="'<?= Router::url('/purchases/pdf') ?>?id=' + encodeURIComponent(activeDetail.purchase.id)" target="_blank" class="card p-3.5 hover:border-primary transition-all flex items-start gap-3" style="text-decoration:none;border-radius:12px;">
                                    <div style="width:40px;height:40px;border-radius:10px;background:#ecfdf5;color:#059669;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                        <i data-lucide="printer" style="width:20px;height:20px;"></i>
                                    </div>
                                    <div>
                                        <div style="font-weight:700;font-size:13px;color:var(--color-ink);">Cetak / Unduh PDF PO</div>
                                        <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Dokumen resmi purchase order A4 dengan logo &amp; rincian item</div>
                                    </div>
                                </a>

                                <!-- Aksi 2: Konfirmasi Penerimaan Barang di Gudang -->
                                <template x-if="activeDetail.purchase.status_penerimaan !== 'diterima' && activeDetail.purchase.status_pembayaran !== 'batal' && activeDetail.purchase.status_penerimaan !== 'kendala_batal'">
                                    <button type="button" @click="openReceiveModal(activeDetail)" class="card p-3.5 hover:border-primary transition-all text-left flex items-start gap-3" style="border-radius:12px;background:rgba(16,185,129,0.04);border-color:rgba(16,185,129,0.3);">
                                        <div style="width:40px;height:40px;border-radius:10px;background:#d1fae5;color:#065f46;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="package-check" style="width:20px;height:20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:700;font-size:13px;color:#065f46;">Verifikasi &amp; Terima Barang</div>
                                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Periksa barang fisik masuk, koreksi qty/harga nota, dan tambah stok gudang</div>
                                        </div>
                                    </button>
                                </template>

                                <!-- Aksi 3: Edit Rincian PO (Khusus PO yang belum diterima/batal) -->
                                <template x-if="activeDetail.purchase.jenis_dokumen === 'po' && activeDetail.purchase.status_penerimaan !== 'diterima' && activeDetail.purchase.status_pembayaran !== 'batal'">
                                    <button type="button" @click="openEditPoModal(activeDetail)" class="card p-3.5 hover:border-blue-500 transition-all text-left flex items-start gap-3" style="border-radius:12px;background:rgba(59,130,246,0.04);border-color:rgba(59,130,246,0.3);">
                                        <div style="width:40px;height:40px;border-radius:10px;background:#dbeafe;color:#1e40af;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="edit-3" style="width:20px;height:20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:700;font-size:13px;color:#1e40af;">Edit Rincian PO Pembelian</div>
                                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Ubah logistik (antar supplier/ambil driver), ganti driver, jadwal, atau item</div>
                                        </div>
                                    </button>
                                </template>

                                <!-- Aksi 4: Bayar Hutang (Jika belum lunas) -->
                                <template x-if="activeDetail.purchase.status_pembayaran === 'belum_lunas'">
                                    <button type="button" @click="showDetailModal = false; openPayModal(activeDetail.purchase)" class="card p-3.5 hover:border-amber-500 transition-all text-left flex items-start gap-3" style="border-radius:12px;background:rgba(245,158,11,0.04);border-color:rgba(245,158,11,0.3);">
                                        <div style="width:40px;height:40px;border-radius:10px;background:#fef3c7;color:#92400e;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="credit-card" style="width:20px;height:20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:700;font-size:13px;color:#92400e;">Catat Pelunasan Hutang</div>
                                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Kurangi saldo akun kas untuk melunasi tagihan tempo vendor</div>
                                        </div>
                                    </button>
                                </template>

                                <!-- Aksi 5: Batalkan Faktur/PO -->
                                <template x-if="activeDetail.purchase.status_pembayaran !== 'batal'">
                                    <button type="button" @click="showDetailModal = false; openCancelModal(activeDetail.purchase)" class="card p-3.5 hover:border-red-500 transition-all text-left flex items-start gap-3" style="border-radius:12px;background:rgba(239,68,68,0.03);border-color:rgba(239,68,68,0.25);">
                                        <div style="width:40px;height:40px;border-radius:10px;background:#fee2e2;color:#991b1b;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                                            <i data-lucide="ban" style="width:20px;height:20px;"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight:700;font-size:13px;color:#991b1b;">Batalkan Dokumen Transaksi</div>
                                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Batalkan transaksi ini dan kembalikan stok/kas (jika sudah diterima)</div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>

                        <!-- TAB 5: LOG AKTIVITAS -->
                        <div x-show="activeDetailTab === 'activity'" style="display:flex;flex-direction:column;gap:14px;">
                            <div style="font-size:12px;color:var(--color-ink-mute);margin-bottom:4px;">
                                Riwayat kronologi aksi dan perubahan status pada dokumen pengadaan ini:
                            </div>

                            <template x-if="activeDetail.activityLogs && activeDetail.activityLogs.length > 0">
                                <div style="display:flex;flex-direction:column;gap:10px;">
                                    <template x-for="(log, lIdx) in activeDetail.activityLogs" :key="lIdx">
                                        <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:10px;font-size:12px;display:flex;gap:12px;align-items:flex-start;">
                                            <div style="width:8px;height:8px;border-radius:50%;background:#3b82f6;margin-top:6px;flex-shrink:0;"></div>
                                            <div style="flex:1;">
                                                <div style="font-weight:700;color:var(--color-ink);" x-text="log.deskripsi_aktivitas"></div>
                                                <div style="font-size:11px;color:var(--color-ink-mute);margin-top:3px;display:flex;gap:8px;flex-wrap:wrap;">
                                                    <span x-text="log.nama_aktor ? (log.nama_aktor + ' (' + (log.peran_aktor || 'staff') + ')') : 'Sistem'"></span>
                                                    <span>&bull;</span>
                                                    <span class="font-mono" x-text="log.dibuat_pada"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!activeDetail.activityLogs || activeDetail.activityLogs.length === 0">
                                <div style="text-align:center;padding:36px;color:var(--color-ink-mute);border:1px dashed var(--color-hairline);border-radius:12px;">
                                    <i data-lucide="clock" style="width:32px;height:32px;margin:0 auto 8px auto;opacity:0.4;"></i>
                                    <div style="font-size:12.5px;font-weight:600;">Belum ada catatan log aktivitas tambahan</div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- MODAL FOOTER -->
            <div style="padding:12px 20px;border-top:1px solid var(--color-hairline);display:flex;justify-content:space-between;align-items:center;background:var(--color-canvas-soft);flex-shrink:0;">
                <button type="button" @click="showDetailModal = false" class="btn btn-secondary btn-sm">Tutup</button>
                <div style="font-size:11.5px;color:var(--color-ink-mute);">
                    Dibuat oleh: <strong style="color:var(--color-ink);" x-text="activeDetail?.purchase?.pembuat || 'Sistem'"></strong>
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL PELUNASAN HUTANG VENDOR (AP)                                        -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showPayModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:480px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title">Pelunasan Hutang Faktur Vendor</div>
                <button @click="showPayModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">
                <div style="background:var(--color-canvas-soft);padding:12px;border-radius:8px;font-size:12px;">
                    <div style="color:var(--color-ink-mute);font-size:11px;">Nomor Faktur:</div>
                    <strong class="font-mono" style="font-size:13px;color:var(--color-ink);" x-text="activePay?.nomor_faktur_pembelian"></strong>
                    <div style="color:var(--color-ink);margin-top:2px;" x-text="'Vendor: ' + (activePay?.nama_pemasok || '-')"></div>
                    <div style="margin-top:6px;font-size:14px;font-weight:700;color:var(--color-primary-deep);">
                        Total Tagihan: <span x-text="formatRupiah(activePay?.total_biaya)"></span>
                    </div>
                </div>

                <div>
                    <label class="form-label">Tanggal Pembayaran *</label>
                    <input type="date" x-model="payForm.tanggal_bayar" class="form-input font-mono">
                </div>

                <div>
                    <label class="form-label">Akun Kas Sumber Dana *</label>
                    <select x-model="payForm.akun_kas_id" class="form-input" style="font-weight:600;">
                        <?php foreach ($cashAccounts as $ca): ?>
                        <option value="<?= $ca['id'] ?>">
                            <?= htmlspecialchars($ca['nama_akun']) ?> (<?= Format::rupiah((float)$ca['saldo_saat_ini']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="form-label">Catatan Pembayaran (Opsional)</label>
                    <input type="text" x-model="payForm.catatan" class="form-input" placeholder="Contoh: Transfer via m-Banking, ref #123...">
                </div>

                <div class="purchase-modal-action-row" style="display:flex;justify-content:flex-end;gap:8px;margin-top:6px;">
                    <button type="button" @click="showPayModal = false" class="btn btn-secondary">Batal</button>
                    <button type="button" @click="submitPayment()" :disabled="isSubmitting" class="btn btn-primary" style="background:#f59e0b;border-color:#f59e0b;font-weight:700;">
                        <i data-lucide="check-circle"></i>
                        <span x-show="!isSubmitting">Konfirmasi Bayar</span>
                        <span x-show="isSubmitting">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL BATAL FAKTUR PEMBELIAN                                              -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showCancelModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:460px;padding:24px;">
            <div class="modal-header">
                <div class="modal-title" style="color:var(--color-danger);">Batalkan Faktur Pembelian</div>
                <button @click="showCancelModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">
                <div style="padding:12px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.2);border-radius:8px;font-size:12px;color:var(--color-danger);">
                    <div style="font-weight:700;display:flex;align-items:center;gap:6px;">
                        <i data-lucide="alert-triangle" style="width:16px;height:16px;"></i>
                        <span>Peringatan Pembatalan:</span>
                    </div>
                    <div style="margin-top:4px;line-height:1.4;">
                        Pembatalan akan <strong>mengurangi kembali stok fisik bahan</strong> di gudang. Jika faktur sudah lunas, saldo akun kas akan dikembalikan secara otomatis.
                    </div>
                </div>

                <div style="font-size:12px;">
                    <div>Faktur: <strong class="font-mono" x-text="activeCancel?.nomor_faktur_pembelian"></strong></div>
                    <div>Vendor: <strong x-text="activeCancel?.nama_pemasok"></strong></div>
                    <div>Total Nominal: <strong x-text="formatRupiah(activeCancel?.total_biaya)"></strong></div>
                </div>

                <div>
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
                        <label class="form-label" style="margin-bottom:0;">Alasan Pembatalan <span style="color:var(--color-danger);">*</span></label>
                        <span x-show="cancelError" x-cloak style="color:var(--color-danger);font-size:11px;font-weight:600;">Wajib diisi</span>
                    </div>
                    <input type="text" 
                           x-ref="cancelAlasanInput"
                           x-model="cancelAlasan" 
                           @input="cancelError = ''"
                           @keydown.enter="submitCancel()"
                           class="form-input" 
                           :style="cancelError ? 'border-color:var(--color-danger);box-shadow:0 0 0 3px rgba(239,68,68,0.18);' : ''"
                           placeholder="Contoh: Salah input kuantitas / faktur dobel...">
                    <!-- Pesan Peringatan Validasi Interaktif di Dalam Pop-up Modal -->
                    <div x-show="cancelError" x-cloak style="display:flex;align-items:center;gap:6px;margin-top:6px;padding:7px 10px;background:rgba(239,68,68,0.08);border:1px solid rgba(239,68,68,0.25);border-radius:6px;font-size:11.5px;color:var(--color-danger);font-weight:600;">
                        <i data-lucide="alert-circle" style="width:14px;height:14px;flex-shrink:0;"></i>
                        <span x-text="cancelError"></span>
                    </div>
                </div>

                <div class="purchase-modal-action-row" style="display:flex;justify-content:flex-end;gap:8px;margin-top:6px;">
                    <button type="button" @click="showCancelModal = false" class="btn btn-secondary">Kembali</button>
                    <button type="button" @click="submitCancel()" :disabled="isSubmitting" class="btn btn-danger" style="font-weight:700;">
                        <i data-lucide="ban"></i>
                        <span x-show="!isSubmitting">Batalkan Faktur</span>
                        <span x-show="isSubmitting">Memproses...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL EDIT PO PEMBELIAN (FLEKSIBEL)                                       -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showEditPoModal" x-cloak class="modal-backdrop" @click.self="showEditPoModal = false">
        <div class="modal-box purchase-modal-box" style="max-width:760px;" @click.stop>
            <div class="modal-header pb-2.5 mb-1 border-b border-hairline">
                <div>
                    <div class="modal-title" style="font-size:15px;display:flex;align-items:center;gap:8px;">
                        <span>Edit PO Pembelian</span>
                        <span class="badge badge-mono font-bold" style="color:var(--color-primary-deep);" x-text="editPoForm.nomor_faktur"></span>
                    </div>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">Sesuaikan vendor, metode logistik driver, jadwal, instruksi, dan daftar bahan</div>
                </div>
                <button @click="showEditPoModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">
                <!-- 1. VENDOR & LOGISTIK -->
                <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);padding:12px 14px;display:flex;flex-direction:column;gap:10px;">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Vendor Pemasok *</label>
                            <select x-model="editPoForm.pemasok_id" class="form-input" style="font-weight:600;">
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_pemasok']) ?> (<?= htmlspecialchars($s['kode_pemasok']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Tanggal Terbit PO</label>
                            <input type="date" x-model="editPoForm.tanggal_pembelian" class="form-input font-mono">
                        </div>
                    </div>

                    <!-- Pilihan Logistik -->
                    <div>
                        <label class="form-label">Metode Logistik Pengadaan *</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="po-radio-card cursor-pointer" :class="editPoForm.metode_logistik === 'diambil_driver' ? 'is-selected' : ''" @click="editPoForm.metode_logistik = 'diambil_driver'">
                                <input type="radio" x-model="editPoForm.metode_logistik" value="diambil_driver" style="display:none;">
                                <div class="po-radio-body">
                                    <span class="po-radio-icon">&#x1F69A;</span>
                                    <div>
                                        <div class="po-radio-title">Diambil Driver Toko</div>
                                        <div class="po-radio-desc">Muncul di rute belanja driver di <em>Pengiriman Driver</em></div>
                                    </div>
                                </div>
                            </label>

                            <label class="po-radio-card cursor-pointer" :class="editPoForm.metode_logistik === 'diantar_supplier' ? 'is-selected' : ''" @click="editPoForm.metode_logistik = 'diantar_supplier'">
                                <input type="radio" x-model="editPoForm.metode_logistik" value="diantar_supplier" style="display:none;">
                                <div class="po-radio-body">
                                    <span class="po-radio-icon">&#x1F3E2;</span>
                                    <div>
                                        <div class="po-radio-title">Diantar oleh Supplier</div>
                                        <div class="po-radio-desc">Vendor mengantarkan barang langsung ke gudang</div>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Form Tambahan Jika Diambil Driver -->
                    <div x-show="editPoForm.metode_logistik === 'diambil_driver'" style="display:flex;flex-direction:column;gap:10px;">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Driver Penanggung Jawab *</label>
                                <select x-model="editPoForm.sales_driver_id" class="form-input" style="font-weight:600;">
                                    <option value="">-- Pilih Armada Driver --</option>
                                    <?php foreach ($drivers as $dr): ?>
                                    <option value="<?= $dr['id'] ?>">
                                        <?= htmlspecialchars($dr['nama_karyawan']) ?> <?= !empty($dr['nomor_polisi_kendaraan']) ? ' (' . htmlspecialchars($dr['nomor_polisi_kendaraan']) . ')' : '' ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Tanggal Jadwal Belanja Driver *</label>
                                <input type="date" x-model="editPoForm.tanggal_jadwal_belanja" class="form-input font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Metode Pembayaran Saat Belanja *</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <label class="po-radio-card-sm cursor-pointer" :class="editPoForm.metode_bayar_belanja === 'tunai_driver' ? 'is-selected' : ''" @click="editPoForm.metode_bayar_belanja = 'tunai_driver'">
                                    <input type="radio" x-model="editPoForm.metode_bayar_belanja" value="tunai_driver" style="display:none;">
                                    <div class="po-radio-body-sm">
                                        <span class="po-radio-icon-sm">&#x1F4B5;</span>
                                        <div>
                                            <div class="po-radio-title-sm">Kas Tunai Driver</div>
                                            <div class="po-radio-desc-sm">Dibekali dana toko</div>
                                        </div>
                                    </div>
                                </label>
                                <label class="po-radio-card-sm cursor-pointer" :class="editPoForm.metode_bayar_belanja === 'transfer_kantor' ? 'is-selected' : ''" @click="editPoForm.metode_bayar_belanja = 'transfer_kantor'">
                                    <input type="radio" x-model="editPoForm.metode_bayar_belanja" value="transfer_kantor" style="display:none;">
                                    <div class="po-radio-body-sm">
                                        <span class="po-radio-icon-sm">&#x1F4B3;</span>
                                        <div>
                                            <div class="po-radio-title-sm">Transfer Kantor</div>
                                            <div class="po-radio-desc-sm">Tempo / dibayar kantor</div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Status Pembayaran PO</label>
                                <select x-model="editPoForm.status_pembayaran" class="form-input">
                                    <option value="belum_lunas">Tempo (Hutang Vendor)</option>
                                    <option value="lunas">Sudah Lunas</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Instruksi Khusus Driver (Opsional)</label>
                                <input type="text" x-model="editPoForm.instruksi_driver" class="form-input" placeholder="Contoh: Ambil barang setelah pengiriman toko tuntas...">
                            </div>
                        </div>
                    </div>

                    <!-- Parameter jika diantar supplier -->
                    <div x-show="editPoForm.metode_logistik === 'diantar_supplier'">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="form-label">Tanggal Perkiraan Tiba di Gudang</label>
                                <input type="date" x-model="editPoForm.tanggal_jadwal_belanja" class="form-input font-mono">
                            </div>
                            <div>
                                <label class="form-label">Status Pembayaran PO</label>
                                <select x-model="editPoForm.status_pembayaran" class="form-input">
                                    <option value="belum_lunas">Tempo (Hutang Vendor)</option>
                                    <option value="lunas">Sudah Lunas / Transfer Dimuka</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DAFTAR ITEM BARANG -->
                <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                    <div class="flex items-center justify-between pb-2 mb-2 border-b border-hairline">
                        <div style="font-size:12px;font-weight:700;color:var(--color-ink);display:flex;align-items:center;gap:6px;">
                            <i data-lucide="package" style="width:14px;height:14px;color:var(--color-primary);"></i>
                            <span>Daftar Bahan / SKU yang Dipesan</span>
                        </div>
                        <button type="button" @click="addEditPoItemRow()" class="btn btn-secondary btn-sm" style="padding:3px 8px;font-size:11.5px;display:inline-flex;align-items:center;gap:4px;">
                            <i data-lucide="plus" style="width:13px;height:13px;"></i>
                            <span>Tambah Item</span>
                        </button>
                    </div>

                    <div style="display:flex;flex-direction:column;gap:8px;">
                        <template x-for="(row, idx) in editPoForm.items" :key="idx">
                            <div class="purchase-item-row" style="background:var(--color-canvas);padding:8px 10px;border-radius:8px;border:1px solid var(--color-hairline);">
                                <div class="purchase-item-select-col" style="flex:1;">
                                    <label class="purchase-item-label">Bahan Baku / Kemasan</label>
                                    <select x-model="row.item_id" class="form-input" style="font-size:12px;height:36px;font-weight:600;">
                                        <option value="">-- Pilih Bahan --</option>
                                        <?php foreach ($items as $it): ?>
                                        <option value="<?= $it['id'] ?>">
                                            <?= htmlspecialchars($it['nama_item']) ?> (<?= $it['tipe_item'] === 'bahan_mentah' ? 'Mentah' : 'Kemasan' ?> - <?= htmlspecialchars($it['satuan_dasar']) ?>)
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="purchase-item-inputs-row" style="display:flex;align-items:center;gap:6px;">
                                    <div class="purchase-item-qty-col" style="width:90px;">
                                        <label class="purchase-item-label">Qty</label>
                                        <input type="number" min="0.01" step="any" x-model.number="row.qty" class="form-input font-mono" style="font-size:12px;height:36px;" placeholder="Qty" @input="recalcEditPoRow(idx)">
                                    </div>
                                    <div class="purchase-item-price-col" style="width:130px;">
                                        <label class="purchase-item-label">Harga Satuan</label>
                                        <input type="text" x-model="row.harga_satuan" class="form-input font-mono input-rupiah" style="font-size:12px;height:36px;" placeholder="Harga" @input="recalcEditPoRow(idx)">
                                    </div>
                                </div>
                                <button type="button" @click="removeEditPoItemRow(idx)" class="btn btn-ghost btn-sm" style="color:var(--color-danger);padding:4px;" title="Hapus Baris">
                                    <i data-lucide="trash-2" style="width:15px;height:15px;"></i>
                                </button>
                            </div>
                        </template>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:8px;border-top:1px solid var(--color-hairline);">
                        <span style="font-size:12px;font-weight:600;color:var(--color-ink-secondary);">Estimasi Total Biaya PO:</span>
                        <strong class="cell-currency font-mono" style="font-size:15px;color:var(--color-primary-deep);" x-text="formatRupiah(editPoTotal)"></strong>
                    </div>
                </div>

                <!-- CATATAN -->
                <div>
                    <label class="form-label">Catatan PO</label>
                    <input type="text" x-model="editPoForm.catatan" class="form-input" placeholder="Catatan tambahan...">
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-hairline" style="display:flex; justify-content:flex-end; align-items:center; gap:8px;">
                    <button type="button" @click="showEditPoModal = false" class="btn btn-secondary w-full sm:w-auto" style="font-weight:600;">Batal</button>
                    <button type="button" @click="submitEditPo()" :disabled="isSubmitting" class="btn btn-primary w-full sm:w-auto justify-center" style="font-weight:700;display:inline-flex;align-items:center;gap:6px;">
                        <i data-lucide="check" style="width:15px;height:15px;"></i>
                        <span x-show="!isSubmitting">Simpan Perubahan PO</span>
                        <span x-show="isSubmitting">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL KONFIRMASI TERIMA BARANG DI GUDANG (RECEIVE GOODS)                  -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showReceiveModal" x-cloak class="modal-backdrop" @click.self="showReceiveModal = false">
        <div class="modal-box purchase-modal-box" style="max-width:860px;width:94vw;" @click.stop>
            <div class="modal-header pb-2.5 mb-1 border-b border-hairline">
                <div>
                    <div class="modal-title" style="font-size:15px;display:flex;align-items:center;gap:8px;">
                        <span>Verifikasi &amp; Penerimaan Barang Gudang</span>
                        <span class="badge badge-mono font-bold" style="color:#059669;" x-text="receiveForm.nomor_faktur_pembelian"></span>
                    </div>
                    <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
                        Vendor: <strong style="color:var(--color-ink);" x-text="receiveForm.nama_pemasok"></strong> &bull; Periksa kuantiti fisik &amp; harga faktur sebelum stok dimasukkan ke gudang
                    </div>
                </div>
                <button @click="showReceiveModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:14px;">
                <!-- 1. TABEL PENERIMAAN ITEM -->
                <div style="border:1px solid var(--color-hairline);border-radius:12px;overflow:hidden;background:var(--color-canvas-soft);">
                    <div style="padding:10px 14px;background:var(--color-canvas);border-bottom:1px solid var(--color-hairline);display:flex;align-items:center;justify-content:space-between;">
                        <span style="font-size:12px;font-weight:700;color:var(--color-ink);">Koreksi Kuantitas &amp; Harga Nota Vendor Masuk</span>
                        <span class="badge badge-info" style="font-size:10.5px;">Fisik vs PO</span>
                    </div>

                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="data-table" style="width:100%;min-width:580px;font-size:12px;">
                            <thead>
                                <tr>
                                    <th style="min-width:180px;">Nama Bahan / SKU</th>
                                    <th class="cell-center cell-nowrap" style="width:80px;">Qty PO</th>
                                    <th class="cell-center cell-nowrap" style="width:110px;">Qty Diterima *</th>
                                    <th class="cell-right cell-nowrap" style="width:130px;">Harga Satuan *</th>
                                    <th class="cell-right cell-nowrap" style="width:130px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(row, idx) in receiveForm.items" :key="row.item_id">
                                    <tr>
                                        <td>
                                            <div style="font-weight:700;color:var(--color-ink);" x-text="row.nama_item"></div>
                                            <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="row.kode_sku + ' (' + row.satuan + ')'"></div>
                                        </td>
                                        <td class="cell-center font-mono cell-nowrap" style="color:var(--color-ink-mute);" x-text="row.po_qty + ' ' + row.satuan"></td>
                                        <td class="cell-center cell-nowrap">
                                            <input type="number" min="0.01" step="any" x-model.number="row.qty" class="form-input font-mono text-center" style="font-size:12px;height:32px;font-weight:700;" @input="recalcReceiveRow(idx)">
                                        </td>
                                        <td class="cell-right cell-nowrap">
                                            <input type="text" x-model="row.harga_satuan" class="form-input font-mono text-right input-rupiah" style="font-size:12px;height:32px;" @input="recalcReceiveRow(idx)">
                                        </td>
                                        <td class="cell-right font-mono cell-nowrap" style="font-weight:700;color:var(--color-primary-deep);" x-text="formatRupiah(row.subtotal)"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr style="background:var(--color-canvas);font-weight:700;">
                                    <td colspan="4" class="cell-right cell-nowrap">Total Biaya Penerimaan Fisik:</td>
                                    <td class="cell-right font-mono cell-nowrap" style="font-size:14px;color:var(--color-primary-deep);" x-text="formatRupiah(receiveTotal)"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- KARTU INFORMASI BELANJA DRIVER (JIKA DIAMBIL DRIVER) -->
                <template x-if="receiveForm.metode_logistik === 'diambil_driver'">
                    <div style="padding:10px 14px;background:rgba(59,130,246,0.06);border:1px solid rgba(59,130,246,0.25);border-radius:10px;display:flex;flex-direction:column;gap:8px;">
                        <div class="flex items-center justify-between gap-2 flex-wrap">
                            <div class="flex items-center gap-2" style="font-size:12px;font-weight:700;color:#1e40af;">
                                <i data-lucide="truck" style="width:15px;height:15px;"></i>
                                <span>Realisasi Belanja Driver: <strong x-text="receiveForm.nama_driver || 'Armada Driver'"></strong></span>
                            </div>
                            <span class="badge" style="background:#dbeafe;color:#1e40af;font-size:11px;font-weight:700;" x-text="receiveForm.metode_bayar_belanja === 'tunai_driver' ? 'Dibekali Dana Tunai Toko' : 'Transfer Kantor / Tempo'"></span>
                        </div>
                        <div class="flex items-center justify-between gap-3 flex-wrap text-xs" style="color:var(--color-ink);">
                            <div>
                                <span style="color:var(--color-ink-mute);">Uang Keluar oleh Driver:</span>
                                <strong class="font-mono text-sm" style="color:#059669;margin-left:4px;" x-text="formatRupiah(receiveForm.nominal_dibayar_driver)"></strong>
                            </div>
                            <template x-if="receiveForm.driver_nota_photo">
                                <div class="flex items-center gap-2">
                                    <span style="color:var(--color-ink-mute);">Struk dari Driver:</span>
                                    <button type="button" @click="openReceiptPreview(receiveForm.driver_nota_photo, 'Struk Vendor Driver - ' + receiveForm.nama_pemasok)" class="btn btn-secondary btn-sm" style="padding:2px 8px;font-size:11px;font-weight:700;color:#2563eb;display:inline-flex;align-items:center;gap:4px;">
                                        <i data-lucide="image" style="width:13px;height:13px;"></i>
                                        <span>Lihat Foto Struk</span>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- 2. PARAMETER PEMBAYARAN & NOTA VENDOR -->
                <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);display:flex;flex-direction:column;gap:10px;">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Nomor Nota / Faktur Fisik dari Vendor *</label>
                            <input type="text" x-model="receiveForm.nomor_nota_vendor" class="form-input" placeholder="Contoh: INV-SPL-2026/089...">
                        </div>
                        <div>
                            <label class="form-label">Status Pembayaran Faktur *</label>
                            <select x-model="receiveForm.status_pembayaran" class="form-input">
                                <option value="lunas">Lunas (Tunai / Kas / Sudah Dibayar Driver)</option>
                                <option value="belum_lunas">Tempo (Masuk Hutang Dagang Vendor)</option>
                            </select>
                        </div>
                    </div>

                    <div x-show="receiveForm.status_pembayaran === 'lunas'">
                        <label class="form-label">Akun Kas Sumber Dana *</label>
                        <select x-model="receiveForm.akun_kas_id" class="form-input">
                            <?php foreach ($cashAccounts as $ca): ?>
                            <option value="<?= $ca['id'] ?>">
                                <?= htmlspecialchars($ca['nama_akun']) ?> (<?= Format::rupiah((float)$ca['saldo_saat_ini']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Upload Foto Nota Fisik -->
                    <div>
                        <label class="form-label">
                            Foto Bukti Nota Fisik Vendor / Surat Jalan (Wajib / Dianjurkan)
                            <template x-if="receiveForm.driver_nota_photo">
                                <span style="font-weight:normal;color:#059669;font-size:11px;">(Foto dari driver sudah tersimpan)</span>
                            </template>
                        </label>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <input type="file" x-ref="receivePhotoInput" @change="handleReceiveFileChange($event)" accept="image/*" capture="environment" class="form-input" style="padding:6px 10px;font-size:12px;flex:1;min-width:0;">
                            <template x-if="receivePhotoPreview">
                                <div style="display:flex;align-items:center;gap:6px;flex-shrink:0;">
                                    <img :src="receivePhotoPreview" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid var(--color-hairline);" alt="Preview">
                                    <button type="button" @click="clearReceivePhoto()" class="btn btn-ghost btn-sm" style="color:var(--color-danger);padding:4px;">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Catatan Penerimaan Gudang (Opsional)</label>
                        <input type="text" x-model="receiveForm.catatan" class="form-input" placeholder="Contoh: Barang diperiksa tim gudang dalam kondisi mulus...">
                    </div>
                </div>

                <!-- Footer Buttons -->
                <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-2 border-t border-hairline">
                    <button type="button" @click="showReceiveModal = false" class="btn btn-secondary w-full sm:w-auto">Batal</button>
                    <button type="button" @click="submitReceiveGoods()" :disabled="isSubmitting" class="btn btn-primary w-full sm:w-auto justify-center" style="font-weight:700;background:#059669;border-color:#059669;">
                        <i data-lucide="package-check"></i>
                        <span x-show="!isSubmitting">Konfirmasi Terima &amp; Tambah Stok</span>
                        <span x-show="isSubmitting">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL PANDUAN ALUR PEMBELIAN & PO (UNTUK ADMIN TOKO / OPERASIONAL AWAM)    -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showGuideModal" x-cloak class="modal-backdrop" @click.self="showGuideModal = false">
        <div class="modal-box purchase-modal-box custom-scrollbar" style="max-width:680px;max-height:90vh;overflow-y:auto;" @click.stop>
            <!-- Header Modal -->
            <div class="flex items-start justify-between pb-3 mb-3 border-b border-hairline">
                <div class="flex items-center gap-3">
                    <div style="width:40px;height:40px;border-radius:12px;background:rgba(59,130,246,0.12);color:#2563eb;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i data-lucide="book-open" style="width:20px;height:20px;"></i>
                    </div>
                    <div>
                        <h3 class="modal-title" style="font-size:16px;font-weight:800;color:var(--color-ink);">Panduan Alur Pembelian &amp; PO</h3>
                        <p style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Ringkasan praktis agar stok gudang &amp; uang kas tercatat dengan akurat</p>
                    </div>
                </div>
                <button type="button" @click="showGuideModal = false" class="btn btn-ghost btn-sm" style="padding:4px;border-radius:8px;" aria-label="Tutup">
                    <i data-lucide="x" style="width:18px;height:18px;"></i>
                </button>
            </div>

            <!-- Isi Panduan -->
            <div class="space-y-4 text-xs" style="color:var(--color-ink);line-height:1.5;">

                <!-- 1. Perbedaan Mode Input -->
                <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:12px;">
                    <div class="flex items-center gap-2 font-bold mb-2.5" style="font-size:13px;color:var(--color-ink);">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-500 text-white text-[11px]">1</span>
                        <span>Pilih Tombol Input Sesuai Kebutuhan</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div style="padding:10px 12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:10px;">
                            <div class="font-bold flex items-center gap-1.5 text-blue-600 mb-1" style="font-size:12px;">
                                <i data-lucide="receipt" style="width:14px;height:14px;"></i>
                                <span>+ Catat Faktur Langsung</span>
                            </div>
                            <div style="font-size:11px;color:var(--color-ink-secondary);margin-bottom:6px;">
                                Digunakan jika <strong>barang sudah dibeli &amp; tiba di toko</strong> saat ini juga.
                            </div>
                            <div style="font-size:10.5px;color:#059669;background:#ecfdf5;padding:4px 8px;border-radius:6px;font-weight:600;">
                                &#10003; Stok gudang langsung bertambah<br>
                                &#10003; Kas langsung dipotong (jika lunas)
                            </div>
                        </div>

                        <div style="padding:10px 12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:10px;">
                            <div class="font-bold flex items-center gap-1.5 text-emerald-600 mb-1" style="font-size:12px;">
                                <i data-lucide="shopping-cart" style="width:14px;height:14px;"></i>
                                <span>+ Buat PO Pembelian</span>
                            </div>
                            <div style="font-size:11px;color:var(--color-ink-secondary);margin-bottom:6px;">
                                Digunakan untuk <strong>pesan ke supplier</strong> atau tugas belanja driver besok/nanti.
                            </div>
                            <div style="font-size:10.5px;color:#2563eb;background:#eff6ff;padding:4px 8px;border-radius:6px;font-weight:600;">
                                &#9201; Stok &amp; kas BELUM berubah<br>
                                &#9201; Berubah saat fisik barang diterima
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Dua Opsi Logistik PO -->
                <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:12px;">
                    <div class="flex items-center gap-2 font-bold mb-2.5" style="font-size:13px;color:var(--color-ink);">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-500 text-white text-[11px]">2</span>
                        <span>Dua Metode Logistik pada PO</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <div style="padding:10px 12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:10px;">
                            <div class="font-bold text-amber-600 mb-1 flex items-center gap-1.5" style="font-size:12px;">
                                <span>&#x1F69A; Diambil Driver Toko</span>
                            </div>
                            <div style="font-size:11px;color:var(--color-ink-secondary);">
                                Tugas otomatis muncul di aplikasi HP Driver (<em>Pengiriman Driver</em>) pada jadwal belanja. Driver belanja di vendor &rarr; klik selesai di HP &rarr; upload foto struk &rarr; bawa barang ke toko.
                            </div>
                        </div>

                        <div style="padding:10px 12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:10px;">
                            <div class="font-bold text-indigo-600 mb-1 flex items-center gap-1.5" style="font-size:12px;">
                                <span>&#x1F3E2; Diantar oleh Supplier</span>
                            </div>
                            <div style="font-size:11px;color:var(--color-ink-secondary);">
                                Pihak vendor/ekspedisi yang mengantarkan barang langsung ke gudang kita sesuai tanggal perkiraan tiba.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Konfirmasi Barang Tiba di Gudang -->
                <div style="padding:12px 14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:12px;">
                    <div class="flex items-center gap-2 font-bold mb-1.5" style="font-size:13px;color:#065f46;">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-emerald-600 text-white text-[11px]">3</span>
                        <span>Saat Barang Sampai di Toko / Gudang (Wajib Verifikasi!)</span>
                    </div>
                    <p style="font-size:11px;color:var(--color-ink-mute);margin-bottom:8px;">
                        Setiap barang PO yang tiba <strong>wajib dikonfirmasi penerimaannya</strong> agar stok gudang resmi bertambah:
                    </p>
                    <div style="padding:10px 12px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:10px;display:flex;flex-direction:column;gap:6px;">
                        <div class="flex items-center gap-2">
                            <span class="badge" style="background:#059669;color:#fff;font-weight:700;font-size:10px;">CARA CEPAT</span>
                            <span style="font-weight:700;color:#065f46;font-size:11.5px;">Klik tombol hijau [&#x1F4E6; Terima] di baris tabel pembelian</span>
                        </div>
                        <ul style="margin:0;padding-left:18px;font-size:11px;color:#047857;display:flex;flex-direction:column;gap:3px;">
                            <li>Hitung kuantitas riil barang yang diterima (bisa disesuaikan jika ada selisih/rusak).</li>
                            <li>Jika belanjaan driver, admin bisa langsung klik <strong>"Lihat Foto Struk"</strong> untuk mencocokkan bon fisik.</li>
                            <li>Klik <strong>"Konfirmasi Terima &amp; Tambah Stok"</strong> &rarr; Stok resmi masuk gudang dan harga modal (HPP) terhitung otomatis.</li>
                        </ul>
                    </div>
                </div>

                <!-- 4. Kendala Driver -->
                <div style="padding:10px 14px;background:rgba(239,68,68,0.06);border:1px solid rgba(239,68,68,0.25);border-radius:12px;">
                    <div class="flex items-center gap-2 font-bold mb-1" style="font-size:12px;color:#991b1b;">
                        <i data-lucide="alert-circle" style="width:15px;height:15px;"></i>
                        <span>Jika Driver Melaporkan Kendala (Toko Tutup / Stok Habis)</span>
                    </div>
                    <p style="font-size:11px;color:var(--color-ink-secondary);margin:0;">
                        Status PO akan otomatis menjadi <span class="badge badge-danger" style="font-size:9.5px;padding:1px 5px;">KENDALA / BATAL</span>. Tombol terima barang terkunci demi keamanan. Admin cukup buka <strong>Detail PO</strong> untuk klik tombol <strong>[Jadwalkan Ulang / Ganti Driver]</strong> atau <strong>[Batalkan PO]</strong>.
                    </p>
                </div>

            </div>

            <!-- Footer Modal -->
            <div class="flex items-center justify-between gap-3 pt-3 mt-4 border-t border-hairline flex-wrap">
                <span style="font-size:11px;color:var(--color-ink-mute);display:flex;align-items:center;gap:4px;">
                    <i data-lucide="info" style="width:14px;height:14px;color:var(--color-primary);"></i>
                    <span>Semua nomor dokumen pengadaan berawalan <strong>PB-</strong></span>
                </span>
                <button type="button" @click="showGuideModal = false" class="btn btn-primary w-full sm:w-auto" style="font-weight:700;padding:6px 20px;">
                    <span>Mengerti &amp; Tutup</span>
                </button>
            </div>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- STYLES KHUSUS RECEIPT PHOTO VIEWER (MOBILE-FIRST & GESTURES)              -->
    <!-- ========================================================================= -->
    <style>
    /* ========================================================================= */
    /* RESPONSIVE STYLES FOR ADD PURCHASE MODAL (MOBILE HP OPTIMIZATION)         */
    /* ========================================================================= */
    @media (max-width: 639px) {
        .purchase-modal-box {
            padding: 14px 12px !important;
            max-height: 92vh !important;
            max-height: 92dvh !important;
            border-radius: 16px !important;
        }
        .purchase-item-desktop-header {
            display: none !important;
        }
        .purchase-item-row {
            background: var(--color-canvas, #ffffff);
            border: 1px solid var(--color-hairline);
            border-radius: 10px;
            padding: 10px;
            display: flex !important;
            flex-direction: column !important;
            gap: 8px !important;
            box-shadow: 0 1px 3px rgba(0,0,0,0.03);
        }
        .purchase-item-mobile-header {
            display: flex !important;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 6px;
            border-bottom: 1px dashed var(--color-hairline);
        }
        .purchase-item-label {
            display: block !important;
            font-size: 10.5px;
            font-weight: 700;
            color: var(--color-ink-mute);
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .purchase-item-select-col {
            width: 100% !important;
        }
        .purchase-item-inputs-row {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 8px !important;
            width: 100% !important;
        }
        .purchase-item-qty-col,
        .purchase-item-price-col {
            width: 100% !important;
        }
        .purchase-item-desktop-delete {
            display: none !important;
        }
        .purchase-dropdown-menu {
            position: absolute;
            top: calc(100% + 4px);
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            min-width: 0 !important;
            max-width: 100% !important;
            z-index: 1050;
            border-radius: 10px;
            overflow: hidden;
            background: var(--color-canvas);
            border: 1px solid var(--color-hairline);
            box-shadow: 0 12px 28px -4px rgba(0,0,0,0.2);
        }
    }

    @media (min-width: 640px) {
        .purchase-modal-box {
            padding: 24px !important;
        }
        .purchase-item-desktop-header {
            display: flex !important;
            align-items: center;
            gap: 8px;
            padding-bottom: 6px;
            font-size: 11px;
            font-weight: 700;
            color: var(--color-ink-mute);
            text-transform: uppercase;
        }
        .purchase-item-row {
            display: flex !important;
            align-items: center;
            gap: 8px;
            background: transparent;
            border: none;
            padding: 0;
            box-shadow: none;
        }
        .purchase-item-mobile-header {
            display: none !important;
        }
        .purchase-item-label {
            display: none !important;
        }
        .purchase-item-select-col {
            flex: 1;
            min-width: 0;
        }
        .purchase-item-inputs-row {
            display: flex !important;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .purchase-item-qty-col {
            width: 100px;
            flex-shrink: 0;
        }
        .purchase-item-price-col {
            width: 140px;
            flex-shrink: 0;
        }
        .purchase-item-desktop-delete {
            display: inline-flex !important;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .purchase-dropdown-menu {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            min-width: 320px;
            max-width: 420px;
            z-index: 1050;
            border-radius: 10px;
            overflow: hidden;
            background: var(--color-canvas);
            border: 1px solid var(--color-hairline);
            box-shadow: 0 12px 28px -4px rgba(0,0,0,0.15);
        }
    }
    .receipt-backdrop {
        z-index: 99999;
        background: rgba(15, 23, 42, 0.65) !important;
        backdrop-filter: blur(14px) saturate(160%) !important;
        -webkit-backdrop-filter: blur(14px) saturate(160%) !important;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 16px;
        position: fixed;
        inset: 0;
        overscroll-behavior: contain;
        touch-action: none;
    }
    .receipt-container {
        width: 100%;
        max-width: 980px;
        height: 88vh;
        max-height: 88vh;
        display: flex;
        flex-direction: column;
        background: var(--color-surface, #ffffff);
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 25px 60px -12px rgba(0, 0, 0, 0.7);
        border: 1px solid var(--color-hairline);
        position: relative;
    }
    .receipt-viewport {
        flex: 1;
        position: relative;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #050811;
        touch-action: none;
        user-select: none;
        -webkit-user-select: none;
    }
    .receipt-floating-toolbar {
        position: absolute;
        bottom: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 30;
        display: flex;
        align-items: center;
        gap: 4px;
        background: rgba(15, 23, 42, 0.88);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        padding: 5px 8px;
        border-radius: 9999px;
        border: 1px solid rgba(255, 255, 255, 0.18);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.6);
        color: #f8fafc;
        user-select: none;
    }
    .receipt-tool-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: transparent;
        border: none;
        color: #f8fafc;
        cursor: pointer;
        transition: all 0.15s ease;
        padding: 0;
    }
    .receipt-tool-btn:hover:not(:disabled) {
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff;
    }
    .receipt-tool-btn:active:not(:disabled) {
        transform: scale(0.92);
    }
    .receipt-tool-btn:disabled {
        opacity: 0.35;
        cursor: not-allowed;
    }
    .receipt-tool-badge {
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.12);
        border-radius: 9999px;
        padding: 4px 10px;
        font-family: var(--font-mono);
        font-size: 11.5px;
        font-weight: 700;
        color: #f8fafc;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .receipt-tool-badge:hover {
        background: rgba(255, 255, 255, 0.2);
    }
    .receipt-tool-divider {
        width: 1px;
        height: 18px;
        background: rgba(255, 255, 255, 0.2);
        margin: 0 3px;
    }

    @media (max-width: 640px) {
        .receipt-backdrop {
            padding: 0 !important;
        }
        .receipt-container {
            max-width: 100vw !important;
            width: 100vw !important;
            height: 100dvh !important;
            max-height: 100dvh !important;
            border-radius: 0 !important;
            border: none !important;
        }
        .receipt-floating-toolbar {
            bottom: calc(16px + env(safe-area-inset-bottom, 0px));
            gap: 6px;
            padding: 6px 12px;
        }
        .receipt-tool-btn {
            width: 40px;
            height: 40px;
        }
        .receipt-tool-badge {
            padding: 5px 12px;
            font-size: 12px;
        }
    }

    /* =========================================================================
     * MODERN PO RADIO SELECTION CARDS - Large (Logistik)
     * ========================================================================= */
    .po-radio-card {
        position: relative;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 12px 14px;
        border: 1.5px solid var(--color-hairline);
        border-radius: 10px;
        background: var(--color-canvas-soft);
        transition: border-color 0.17s ease, background 0.17s ease, box-shadow 0.17s ease;
        user-select: none;
    }
    .po-radio-card:hover {
        border-color: rgba(59,130,246,0.45);
        background: rgba(59,130,246,0.035);
    }
    .po-radio-card.is-selected {
        border-color: var(--color-primary, #3b82f6);
        background: rgba(59,130,246,0.07);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    }
    .po-radio-dot {
        flex-shrink: 0;
        margin-top: 2px;
        width: 17px;
        height: 17px;
        border-radius: 50%;
        border: 2px solid var(--color-hairline);
        background: var(--color-canvas);
        transition: border-color 0.17s ease, background 0.17s ease;
        position: relative;
    }
    .po-radio-dot::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background: var(--color-primary, #3b82f6);
        transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .po-radio-dot.is-checked {
        border-color: var(--color-primary, #3b82f6);
        background: var(--color-canvas);
    }
    .po-radio-dot.is-checked::after {
        transform: translate(-50%, -50%) scale(1);
    }
    .po-radio-body {
        display: flex;
        align-items: center;
        gap: 10px;
        flex: 1;
        min-width: 0;
    }
    .po-radio-icon {
        font-size: 22px;
        line-height: 1;
        flex-shrink: 0;
    }
    .po-radio-title {
        font-size: 12.5px;
        font-weight: 700;
        color: var(--color-ink);
        line-height: 1.3;
    }
    .po-radio-desc {
        font-size: 10.5px;
        color: var(--color-ink-mute);
        margin-top: 2px;
        line-height: 1.4;
    }
    .po-radio-card.is-selected .po-radio-title {
        color: var(--color-primary, #3b82f6);
    }

    /* =========================================================================
     * MODERN PO RADIO SELECTION CARDS - Small (Metode Bayar)
     * ========================================================================= */
    .po-radio-card-sm {
        position: relative;
        display: flex;
        align-items: center;
        gap: 9px;
        padding: 9px 12px;
        border: 1.5px solid var(--color-hairline);
        border-radius: 9px;
        background: var(--color-canvas-soft);
        transition: border-color 0.17s ease, background 0.17s ease, box-shadow 0.17s ease;
        user-select: none;
    }
    .po-radio-card-sm:hover {
        border-color: rgba(59,130,246,0.45);
        background: rgba(59,130,246,0.035);
    }
    .po-radio-card-sm.is-selected {
        border-color: var(--color-primary, #3b82f6);
        background: rgba(59,130,246,0.07);
        box-shadow: 0 0 0 3px rgba(59,130,246,0.12);
    }
    .po-radio-dot-sm {
        flex-shrink: 0;
        width: 15px;
        height: 15px;
        border-radius: 50%;
        border: 2px solid var(--color-hairline);
        background: var(--color-canvas);
        transition: border-color 0.17s ease;
        position: relative;
    }
    .po-radio-dot-sm::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%) scale(0);
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: var(--color-primary, #3b82f6);
        transition: transform 0.18s cubic-bezier(0.34, 1.56, 0.64, 1);
    }
    .po-radio-dot-sm.is-checked {
        border-color: var(--color-primary, #3b82f6);
    }
    .po-radio-dot-sm.is-checked::after {
        transform: translate(-50%, -50%) scale(1);
    }
    .po-radio-body-sm {
        display: flex;
        align-items: center;
        gap: 7px;
        flex: 1;
        min-width: 0;
    }
    .po-radio-icon-sm {
        font-size: 17px;
        line-height: 1;
        flex-shrink: 0;
    }
    .po-radio-title-sm {
        font-size: 12px;
        font-weight: 700;
        color: var(--color-ink);
        line-height: 1.2;
    }
    .po-radio-desc-sm {
        font-size: 10px;
        color: var(--color-ink-mute);
        margin-top: 1px;
        line-height: 1.3;
    }
    .po-radio-card-sm.is-selected .po-radio-title-sm {
        color: var(--color-primary, #3b82f6);
    }

    /* Keyboard navigation accessibility focus & active states */
    .po-radio-card:focus-visible,
    .po-radio-card-sm:focus-visible {
        outline: 2px solid var(--color-primary, #3b82f6) !important;
        outline-offset: 2px;
    }
    .searchable-option {
        transition: background 0.12s ease;
    }
    .searchable-option:hover,
    .searchable-option.is-active {
        background: rgba(59, 130, 246, 0.12) !important;
        outline: 1.5px solid rgba(59, 130, 246, 0.4) !important;
        outline-offset: -1px;
    }
    .searchable-option.is-selected {
        background: rgba(59, 130, 246, 0.16) !important;
    }
    .kbd-badge {
        display: inline-block;
        padding: 1.5px 5.5px;
        font-size: 10px;
        font-family: var(--font-mono, monospace);
        font-weight: 700;
        color: var(--color-ink, #0f172a);
        background: var(--color-canvas, #ffffff);
        border: 1px solid var(--color-hairline-strong, #cbd5e1);
        border-bottom: 2px solid var(--color-hairline-strong, #94a3b8);
        border-radius: 4px;
        line-height: 1.2;
    }

    /* =========================================================================
     * FULL MODAL RESPONSIVENESS - Detail 5-tab, Receive, Pay, Cancel
     * ========================================================================= */

    /* Detail modal - tabs scroll on small screens */
    .modal-tab-nav {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        scrollbar-width: none;
    }
    .modal-tab-nav::-webkit-scrollbar { display: none; }

    /* Receive modal table scroll wrapper */
    .receive-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    @media (max-width: 639px) {
        /* po-radio-card stacks icon + text nicely on small screens */
        .po-radio-card {
            padding: 10px 12px;
        }
        .po-radio-icon {
            font-size: 18px;
        }
        .po-radio-title { font-size: 12px; }
        .po-radio-desc { font-size: 10px; }

        /* Detail modal - tight tab buttons */
        .modal-tab-btn {
            white-space: nowrap;
            padding: 7px 10px !important;
            font-size: 11.5px !important;
        }

        /* Receive/Pay/Cancel modal actions stack vertically */
        .purchase-modal-action-row {
            flex-direction: column !important;
            gap: 8px !important;
        }
        .purchase-modal-action-row .btn {
            width: 100% !important;
            justify-content: center !important;
        }

        /* Full-width modals on xs phones */
        .modal-box-lg,
        .modal-box {
            margin: 0 !important;
            border-radius: 16px 16px 0 0 !important;
            max-height: 95vh !important;
            max-height: 95dvh !important;
        }
    }
    </style>

    <!-- ========================================================================= -->
    <!-- MODAL RESPONSIVE PREVIEW FOTO NOTA (TOUCH PINCH & PAN VIEWER)             -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showReceiptModal" 
         x-cloak 
         class="receipt-backdrop" 
         @keydown.window="handleViewerKeydown($event)">
        
        <div class="receipt-container" @click.stop>
            <!-- Header Modal -->
            <div class="receipt-header" style="display:flex;justify-content:space-between;align-items:center;padding:12px 18px;border-bottom:1px solid var(--color-hairline);background:var(--color-surface, #ffffff);z-index:10;">
                <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                    <div style="width:34px;height:34px;border-radius:10px;background:rgba(59,130,246,0.12);display:flex;align-items:center;justify-content:center;color:#3b82f6;flex-shrink:0;">
                        <i data-lucide="image" style="width:18px;height:18px;"></i>
                    </div>
                    <div style="min-width:0;">
                        <h3 style="font-size:14px;font-weight:700;color:var(--color-ink-primary);margin:0;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">Foto Bukti Nota Pembelian</h3>
                        <div style="font-size:11px;color:var(--color-ink-mute);font-family:monospace;" x-text="receiptModalTitle"></div>
                    </div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;flex-shrink:0;">
                    <button type="button" @click="closeReceiptPreview()" class="btn btn-ghost btn-sm" style="padding:6px;border-radius:8px;" title="Tutup">
                        <i data-lucide="x" style="width:20px;height:20px;"></i>
                    </button>
                </div>
            </div>

            <!-- Viewport Area Foto Gambar (Interactive Pinch & Pan Viewport) -->
            <div class="receipt-viewport" 
                 x-ref="receiptViewport"
                 @wheel.prevent="handleWheel($event)"
                 @mousedown="handleMouseDown($event)"
                 @touchstart="handleTouchStart($event)"
                 @touchmove.prevent="handleTouchMove($event)"
                 @touchend="handleTouchEnd($event)"
                 @touchcancel="handleTouchEnd($event)"
                 @dblclick="toggleDoubleTap($event.clientX, $event.clientY)">

                <!-- State Error jika file fisik tidak ditemukan / dibersihkan -->
                <div x-show="receiptLoadError" style="margin:auto;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;max-width:440px;width:100%;padding:32px 16px;z-index:5;">
                    <div style="width:56px;height:56px;border-radius:16px;background:rgba(239,68,68,0.15);display:flex;align-items:center;justify-content:center;color:#ef4444;margin:0 auto 16px auto;box-shadow:0 4px 12px rgba(239,68,68,0.12);">
                        <i data-lucide="image-off" style="width:28px;height:28px;display:block;"></i>
                    </div>
                    <div style="font-size:15px;font-weight:700;color:#f8fafc;margin-bottom:6px;text-align:center;width:100%;">Foto Bukti Nota Tidak Ditemukan</div>
                    <div style="font-size:12.5px;color:#94a3b8;max-width:380px;line-height:1.6;margin:0 auto;text-align:center;width:100%;">
                        Berkas foto fisik nota ini tidak ditemukan di direktori server. Kemungkinan merupakan berkas lama sebelum pembaruan sistem yang telah dibersihkan.
                    </div>
                </div>

                <!-- Gambar Nota Utama (Hardware-Accelerated CSS Transform) -->
                <template x-if="receiptModalUrl">
                    <img :src="receiptModalUrl" 
                         alt="Foto Nota Pembelian" 
                         x-show="!receiptLoadError"
                         @load="onReceiptImageLoaded()"
                         @error="receiptLoadError = true; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });"
                         draggable="false"
                         :style="{
                             display: receiptLoadError ? 'none' : 'block',
                             maxWidth: '100%',
                             maxHeight: '100%',
                             objectFit: 'contain',
                             transform: 'translate3d(' + zoomPanX + 'px, ' + zoomPanY + 'px, 0) scale(' + zoomScale + ') rotate(' + zoomRotate + 'deg)',
                             transformOrigin: 'center center',
                             transition: isDragging ? 'none' : 'transform 0.18s cubic-bezier(0.2, 0, 0, 1)',
                             cursor: zoomScale > 1.05 ? (isDragging ? 'grabbing' : 'grab') : 'zoom-in',
                             userSelect: 'none',
                             webkitUserDrag: 'none'
                         }">
                </template>

                <!-- Floating Glassmorphism Controls (Bar Alat Sentuh Mengambang) -->
                <div x-show="!receiptLoadError" class="receipt-floating-toolbar">
                    <!-- Zoom Out -->
                    <button type="button" @click="zoomStep(-0.3)" class="receipt-tool-btn" title="Perkecil Zoom (-)" :disabled="zoomScale <= 0.6">
                        <i data-lucide="minus" style="width:16px;height:16px;"></i>
                    </button>

                    <!-- Persentase & Reset -->
                    <button type="button" @click="resetZoom()" class="receipt-tool-badge" title="Klik untuk Reset Tampilan Fit">
                        <span x-text="Math.round(zoomScale * 100) + '%'"></span>
                    </button>

                    <!-- Zoom In -->
                    <button type="button" @click="zoomStep(0.3)" class="receipt-tool-btn" title="Perbesar Zoom (+)" :disabled="zoomScale >= 5.0">
                        <i data-lucide="plus" style="width:16px;height:16px;"></i>
                    </button>

                    <div class="receipt-tool-divider"></div>

                    <!-- Rotate 90 Deg Clockwise -->
                    <button type="button" @click="rotateClockwise()" class="receipt-tool-btn" title="Putar Posisi 90&deg;">
                        <i data-lucide="rotate-cw" style="width:16px;height:16px;"></i>
                    </button>

                    <!-- Fit / Reset -->
                    <button type="button" @click="resetZoom()" class="receipt-tool-btn" title="Reset Ukuran Normal (Fit Layar)">
                        <i data-lucide="maximize-2" style="width:15px;height:15px;"></i>
                    </button>
                </div>
            </div>

            <!-- Footer Modal (Petunjuk Gestur & Navigasi) -->
            <div class="receipt-footer" style="display:flex;align-items:center;justify-content:center;padding:10px 18px;border-top:1px solid var(--color-hairline);background:var(--color-canvas-soft);font-size:11.5px;color:var(--color-ink-mute);z-index:10;text-align:center;">
                <div style="display:flex;align-items:center;gap:6px;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                    <i data-lucide="info" style="width:14px;height:14px;flex-shrink:0;"></i>
                    <span class="hidden sm:inline">Geser untuk memindahkan foto &bull; Scroll mouse / Cubit 2 jari untuk zoom &bull; Ketuk 2x untuk zoom cepat</span>
                    <span class="inline sm:hidden">Cubit 2 jari untuk zoom &bull; Geser foto &bull; Ketuk 2x zoom</span>
                </div>
            </div>
        </div>
    </div>
    </template>

</div>

<script>
function purchaseApp() {
    return {
        purchases: <?= json_encode($purchases) ?>,
        suppliers: <?= json_encode($suppliers) ?>,
        availableItems: <?= json_encode($items) ?>,
        cashAccounts: <?= json_encode($cashAccounts) ?>,
        drivers: <?= json_encode($drivers ?? []) ?>,
        suggestedPbSuffix: '<?= $suggestedPbSuffix ?>',

        searchQuery: '',
        filterStatus: 'semua',
        filterSupplier: 'semua',
        filterType: 'semua',

        showModal: false,
        showDetailModal: false,
        showPayModal: false,
        showCancelModal: false,
        showReceiptModal: false,
        showEditPoModal: false,
        showReceiveModal: false,
        showGuideModal: false,

        activeDetailTab: 'items',
        receiptModalUrl: '',
        receiptModalTitle: '',
        receiptLoadError: false,
        zoomScale: 1.0,
        zoomPanX: 0,
        zoomPanY: 0,
        zoomRotate: 0,
        isDragging: false,
        isPinching: false,
        dragStartX: 0,
        dragStartY: 0,
        pinchStartDist: 0,
        pinchStartScale: 1.0,
        lastTapTime: 0,

        isSubmitting: false,
        isLoadingDetail: false,

        activeDetail: null,
        activePay: null,
        activeCancel: null,
        cancelAlasan: '',
        cancelError: '',

        showAllMaterials: false,
        photoFile: null,
        photoPreview: null,
        receivePhotoFile: null,
        receivePhotoPreview: null,
        currentPemasokId: '',

        form: {
            jenis_dokumen: 'faktur',
            pemasok_id: '',
            nomor_faktur_suffix: '',
            tanggal_pembelian: '<?= date('Y-m-d') ?>',
            status_pembayaran: 'lunas',
            akun_kas_id: '<?= $cashAccounts[0]['id'] ?? '' ?>',
            catatan: '',
            metode_logistik: 'diambil_driver',
            sales_driver_id: '',
            tanggal_jadwal_belanja: '<?= date('Y-m-d') ?>',
            instruksi_driver: '',
            metode_bayar_belanja: 'tunai_driver',
            items: []
        },

        editPoForm: {
            id: null,
            nomor_faktur: '',
            pemasok_id: '',
            tanggal_pembelian: '<?= date('Y-m-d') ?>',
            metode_logistik: 'diantar_supplier',
            sales_driver_id: '',
            tanggal_jadwal_belanja: '<?= date('Y-m-d') ?>',
            instruksi_driver: '',
            metode_bayar_belanja: 'tempo_vendor',
            catatan: '',
            items: []
        },

        receiveForm: {
            id: null,
            nomor_faktur_pembelian: '',
            nama_pemasok: '',
            total_biaya_estimasi: 0,
            metode_bayar_belanja: '',
            nomor_nota_vendor: '',
            status_pembayaran: 'lunas',
            akun_kas_id: '<?= $cashAccounts[0]['id'] ?? '' ?>',
            catatan: '',
            items: []
        },

        payForm: {
            pembelian_id: '',
            akun_kas_id: '<?= $cashAccounts[0]['id'] ?? '' ?>',
            tanggal_bayar: '<?= date('Y-m-d') ?>',
            catatan: ''
        },

        init() {
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        get selectedSupplier() {
            if (!this.form.pemasok_id) return null;
            return this.suppliers.find(s => String(s.id) === String(this.form.pemasok_id)) || null;
        },

        get supplierItems() {
            if (!this.form.pemasok_id) return [];
            return this.availableItems.filter(it => String(it.pemasok_utama_id) === String(this.form.pemasok_id));
        },

        get filteredPurchases() {
            return this.purchases.filter(p => {
                const q = this.searchQuery.toLowerCase().trim();
                const matchQuery = !q ||
                    (p.nomor_faktur_pembelian && p.nomor_faktur_pembelian.toLowerCase().includes(q)) ||
                    (p.nama_pemasok && p.nama_pemasok.toLowerCase().includes(q)) ||
                    (p.nama_driver && p.nama_driver.toLowerCase().includes(q)) ||
                    (p.catatan && p.catatan.toLowerCase().includes(q));

                const matchStatus = this.filterStatus === 'semua' || p.status_pembayaran === this.filterStatus;
                const matchSupplier = this.filterSupplier === 'semua' || String(p.pemasok_id) === String(this.filterSupplier);
                const matchType = this.filterType === 'semua' || (p.jenis_dokumen || 'faktur') === this.filterType;

                return matchQuery && matchStatus && matchSupplier && matchType;
            });
        },

        get formTotal() {
            return this.form.items.reduce((sum, it) => {
                const rawHarga = typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0);
                return sum + (Number(it.qty || 0) * rawHarga);
            }, 0);
        },

        get editPoTotal() {
            if (!this.editPoForm.items) return 0;
            return this.editPoForm.items.reduce((sum, it) => {
                const rawHarga = typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0);
                return sum + (Number(it.qty || 0) * rawHarga);
            }, 0);
        },

        get receiveTotal() {
            if (!this.receiveForm.items) return 0;
            return this.receiveForm.items.reduce((sum, it) => {
                const rawHarga = typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0);
                return sum + (Number(it.qty || 0) * rawHarga);
            }, 0);
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
        },

        getFilteredItems(row) {
            const q = (row.search || '').toLowerCase().trim();
            let list = this.availableItems;

            // Filter by supplier unless toggle "showAllMaterials" is active
            if (!this.showAllMaterials && this.form.pemasok_id) {
                list = list.filter(it => String(it.pemasok_utama_id) === String(this.form.pemasok_id));
            }

            if (!q) return list;
            return list.filter(it => {
                const name = (it.nama_item || '').toLowerCase();
                const sku = (it.kode_sku || '').toLowerCase();
                const tipe = (it.tipe_item || '').toLowerCase();
                return name.includes(q) || sku.includes(q) || tipe.includes(q);
            });
        },

        getSelectedItemName(itemId) {
            if (!itemId) return '-- Pilih Bahan Baku / Kemasan --';
            const it = this.availableItems.find(x => String(x.id) === String(itemId));
            if (!it) return '-- Pilih Bahan Baku / Kemasan --';
            return it.nama_item + ' (' + (it.tipe_item === 'bahan_mentah' ? 'Mentah' : 'Kemasan') + ' - ' + it.satuan_dasar + ')';
        },

        getSelectedItemUnit(itemId) {
            if (!itemId) return '';
            const it = this.availableItems.find(x => String(x.id) === String(itemId));
            return it ? it.satuan_dasar : '';
        },

        async onSupplierChange() {
            if (this.form.items.some(it => it.item_id)) {
                const confirmed = window.AppConfirm ? await window.AppConfirm({
                    title: 'Ganti Pemasok?',
                    message: 'Mengganti pemasok akan mereset daftar barang yang telah dipilih di tabel. Lanjutkan?',
                    type: 'warning',
                    confirmText: 'Ya, Ganti Pemasok',
                    cancelText: 'Batal'
                }) : confirm('Mengganti pemasok akan mereset daftar barang yang telah dipilih. Lanjutkan?');

                if (!confirmed) {
                    this.form.pemasok_id = this.currentPemasokId;
                    return;
                }
            }
            this.currentPemasokId = this.form.pemasok_id;
            this.form.items = [];
            if (this.form.pemasok_id) {
                this.addItemRow();
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                    setTimeout(() => {
                        const btn = document.getElementById('item-btn-0');
                        if (btn) btn.focus();
                    }, 50);
                });
            }
        },

        focusAfterVendor() {
            if (!this.form.pemasok_id) return;
            if (this.form.items.length === 0) {
                this.addItemRow();
            }
            this.$nextTick(() => {
                const firstBtn = document.getElementById('item-btn-0');
                if (firstBtn) firstBtn.focus();
            });
        },

        openAddModal(type = 'faktur') {
            this.form = {
                jenis_dokumen: type,
                pemasok_id: '',
                nomor_faktur_suffix: this.suggestedPbSuffix,
                tanggal_pembelian: '<?= date('Y-m-d') ?>',
                status_pembayaran: type === 'po' ? 'belum_lunas' : 'lunas',
                akun_kas_id: '<?= $cashAccounts[0]['id'] ?? '' ?>',
                catatan: '',
                metode_logistik: 'diambil_driver',
                sales_driver_id: '',
                tanggal_jadwal_belanja: '<?= date('Y-m-d') ?>',
                instruksi_driver: '',
                metode_bayar_belanja: 'tunai_driver',
                items: []
            };
            this.currentPemasokId = '';
            this.showAllMaterials = false;
            this.clearPhoto();
            this.showModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                setTimeout(() => {
                    const vendorSel = document.getElementById('purchase-vendor-select');
                    if (vendorSel) vendorSel.focus();
                }, 80);
            });
        },

        handleModalKeydown(e) {
            if (!this.showModal) return;

            // 1. Submit PO / Faktur with Ctrl+Enter or Cmd+Enter
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                e.preventDefault();
                this.submitPurchase();
                return;
            }

            // 2. Add row with Alt+N or F2
            if ((e.altKey && (e.key === 'n' || e.key === 'N')) || e.key === 'F2') {
                e.preventDefault();
                if (this.form.pemasok_id) {
                    this.addItemRowAndFocus();
                } else {
                    toast.warning('Pilih vendor pemasok terlebih dahulu!');
                    const vendorSel = document.getElementById('purchase-vendor-select');
                    if (vendorSel) vendorSel.focus();
                }
                return;
            }

            // 3. Escape key to close modal (if no dropdown is open)
            if (e.key === 'Escape') {
                const openRow = this.form.items.find(r => r.dropdownOpen);
                if (openRow) {
                    openRow.dropdownOpen = false;
                    e.stopPropagation();
                    return;
                }
                this.showModal = false;
            }
        },

        addItemRow() {
            this.form.items.push({ 
                item_id: '', 
                qty: 1, 
                harga_satuan: '0', 
                subtotal: 0, 
                dropdownOpen: false, 
                search: '',
                activeIndex: 0
            });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        addItemRowAndFocus() {
            this.addItemRow();
            this.$nextTick(() => {
                const newIdx = this.form.items.length - 1;
                const newRow = this.form.items[newIdx];
                this.openItemDropdown(newRow, newIdx);
            });
        },

        removeItemRow(idx) {
            if (this.form.items.length <= 1) {
                this.form.items = [{
                    item_id: '',
                    qty: 1,
                    harga_satuan: '0',
                    subtotal: 0,
                    dropdownOpen: false,
                    search: '',
                    activeIndex: 0
                }];
                this.$nextTick(() => {
                    const btn = document.getElementById('item-btn-0');
                    if (btn) btn.focus();
                });
                return;
            }
            this.form.items.splice(idx, 1);
            this.$nextTick(() => {
                const targetIdx = Math.max(0, idx - 1);
                const btn = document.getElementById('item-btn-' + targetIdx);
                if (btn) btn.focus();
            });
        },

        openItemDropdown(row, idx) {
            this.form.items.forEach((r, i) => {
                if (i !== idx) r.dropdownOpen = false;
            });
            row.dropdownOpen = true;
            row.search = '';
            const items = this.getFilteredItems(row);
            const currIdx = items.findIndex(it => String(it.id) === String(row.item_id));
            row.activeIndex = currIdx >= 0 ? currIdx : 0;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                const searchInp = document.getElementById('item-search-' + idx);
                if (searchInp) {
                    searchInp.focus();
                    searchInp.select();
                }
                this.scrollItemIntoView(idx, row.activeIndex);
            });
        },

        toggleItemDropdown(row, idx) {
            if (row.dropdownOpen) {
                this.closeItemDropdown(row, idx);
            } else {
                this.openItemDropdown(row, idx);
            }
        },

        closeItemDropdown(row, idx) {
            row.dropdownOpen = false;
            this.$nextTick(() => {
                const btn = document.getElementById('item-btn-' + idx);
                if (btn) btn.focus();
            });
        },

        navigateDropdownItem(row, idx, dir) {
            const items = this.getFilteredItems(row);
            if (!items || items.length === 0) return;
            let next = (row.activeIndex || 0) + dir;
            if (next < 0) next = 0;
            if (next >= items.length) next = items.length - 1;
            row.activeIndex = next;
            this.scrollItemIntoView(idx, next);
        },

        scrollItemIntoView(rowIdx, itemIdx) {
            this.$nextTick(() => {
                const list = document.getElementById('item-opt-list-' + rowIdx);
                const opt = document.getElementById('item-opt-' + rowIdx + '-' + itemIdx);
                if (list && opt) {
                    const lTop = list.scrollTop;
                    const lBottom = lTop + list.clientHeight;
                    const oTop = opt.offsetTop;
                    const oBottom = oTop + opt.clientHeight;
                    if (oTop < lTop) {
                        list.scrollTop = oTop;
                    } else if (oBottom > lBottom) {
                        list.scrollTop = oBottom - list.clientHeight;
                    }
                }
            });
        },

        selectActiveDropdownItem(row, idx) {
            const items = this.getFilteredItems(row);
            if (!items || items.length === 0) return;
            const targetIdx = Math.max(0, Math.min(items.length - 1, row.activeIndex || 0));
            this.selectItemRow(row, idx, items[targetIdx]);
        },

        selectItemRow(row, idx, item) {
            const alreadyExists = this.form.items.some((r, i) => i !== idx && String(r.item_id) === String(item.id));
            if (alreadyExists) {
                toast.warning(`Bahan "${item.nama_item}" sudah ada di daftar. Silakan sesuaikan jumlah kuantitas pada baris tersebut.`);
                row.dropdownOpen = false;
                return;
            }
            row.item_id = item.id;
            row.dropdownOpen = false;
            row.search = '';
            row.activeIndex = 0;
            this.onItemChange(idx);
            this.$nextTick(() => {
                const qtyEl = document.getElementById('item-qty-' + idx);
                if (qtyEl) {
                    qtyEl.focus();
                    qtyEl.select();
                }
            });
        },

        onQtyEnter(row, idx) {
            this.$nextTick(() => {
                const priceInp = document.getElementById('item-price-' + idx);
                if (priceInp) {
                    priceInp.focus();
                    priceInp.select();
                }
            });
        },

        onPriceEnter(row, idx) {
            if (idx === this.form.items.length - 1) {
                this.addItemRow();
                this.$nextTick(() => {
                    const newIdx = this.form.items.length - 1;
                    const newRow = this.form.items[newIdx];
                    this.openItemDropdown(newRow, newIdx);
                });
            } else {
                this.$nextTick(() => {
                    const nextBtn = document.getElementById('item-btn-' + (idx + 1));
                    if (nextBtn) {
                        nextBtn.focus();
                    }
                });
            }
        },

        moveRowField(idx, dir, field) {
            const targetIdx = idx + dir;
            if (targetIdx >= 0 && targetIdx < this.form.items.length) {
                const targetInp = document.getElementById('item-' + field + '-' + targetIdx);
                if (targetInp) {
                    targetInp.focus();
                    if (typeof targetInp.select === 'function') targetInp.select();
                }
            }
        },

        onItemChange(idx) {
            const row = this.form.items[idx];
            const found = this.availableItems.find(i => String(i.id) === String(row.item_id));
            if (found) {
                row.harga_satuan = window.formatRupiahNumber ? window.formatRupiahNumber(found.harga_pokok_pembelian || 0) : String(found.harga_pokok_pembelian || 0);
                this.recalcRow(idx);
            }
        },

        recalcRow(idx) {
            const row = this.form.items[idx];
            const rawHarga = typeof row.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(row.harga_satuan) : Number(row.harga_satuan.replace(/\./g, ''))) : Number(row.harga_satuan || 0);
            row.subtotal = Number(row.qty || 0) * rawHarga;
        },

        handleFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 15 * 1024 * 1024) {
                toast.warning('Ukuran file foto maksimal 15MB!');
                event.target.value = '';
                return;
            }
            if (!file.type.match(/^image\//i)) {
                toast.warning('Format file harus berupa gambar (JPG, PNG, atau WebP)!');
                event.target.value = '';
                return;
            }

            // Kompresi sisi klien via HTML5 Canvas agar transfer upload cepat dan ringan
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const maxDim = 1600;
                    let w = img.width;
                    let h = img.height;
                    if (w > maxDim || h > maxDim) {
                        if (w >= h) {
                            h = Math.round((h / w) * maxDim);
                            w = maxDim;
                        } else {
                            w = Math.round((w / h) * maxDim);
                            h = maxDim;
                        }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(img, 0, 0, w, h);

                    canvas.toBlob((blob) => {
                        if (blob) {
                            this.photoFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", { type: 'image/jpeg' });
                            this.photoPreview = canvas.toDataURL('image/jpeg', 0.82);
                        } else {
                            this.photoFile = file;
                            this.photoPreview = e.target.result;
                        }
                    }, 'image/jpeg', 0.82);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        clearPhoto() {
            this.photoFile = null;
            this.photoPreview = null;
            if (this.$refs.photoFileInput) {
                this.$refs.photoFileInput.value = '';
            }
        },

        resetZoom() {
            this.zoomScale = 1.0;
            this.zoomPanX = 0;
            this.zoomPanY = 0;
            this.zoomRotate = 0;
            this.isDragging = false;
            this.isPinching = false;
        },

        zoomStep(step) {
            const next = Math.min(5.0, Math.max(0.6, Number((this.zoomScale + step).toFixed(2))));
            this.zoomScale = next;
            if (next <= 1.0) {
                this.zoomPanX = 0;
                this.zoomPanY = 0;
            } else {
                this.clampPan();
            }
        },

        rotateClockwise() {
            this.zoomRotate = (this.zoomRotate + 90) % 360;
        },

        clampPan() {
            if (this.zoomScale <= 1.0) {
                this.zoomPanX = 0;
                this.zoomPanY = 0;
                return;
            }
            const bound = Math.max(100, 480 * (this.zoomScale - 0.7));
            this.zoomPanX = Math.max(-bound, Math.min(bound, this.zoomPanX));
            this.zoomPanY = Math.max(-bound, Math.min(bound, this.zoomPanY));
        },

        onReceiptImageLoaded() {
            this.receiptLoadError = false;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        openReceiptPreview(url, title) {
            if (!url) return;
            this.resetZoom();
            this.receiptLoadError = false;
            this.receiptModalUrl = '<?= Router::url('/') ?>' + url.replace(/^\//, '');
            this.receiptModalTitle = title ? ('No. Faktur: ' + title) : 'Foto Bukti Nota Pembelian';
            this.showReceiptModal = true;
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        closeReceiptPreview() {
            this.showReceiptModal = false;
            this.receiptModalUrl = '';
            this.resetZoom();
            this.receiptLoadError = false;
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
        },

        toggleDoubleTap(clientX, clientY) {
            if (this.receiptLoadError) return;
            if (this.zoomScale > 1.2) {
                this.resetZoom();
            } else {
                this.zoomScale = 2.4;
                this.zoomPanX = 0;
                this.zoomPanY = 0;
            }
        },

        handleMouseDown(e) {
            if (e.button !== 0 || this.receiptLoadError) return;
            this.isDragging = true;
            this.dragStartX = e.clientX - this.zoomPanX;
            this.dragStartY = e.clientY - this.zoomPanY;

            const onMouseMove = (ev) => {
                if (!this.isDragging) return;
                this.zoomPanX = ev.clientX - this.dragStartX;
                this.zoomPanY = ev.clientY - this.dragStartY;
                this.clampPan();
            };

            const onMouseUp = () => {
                this.isDragging = false;
                this.clampPan();
                window.removeEventListener('mousemove', onMouseMove);
                window.removeEventListener('mouseup', onMouseUp);
            };

            window.addEventListener('mousemove', onMouseMove);
            window.addEventListener('mouseup', onMouseUp);
        },

        handleTouchStart(e) {
            if (this.receiptLoadError) return;
            if (e.touches.length === 2) {
                this.isPinching = true;
                this.isDragging = false;
                this.pinchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                this.pinchStartScale = this.zoomScale;
            } else if (e.touches.length === 1) {
                const now = Date.now();
                if (now - this.lastTapTime < 300) {
                    this.toggleDoubleTap(e.touches[0].clientX, e.touches[0].clientY);
                    this.lastTapTime = 0;
                    return;
                }
                this.lastTapTime = now;

                this.isDragging = true;
                this.dragStartX = e.touches[0].clientX - this.zoomPanX;
                this.dragStartY = e.touches[0].clientY - this.zoomPanY;
            }
        },

        handleTouchMove(e) {
            if (this.receiptLoadError) return;
            if (this.isPinching && e.touches.length === 2) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                if (this.pinchStartDist > 0) {
                    const factor = dist / this.pinchStartDist;
                    this.zoomScale = Math.min(5.0, Math.max(0.6, Number((this.pinchStartScale * factor).toFixed(2))));
                }
            } else if (this.isDragging && e.touches.length === 1) {
                this.zoomPanX = e.touches[0].clientX - this.dragStartX;
                this.zoomPanY = e.touches[0].clientY - this.dragStartY;
                this.clampPan();
            }
        },

        handleTouchEnd(e) {
            if (e.touches.length < 2) {
                this.isPinching = false;
            }
            if (e.touches.length === 0) {
                this.isDragging = false;
                this.clampPan();
            }
        },

        handleWheel(e) {
            if (this.receiptLoadError) return;
            const delta = e.deltaY < 0 ? 0.25 : -0.25;
            this.zoomStep(delta);
        },

        handleViewerKeydown(e) {
            if (!this.showReceiptModal || this.receiptLoadError) return;
            if (e.key === '+' || e.key === '=') {
                e.preventDefault();
                this.zoomStep(0.25);
            } else if (e.key === '-' || e.key === '_') {
                e.preventDefault();
                this.zoomStep(-0.25);
            } else if (e.key === 'r' || e.key === 'R' || e.key === '0') {
                e.preventDefault();
                this.resetZoom();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                this.zoomPanX -= 35;
                this.clampPan();
            } else if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this.zoomPanX += 35;
                this.clampPan();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.zoomPanY += 35;
                this.clampPan();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.zoomPanY -= 35;
                this.clampPan();
            }
        },

        async openDetailModal(id) {
            this.isLoadingDetail = true;
            this.activeDetail = null;
            this.activeDetailTab = 'items';
            this.showDetailModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });

            try {
                const res = await fetch('<?= Router::url('/purchases/detail') ?>?id=' + encodeURIComponent(id));
                const json = await res.json();
                if (json.success) {
                    this.activeDetail = json;
                } else {
                    toast.error('Gagal memuat detail faktur: ' + json.message);
                    this.showDetailModal = false;
                }
            } catch (err) {
                toast.error('Kesalahan koneksi saat memuat detail faktur.');
                this.showDetailModal = false;
            } finally {
                this.isLoadingDetail = false;
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        openPayModal(pb) {
            this.activePay = pb;
            this.payForm = {
                pembelian_id: pb.id,
                akun_kas_id: '<?= $cashAccounts[0]['id'] ?? '' ?>',
                tanggal_bayar: '<?= date('Y-m-d') ?>',
                catatan: ''
            };
            this.showPayModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        async submitPayment() {
            if (!this.payForm.akun_kas_id) {
                toast.warning('Mohon pilih akun kas sumber dana!');
                return;
            }

            const selectedKas = this.cashAccounts.find(k => String(k.id) === String(this.payForm.akun_kas_id));
            if (selectedKas && Number(selectedKas.saldo_saat_ini || 0) < Number(this.activePay?.total_biaya || 0)) {
                toast.warning(`Saldo kas ${selectedKas.nama_akun} (${this.formatRupiah(selectedKas.saldo_saat_ini)}) tidak mencukupi untuk melunasi tagihan ${this.formatRupiah(this.activePay?.total_biaya)}.`);
                return;
            }

            this.isSubmitting = true;
            if (window.AppAction) window.AppAction.show('Mencatat pelunasan faktur...');

            try {
                const res = await fetch('<?= Router::url('/purchases/pay') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(this.payForm)
                });
                const json = await res.json();
                if (json.success) {
                    if (window.AppAction) {
                        await window.AppAction.success('Hutang Berhasil Dilunasi!', '', 1200);
                    } else {
                        toast.success(json.message);
                    }
                    this.showPayModal = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    const msg = json.message || 'Gagal melunasi hutang';
                    if (window.AppAction) {
                        await window.AppAction.error('Gagal Melunasi Hutang', msg);
                    } else {
                        toast.error('Gagal: ' + msg);
                    }
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Kesalahan Jaringan!', 'Terjadi kesalahan koneksi saat melunasi faktur.');
                } else {
                    toast.error('Terjadi kesalahan koneksi saat melunasi faktur.');
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        openCancelModal(pb) {
            this.activeCancel = pb;
            this.cancelAlasan = '';
            this.cancelError = '';
            this.showCancelModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
                if (this.$refs.cancelAlasanInput) {
                    this.$refs.cancelAlasanInput.focus();
                }
            });
        },

        async submitCancel() {
            if (!this.cancelAlasan.trim()) {
                this.cancelError = 'Mohon isi alasan pembatalan faktur terlebih dahulu!';
                if (this.$refs.cancelAlasanInput) {
                    this.$refs.cancelAlasanInput.focus();
                }
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
                return;
            }
            this.cancelError = '';

            this.isSubmitting = true;
            if (window.AppAction) window.AppAction.show('Membatalkan faktur pembelian...');

            try {
                const res = await fetch('<?= Router::url('/purchases/cancel') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        pembelian_id: this.activeCancel.id,
                        alasan: this.cancelAlasan.trim()
                    })
                });
                const json = await res.json();
                if (json.success) {
                    if (window.AppAction) {
                        await window.AppAction.success('Faktur Dibatalkan!', '', 1200);
                    } else {
                        toast.success(json.message);
                    }
                    this.showCancelModal = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    const msg = json.message || 'Gagal membatalkan faktur';
                    this.cancelError = msg;
                    if (window.AppAction) {
                        await window.AppAction.error('Gagal Membatalkan Faktur', msg);
                    } else {
                        toast.error('Gagal: ' + msg);
                    }
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Kesalahan Jaringan!', 'Terjadi kesalahan koneksi saat membatalkan faktur.');
                } else {
                    toast.error('Terjadi kesalahan koneksi saat membatalkan faktur.');
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        openEditPoModal(detail) {
            if (!detail || !detail.purchase) return;
            const pb = detail.purchase;
            this.editPoForm = {
                id: pb.id,
                nomor_faktur: pb.nomor_faktur_pembelian,
                pemasok_id: pb.pemasok_id,
                tanggal_pembelian: pb.tanggal_pembelian,
                metode_logistik: pb.metode_logistik || 'diantar_supplier',
                sales_driver_id: pb.sales_driver_id || '',
                tanggal_jadwal_belanja: pb.tanggal_jadwal_belanja || '<?= date('Y-m-d') ?>',
                instruksi_driver: pb.instruksi_driver || '',
                metode_bayar_belanja: pb.metode_bayar_belanja || 'tempo_vendor',
                status_pembayaran: pb.status_pembayaran || 'belum_lunas',
                catatan: pb.catatan || '',
                items: (detail.items || []).map(it => ({
                    item_id: it.item_id,
                    qty: Number(it.kuantitas || 1),
                    harga_satuan: window.formatRupiahNumber ? window.formatRupiahNumber(it.harga_satuan) : String(it.harga_satuan || 0),
                    subtotal: Number(it.subtotal || 0)
                }))
            };
            this.showDetailModal = false;
            this.showEditPoModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        addEditPoItemRow() {
            this.editPoForm.items.push({
                item_id: '',
                qty: 1,
                harga_satuan: '0',
                subtotal: 0
            });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        removeEditPoItemRow(idx) {
            this.editPoForm.items.splice(idx, 1);
            if (this.editPoForm.items.length === 0) {
                this.addEditPoItemRow();
            }
        },

        recalcEditPoRow(idx) {
            const row = this.editPoForm.items[idx];
            const rawHarga = typeof row.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(row.harga_satuan) : Number(row.harga_satuan.replace(/\./g, ''))) : Number(row.harga_satuan || 0);
            row.subtotal = Number(row.qty || 0) * rawHarga;
        },

        async submitEditPo() {
            if (!this.editPoForm.pemasok_id) {
                toast.warning('Mohon pilih vendor pemasok!');
                return;
            }
            if (this.editPoForm.metode_logistik === 'diambil_driver' && !this.editPoForm.sales_driver_id) {
                toast.warning('Mohon pilih driver penanggung jawab!');
                return;
            }
            const validItems = (this.editPoForm.items || []).filter(it => it.item_id && Number(it.qty) > 0);
            if (validItems.length === 0) {
                toast.warning('Mohon isi minimal 1 item pesanan!');
                return;
            }

            this.isSubmitting = true;
            if (window.AppAction) window.AppAction.show('Menyimpan perubahan PO...');

            try {
                const preparedItems = validItems.map(it => ({
                    item_id: it.item_id,
                    qty: Number(it.qty || 0),
                    harga_satuan: typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0),
                    subtotal: Number(it.qty || 0) * (typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0))
                }));

                const res = await fetch('<?= Router::url('/purchases/update-po') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id: this.editPoForm.id,
                        pemasok_id: this.editPoForm.pemasok_id,
                        tanggal_pembelian: this.editPoForm.tanggal_pembelian,
                        metode_logistik: this.editPoForm.metode_logistik,
                        sales_driver_id: this.editPoForm.sales_driver_id,
                        tanggal_jadwal_belanja: this.editPoForm.tanggal_jadwal_belanja,
                        instruksi_driver: this.editPoForm.instruksi_driver,
                        metode_bayar_belanja: this.editPoForm.metode_bayar_belanja,
                        status_pembayaran: this.editPoForm.status_pembayaran || 'belum_lunas',
                        catatan: this.editPoForm.catatan,
                        items: preparedItems
                    })
                });
                const json = await res.json();
                if (json.success) {
                    if (window.AppAction) {
                        await window.AppAction.success('PO Berhasil Diperbarui!', '', 1200);
                    } else {
                        toast.success(json.message);
                    }
                    this.showEditPoModal = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    const msg = json.message || 'Gagal memperbarui PO';
                    if (window.AppAction) {
                        await window.AppAction.error('Gagal Mengubah PO', msg);
                    } else {
                        toast.error('Gagal: ' + msg);
                    }
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Kesalahan Jaringan!', 'Terjadi kesalahan koneksi saat memperbarui PO.');
                } else {
                    toast.error('Terjadi kesalahan koneksi saat memperbarui PO.');
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        async openQuickReceive(id) {
            await this.openDetailModal(id);
            if (this.activeDetail && this.activeDetail.purchase) {
                if (this.activeDetail.purchase.status_penerimaan === 'kendala_batal') {
                    toast.warning('PO ini sedang berstatus kendala. Silakan jadwalkan ulang atau ganti driver terlebih dahulu.');
                    return;
                }
                this.openReceiveModal(this.activeDetail);
            }
        },

        openReceiveModal(detail) {
            if (!detail || !detail.purchase) return;
            const pb = detail.purchase;

            // Logika Auto-Detect Status Pembayaran Cerdas:
            let defaultStatusBayar = (pb.status_pembayaran === 'lunas') ? 'lunas' : 'belum_lunas';
            if (pb.jenis_dokumen === 'po' && pb.metode_logistik === 'diambil_driver') {
                defaultStatusBayar = (pb.metode_bayar_belanja === 'tunai_driver') ? 'lunas' : 'belum_lunas';
            }

            this.receiveForm = {
                id: pb.id,
                nomor_faktur_pembelian: pb.nomor_faktur_pembelian,
                nama_pemasok: pb.nama_pemasok,
                total_biaya_estimasi: pb.total_biaya,
                metode_logistik: pb.metode_logistik || 'diantar_supplier',
                metode_bayar_belanja: pb.metode_bayar_belanja,
                nama_driver: pb.nama_driver || '',
                nominal_dibayar_driver: Number(pb.nominal_dibayar_driver || 0),
                driver_nota_photo: pb.url_foto_nota || '',
                nomor_nota_vendor: pb.nomor_nota_vendor || '',
                status_pembayaran: defaultStatusBayar,
                akun_kas_id: '<?= $cashAccounts[0]['id'] ?? '' ?>',
                catatan: '',
                items: (detail.items || []).map(it => ({
                    item_id: it.item_id,
                    nama_item: it.nama_item,
                    kode_sku: it.kode_sku,
                    satuan: it.satuan_dasar,
                    po_qty: Number(it.kuantitas || 0),
                    qty: Number(it.kuantitas || 0),
                    harga_satuan: window.formatRupiahNumber ? window.formatRupiahNumber(it.harga_satuan) : String(it.harga_satuan || 0),
                    subtotal: Number(it.subtotal || 0)
                }))
            };
            this.clearReceivePhoto();
            this.showDetailModal = false;
            this.showReceiveModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        recalcReceiveRow(idx) {
            const row = this.receiveForm.items[idx];
            const rawHarga = typeof row.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(row.harga_satuan) : Number(row.harga_satuan.replace(/\./g, ''))) : Number(row.harga_satuan || 0);
            row.subtotal = Number(row.qty || 0) * rawHarga;
        },

        handleReceiveFileChange(event) {
            const file = event.target.files[0];
            if (!file) return;
            if (file.size > 15 * 1024 * 1024) {
                toast.warning('Ukuran file foto maksimal 15MB!');
                event.target.value = '';
                return;
            }
            if (!file.type.match(/^image\//i)) {
                toast.warning('Format file harus berupa gambar (JPG, PNG, atau WebP)!');
                event.target.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    const maxDim = 1600;
                    let w = img.width;
                    let h = img.height;
                    if (w > maxDim || h > maxDim) {
                        if (w >= h) {
                            h = Math.round((h / w) * maxDim);
                            w = maxDim;
                        } else {
                            w = Math.round((w / h) * maxDim);
                            h = maxDim;
                        }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, w, h);
                    ctx.drawImage(img, 0, 0, w, h);

                    canvas.toBlob((blob) => {
                        if (blob) {
                            this.receivePhotoFile = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", { type: 'image/jpeg' });
                            this.receivePhotoPreview = canvas.toDataURL('image/jpeg', 0.82);
                        } else {
                            this.receivePhotoFile = file;
                            this.receivePhotoPreview = e.target.result;
                        }
                    }, 'image/jpeg', 0.82);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        clearReceivePhoto() {
            this.receivePhotoFile = null;
            this.receivePhotoPreview = null;
            if (this.$refs.receivePhotoInput) {
                this.$refs.receivePhotoInput.value = '';
            }
        },

        async submitReceiveGoods() {
            if (!this.receiveForm.nomor_nota_vendor.trim()) {
                toast.warning('Mohon isi nomor nota fisik dari vendor / bukti jalan!');
                return;
            }
            const validItems = (this.receiveForm.items || []).filter(it => it.item_id && Number(it.qty) > 0);
            if (validItems.length === 0) {
                toast.warning('Mohon verifikasi minimal 1 item dengan kuantiti > 0!');
                return;
            }
            if (this.receiveForm.status_pembayaran === 'lunas') {
                if (!this.receiveForm.akun_kas_id) {
                    toast.warning('Mohon pilih akun kas sumber dana untuk pembayaran lunas!');
                    return;
                }
                const selectedKas = this.cashAccounts.find(k => String(k.id) === String(this.receiveForm.akun_kas_id));
                if (selectedKas && Number(selectedKas.saldo_saat_ini || 0) < this.receiveTotal) {
                    toast.warning(`Saldo kas ${selectedKas.nama_akun} (${this.formatRupiah(selectedKas.saldo_saat_ini)}) tidak mencukupi untuk total faktur ${this.formatRupiah(this.receiveTotal)}.`);
                    return;
                }
            }

            this.isSubmitting = true;
            if (window.AppAction) window.AppAction.show('Memproses penerimaan barang ke gudang...');

            try {
                const formData = new FormData();
                formData.append('id', this.receiveForm.id);
                formData.append('nomor_nota_vendor', this.receiveForm.nomor_nota_vendor.trim());
                formData.append('status_pembayaran', this.receiveForm.status_pembayaran);
                formData.append('akun_kas_id', this.receiveForm.status_pembayaran === 'lunas' ? (this.receiveForm.akun_kas_id || '') : '');
                formData.append('catatan', this.receiveForm.catatan || '');

                const preparedItems = validItems.map(it => ({
                    item_id: it.item_id,
                    qty: Number(it.qty || 0),
                    harga_satuan: typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0),
                    subtotal: Number(it.qty || 0) * (typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0))
                }));
                formData.append('items', JSON.stringify(preparedItems));

                if (this.receivePhotoFile) {
                    formData.append('foto_nota', this.receivePhotoFile);
                }

                const res = await fetch('<?= Router::url('/purchases/receive') ?>', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    if (window.AppAction) {
                        await window.AppAction.success('Barang Berhasil Diterima di Gudang!', '', 1400);
                    } else {
                        toast.success('Penerimaan berhasil diverifikasi dan stok telah ditambahkan!');
                    }
                    this.showReceiveModal = false;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    const msg = json.message || 'Gagal memproses penerimaan barang';
                    if (window.AppAction) {
                        await window.AppAction.error('Gagal Penerimaan Barang', msg);
                    } else {
                        toast.error('Gagal: ' + msg);
                    }
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Kesalahan Jaringan!', 'Terjadi kesalahan koneksi saat menerima barang.');
                } else {
                    toast.error('Terjadi kesalahan koneksi saat menerima barang.');
                }
            } finally {
                this.isSubmitting = false;
            }
        },

        async submitPurchase() {
            if (!this.form.pemasok_id) {
                toast.warning('Mohon pilih vendor pemasok terlebih dahulu!');
                return;
            }

            if (this.form.jenis_dokumen === 'po' && this.form.metode_logistik === 'diambil_driver' && !this.form.sales_driver_id) {
                toast.warning('Mohon pilih driver yang ditugaskan untuk mengambil belanjaan PO ini!');
                return;
            }

            const validItems = this.form.items.filter(it => it.item_id && Number(it.qty) > 0);
            if (validItems.length === 0) {
                toast.warning('Mohon tambahkan minimal 1 item barang yang dipesan dengan kuantitas > 0!');
                return;
            }

            if (this.form.jenis_dokumen === 'faktur' && this.form.status_pembayaran === 'lunas') {
                if (!this.form.akun_kas_id) {
                    toast.warning('Mohon pilih akun kas sumber dana untuk pembayaran lunas!');
                    return;
                }
                const selectedKas = this.cashAccounts.find(k => String(k.id) === String(this.form.akun_kas_id));
                if (selectedKas && Number(selectedKas.saldo_saat_ini || 0) < this.formTotal) {
                    toast.warning(`Saldo kas ${selectedKas.nama_akun} (${this.formatRupiah(selectedKas.saldo_saat_ini)}) tidak mencukupi untuk total faktur ${this.formatRupiah(this.formTotal)}. Silakan pilih akun kas lain atau pilih opsi pembayaran Tempo (Hutang).`);
                    return;
                }
            }

            this.isSubmitting = true;
            if (window.AppAction) {
                window.AppAction.show(this.form.jenis_dokumen === 'po' ? 'Membuat PO Pembelian...' : 'Menyimpan faktur pembelian...');
            }

            try {
                const formData = new FormData();
                formData.append('jenis_dokumen', this.form.jenis_dokumen || 'faktur');
                formData.append('pemasok_id', this.form.pemasok_id);
                formData.append('nomor_faktur', 'PB-' + (this.form.nomor_faktur_suffix || '').trim());
                formData.append('tanggal_pembelian', this.form.tanggal_pembelian);
                formData.append('status_pembayaran', this.form.status_pembayaran || 'belum_lunas');
                formData.append('akun_kas_id', (this.form.jenis_dokumen === 'faktur' && this.form.status_pembayaran === 'lunas') ? (this.form.akun_kas_id || '') : '');
                formData.append('metode_logistik', this.form.metode_logistik || 'diantar_supplier');
                formData.append('sales_driver_id', this.form.sales_driver_id || '');
                formData.append('tanggal_jadwal_belanja', this.form.tanggal_jadwal_belanja || '');
                formData.append('instruksi_driver', this.form.instruksi_driver || '');
                formData.append('metode_bayar_belanja', this.form.metode_bayar_belanja || '');
                formData.append('catatan', this.form.catatan || '');

                const preparedItems = validItems.map(it => ({
                    item_id: it.item_id,
                    qty: Number(it.qty || 0),
                    harga_satuan: typeof it.harga_satuan === 'string' ? (window.unformatRupiah ? window.unformatRupiah(it.harga_satuan) : Number(it.harga_satuan.replace(/\./g, ''))) : Number(it.harga_satuan || 0),
                    subtotal: Number(it.subtotal || 0)
                }));
                formData.append('items', JSON.stringify(preparedItems));

                if (this.photoFile) {
                    formData.append('foto_nota', this.photoFile);
                }

                const res = await fetch('<?= Router::url('/purchases/store') ?>', {
                    method: 'POST',
                    body: formData
                });
                const json = await res.json();
                if (json.success) {
                    if (window.AppAction) {
                        const title = this.form.jenis_dokumen === 'po' ? 'PO Pembelian Berhasil Dibuat!' : 'Faktur Berhasil Disimpan!';
                        await window.AppAction.success(title, '', 1200);
                    } else {
                        toast.success(json.message);
                    }
                    try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (e) {}
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    const msg = json.message || 'Gagal menyimpan transaksi pembelian';
                    if (window.AppAction) {
                        await window.AppAction.error('Gagal Menyimpan', msg);
                    } else {
                        toast.error('Gagal: ' + msg);
                    }
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Kesalahan Jaringan / Koneksi!', 'Terjadi kesalahan koneksi saat menyimpan pembelian.');
                } else {
                    toast.error('Terjadi kesalahan koneksi saat menyimpan pembelian.');
                }
            } finally {
                this.isSubmitting = false;
            }
        }
    }
}
</script>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/layouts/master.php';
?>


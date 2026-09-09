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
        <div class="page-header-actions">
            <button @click="openAddModal()" class="btn btn-primary" style="font-weight:700;">
                <i data-lucide="plus"></i>
                <span>Catat Faktur Pembelian</span>
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
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Faktur aktif (non-batal)</div>
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
                <div style="font-size:11px;color:var(--color-ink-mute-2);margin-top:2px;">Akumulasi faktur aktif</div>
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
                    <input type="text" x-model="searchQuery" placeholder="Cari nomor faktur / vendor..." class="form-input" style="height:38px;font-size:13px;">
                </div>

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
                <strong style="color:var(--color-ink);" x-text="filteredPurchases.length + ' Faktur'"></strong>
            </div>
        </div>

        <!-- TABLE LIST -->
        <div class="overflow-x-auto custom-scrollbar">
            <table class="data-table" style="min-width: 960px;">
                <thead>
                    <tr>
                        <th style="width:160px; min-width:140px;" class="cell-nowrap">No. Faktur</th>
                        <th style="width:110px; min-width:95px;" class="cell-nowrap">Tanggal</th>
                        <th style="min-width:190px;">Vendor Pemasok</th>
                        <th class="cell-right cell-nowrap" style="width:140px; min-width:120px;">Total Biaya</th>
                        <th class="cell-center cell-nowrap" style="width:120px; min-width:110px;">Status Bayar</th>
                        <th class="cell-center cell-nowrap" style="width:110px; min-width:100px;">Penerimaan</th>
                        <th class="cell-center cell-nowrap" style="width:80px;">Nota</th>
                        <th class="cell-center cell-nowrap" style="width:120px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="pb in filteredPurchases" :key="pb.id">
                        <tr>
                            <td class="cell-nowrap">
                                <button @click="openDetailModal(pb.id)" class="badge badge-mono cursor-pointer" style="font-weight:700;letter-spacing:0.3px;" title="Klik untuk lihat rincian faktur">
                                    <span x-text="pb.nomor_faktur_pembelian"></span>
                                </button>
                                <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:2px;" x-text="(pb.total_items || 0) + ' jenis item'"></div>
                            </td>
                            <td class="cell-nowrap" style="font-family:var(--font-mono);font-size:12px;" x-text="pb.tanggal_pembelian"></td>
                            <td>
                                <div style="font-weight:700;color:var(--color-ink);" x-text="pb.nama_pemasok || '-'"></div>
                                <div style="font-size:11px;color:var(--color-ink-mute);display:flex;gap:6px;align-items:center;">
                                    <span x-text="pb.kode_pemasok || ''"></span>
                                    <template x-if="pb.supplier_telepon">
                                        <span x-text="'• ' + pb.supplier_telepon"></span>
                                    </template>
                                </div>
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
                                <span class="badge badge-info" style="text-transform:capitalize;" x-text="pb.status_penerimaan"></span>
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
                                <div style="display:flex;align-items:center;justify-content:center;gap:4px;">
                                    <!-- Tombol Detail -->
                                    <button @click="openDetailModal(pb.id)" class="btn btn-ghost btn-sm" style="padding:5px;" title="Detail Rincian Faktur">
                                        <i data-lucide="eye" style="width:15px;height:15px;color:var(--color-primary);"></i>
                                    </button>

                                    <!-- Tombol Bayar Hutang (khusus belum lunas) -->
                                    <template x-if="pb.status_pembayaran === 'belum_lunas'">
                                        <button @click="openPayModal(pb)" class="btn btn-ghost btn-sm" style="padding:5px;" title="Catat Pelunasan Hutang">
                                            <i data-lucide="credit-card" style="width:15px;height:15px;color:#f59e0b;"></i>
                                        </button>
                                    </template>

                                    <!-- Tombol Batal (khusus selain batal) -->
                                    <template x-if="pb.status_pembayaran !== 'batal'">
                                        <button @click="openCancelModal(pb)" class="btn btn-ghost btn-sm" style="padding:5px;" title="Batalkan Faktur">
                                            <i data-lucide="ban" style="width:15px;height:15px;color:var(--color-danger);"></i>
                                        </button>
                                    </template>
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
    <div x-show="showModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:740px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div class="modal-title">Catat Faktur Pembelian Bahan Vendor</div>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:2px;">Penerimaan stok bahan baku mentah &amp; kemasan dari supplier</div>
                </div>
                <button @click="showModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <div style="display:flex;flex-direction:column;gap:16px;">

                <!-- LANGKAH 1: VENDOR & DATA DOKUMEN -->
                <div style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);padding:14px;">
                    <div style="display:flex;align-items:center;gap:6px;margin-bottom:12px;font-size:12px;font-weight:700;color:var(--color-ink);">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#3b82f6;color:#fff;font-size:11px;">1</span>
                        <span>Informasi Pemasok &amp; Dokumen Faktur</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Vendor Pemasok *</label>
                            <select x-model="form.pemasok_id" @change="onSupplierChange()" class="form-input" style="font-weight:600;">
                                <option value="">-- Pilih Vendor Pemasok --</option>
                                <?php foreach ($suppliers as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['nama_pemasok']) ?> (<?= htmlspecialchars($s['kode_pemasok']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Nomor Faktur / Nota Pembelian (Otomatis)</label>
                            <div class="input-group-addon">
                                <span class="addon-prefix" style="background:var(--color-canvas-soft);color:var(--color-ink-secondary);font-weight:700;">PB-</span>
                                <input type="text" x-model="form.nomor_faktur_suffix" readonly
                                       class="form-input font-mono uppercase addon-input" 
                                       style="background:var(--color-canvas-soft);cursor:not-allowed;color:var(--color-ink);font-weight:700;" 
                                       placeholder="<?= $suggestedPbSuffix ?>">
                            </div>
                            <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:3px;display:flex;align-items:center;gap:4px;">
                                <i data-lucide="lock" style="width:12px;height:12px;color:var(--color-primary);"></i>
                                <span>Nomor faktur di-generate otomatis oleh sistem (Read-only).</span>
                            </div>
                        </div>
                    </div>

                    <!-- KARTU DETAIL VENDOR TERPILIH -->
                    <template x-if="selectedSupplier">
                        <div style="margin-top:10px;padding:12px;background:var(--color-canvas);border:1px solid var(--color-hairline);border-radius:8px;font-size:11.5px;display:flex;flex-direction:column;gap:8px;color:var(--color-ink-secondary);">
                            <!-- Baris 1: Kontak & Rekening Bank -->
                            <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
                                <div style="display:flex;align-items:center;gap:4px;">
                                    <i data-lucide="phone" style="width:13px;height:13px;color:var(--color-primary);"></i>
                                    <span x-text="selectedSupplier.nomor_telepon || 'Tanpa No. Telp'"></span>
                                </div>
                                <template x-if="selectedSupplier.nama_bank && selectedSupplier.nomor_rekening">
                                    <div style="display:flex;align-items:center;gap:4px;">
                                        <i data-lucide="credit-card" style="width:13px;height:13px;color:#3b82f6;"></i>
                                        <span x-text="selectedSupplier.nama_bank + ': ' + selectedSupplier.nomor_rekening + ' (a.n ' + (selectedSupplier.atas_nama_rekening || '-') + ')'"></span>
                                    </div>
                                </template>
                                <template x-if="selectedSupplier.alamat_lengkap && selectedSupplier.alamat_lengkap !== '-'">
                                    <div style="display:flex;align-items:center;gap:4px;">
                                        <i data-lucide="map-pin" style="width:13px;height:13px;color:#f59e0b;"></i>
                                        <span class="truncate" style="max-width:320px;" x-text="selectedSupplier.alamat_lengkap"></span>
                                    </div>
                                </template>
                            </div>

                            <!-- Baris 2: Ringkasan Jumlah Item yang Dijual Pemasok -->
                            <div style="padding-top:8px;border-top:1px dashed var(--color-hairline);display:flex;align-items:center;gap:8px;font-size:11.5px;">
                                <div style="display:flex;align-items:center;gap:4px;font-weight:600;color:var(--color-ink);">
                                    <i data-lucide="package" style="width:13px;height:13px;color:var(--color-primary);"></i>
                                    <span>Jumlah Item Dijual:</span>
                                </div>
                                
                                <template x-if="supplierItems.length > 0">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span class="badge badge-primary" style="font-size:11px;font-weight:600;" x-text="supplierItems.length + ' Item'"></span>
                                        <span style="color:var(--color-ink-mute);font-size:11px;">(bahan baku / kemasan terdaftar)</span>
                                    </div>
                                </template>

                                <template x-if="supplierItems.length === 0">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <span class="badge badge-warning" style="font-size:10.5px;">0 Item</span>
                                        <span style="color:var(--color-ink-mute);font-size:11px;">(Gunakan centang <em>"Semua Bahan (Pemasok Alternatif)"</em> di langkah 2 jika diperlukan)</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

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
                            <input type="file" x-ref="photoFileInput" @change="handleFileChange($event)" accept="image/*" capture="environment" class="form-input" style="padding:6px 10px;font-size:12px;flex:1;">
                            <template x-if="photoPreview">
                                <div style="display:flex;align-items:center;gap:6px;">
                                    <img :src="photoPreview" style="width:36px;height:36px;object-fit:cover;border-radius:6px;border:1px solid var(--color-hairline);" alt="Preview">
                                    <button type="button" @click="clearPhoto()" class="btn btn-ghost btn-sm" style="color:var(--color-danger);padding:4px;">
                                        <i data-lucide="trash-2" style="width:14px;height:14px;"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- LANGKAH 2: RINCIAN BARANG DITERIMA -->
                <div style="padding:14px;background:var(--color-canvas-soft);border:1px solid var(--color-hairline);border-radius:var(--rounded-md);">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <div style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:var(--color-ink);">
                            <span style="display:inline-flex;align-items:center;justify-content:center;width:20px;height:20px;border-radius:50%;background:#3b82f6;color:#fff;font-size:11px;">2</span>
                            <span>Daftar Bahan Baku / Kemasan Masuk</span>
                        </div>

                        <div style="display:flex;align-items:center;gap:12px;">
                            <!-- Toggle Tampilkan Semua Bahan (Supplier Alternatif) -->
                            <label x-show="form.pemasok_id" class="flex items-center gap-2 cursor-pointer" style="font-size:11.5px;color:var(--color-ink-secondary);user-select:none;">
                                <input type="checkbox" x-model="showAllMaterials" class="form-checkbox" style="width:14px;height:14px;border-radius:4px;">
                                <span>Semua Bahan (Pemasok Alternatif)</span>
                            </label>

                            <button x-show="form.pemasok_id" @click="addItemRow()" type="button" class="btn btn-secondary btn-sm" style="padding:4px 10px;font-size:11.5px;">
                                <i data-lucide="plus" style="width:13px;height:13px;"></i>
                                <span>Tambah Baris</span>
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
                            <!-- Column Header Labels -->
                            <div style="display:grid;grid-template-columns:2.5fr 1fr 1.3fr 36px;gap:8px;padding-bottom:6px;font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">
                                <div>Nama Bahan Baku / SKU</div>
                                <div>Kuantitas</div>
                                <div>Harga Satuan (Rp)</div>
                                <div></div>
                            </div>

                            <div style="display:flex;flex-direction:column;gap:8px;min-height:160px;overflow:visible;">
                                <template x-for="(row, idx) in form.items" :key="idx">
                                    <div style="display:grid;grid-template-columns:2.5fr 1fr 1.3fr 36px;gap:8px;align-items:center;">
                                        <!-- Searchable Item Dropdown -->
                                        <div class="relative" @click.outside="row.dropdownOpen = false">
                                            <button type="button" @click="toggleItemDropdown(row)"
                                                    class="form-input flex items-center justify-between w-full text-left"
                                                    style="height:36px;font-size:12px;font-weight:600;border-radius:8px;cursor:pointer;background:var(--color-canvas);padding:0 8px;">
                                                <span class="truncate" :style="!row.item_id ? 'color:var(--color-ink-mute);font-weight:500;' : 'color:var(--color-ink);'"
                                                      x-text="getSelectedItemName(row.item_id)"></span>
                                                <i data-lucide="chevron-down" style="width:13px;height:13px;flex-shrink:0;transition:transform 0.2s;" :style="row.dropdownOpen ? 'transform:rotate(180deg)' : ''"></i>
                                            </button>

                                            <div x-show="row.dropdownOpen" x-cloak
                                                 class="dropdown-menu-searchable"
                                                 style="position:absolute;top:calc(100% + 4px);left:0;min-width:300px;max-width:380px;z-index:1050;border-radius:10px;overflow:hidden;background:var(--color-canvas);border:1px solid var(--color-hairline);box-shadow:0 12px 28px -4px rgba(0,0,0,0.15);">
                                                <div style="padding:6px 8px;border-bottom:1px solid var(--color-hairline);background:var(--color-canvas-soft);">
                                                    <div style="position:relative;display:flex;align-items:center;">
                                                        <i data-lucide="search" style="position:absolute;left:8px;width:13px;height:13px;color:var(--color-ink-mute);pointer-events:none;"></i>
                                                        <input type="text" x-model="row.search"
                                                               @keydown.escape="row.dropdownOpen = false"
                                                               placeholder="Cari nama barang / SKU..."
                                                               class="form-input"
                                                               style="height:30px;padding-left:26px;font-size:11.5px;border-radius:6px;width:100%;background:var(--color-canvas);">
                                                    </div>
                                                </div>
                                                <div style="max-height:180px;overflow-y:auto;" class="custom-scrollbar">
                                                    <template x-for="it in getFilteredItems(row)" :key="it.id">
                                                        <div @click="selectItemRow(row, idx, it)"
                                                             class="searchable-option"
                                                             :class="{ 'is-selected': String(it.id) === String(row.item_id) }"
                                                             style="padding:8px 10px;font-size:11.5px;cursor:pointer;display:flex;align-items:center;justify-content:space-between;gap:6px;border-bottom:1px solid var(--color-hairline-soft);">
                                                            <div style="min-width:0;flex:1;">
                                                                <div style="font-weight:700;color:var(--color-ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="it.nama_item"></div>
                                                                <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="(it.tipe_item === 'bahan_mentah' ? 'Bahan Mentah' : 'Bahan Kemas') + ' • Satuan: ' + it.satuan_dasar"></div>
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

                                        <!-- Qty with Unit Badge -->
                                        <div class="relative flex items-center">
                                            <input type="number" step="1" min="1" x-model.number="row.qty" placeholder="Qty" class="form-input font-mono" style="font-size:12px;height:36px;padding-right:38px;" @input="recalcRow(idx)">
                                            <span style="position:absolute;right:8px;font-size:10.5px;font-weight:700;color:var(--color-ink-mute);pointer-events:none;" x-text="getSelectedItemUnit(row.item_id)"></span>
                                        </div>

                                        <!-- Harga Satuan -->
                                        <input type="text" x-model="row.harga_satuan" placeholder="Harga" class="form-input font-mono input-rupiah" style="font-size:12px;height:36px;" @input="recalcRow(idx)">

                                        <!-- Delete Row -->
                                        <button @click="removeItemRow(idx)" type="button" class="btn btn-ghost btn-sm" style="color:var(--color-danger);padding:4px;height:36px;width:36px;" title="Hapus Baris">
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
                        </div>
                    </template>
                </div>

                <div>
                    <label class="form-label">Catatan / Keterangan Pengiriman</label>
                    <input type="text" x-model="form.catatan" class="form-input" placeholder="Contoh: Pengiriman via armada vendor, barang diterima dalam kondisi baik...">
                </div>

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:6px;">
                    <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                    <button type="button" @click="submitPurchase()" :disabled="isSubmitting" class="btn btn-primary" style="font-weight:700;">
                        <i data-lucide="save"></i>
                        <span x-show="!isSubmitting">Simpan Faktur &amp; Tambah Stok</span>
                        <span x-show="isSubmitting">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    </template>

    <!-- ========================================================================= -->
    <!-- MODAL DETAIL FAKTUR PEMBELIAN                                             -->
    <!-- ========================================================================= -->
    <template x-teleport="body">
    <div x-show="showDetailModal" x-cloak class="modal-backdrop">
        <div class="modal-box" style="max-width:680px;padding:24px;">
            <div class="modal-header">
                <div>
                    <div style="display:flex;align-items:center;gap:8px;">
                        <span class="badge badge-mono" style="font-size:13px;font-weight:700;" x-text="activeDetail?.purchase?.nomor_faktur_pembelian"></span>
                        <span class="badge" 
                              :class="{
                                  'badge-primary': activeDetail?.purchase?.status_pembayaran === 'lunas',
                                  'badge-warning': activeDetail?.purchase?.status_pembayaran === 'belum_lunas',
                                  'badge-danger': activeDetail?.purchase?.status_pembayaran === 'batal'
                              }" 
                              style="text-transform:capitalize;" 
                              x-text="activeDetail?.purchase?.status_pembayaran === 'belum_lunas' ? 'Tempo (Hutang)' : (activeDetail?.purchase?.status_pembayaran === 'batal' ? 'Dibatalkan' : 'Lunas')">
                        </span>
                    </div>
                    <div style="font-size:11.5px;color:var(--color-ink-mute);margin-top:3px;">
                        Tanggal Faktur: <strong class="font-mono" x-text="activeDetail?.purchase?.tanggal_pembelian"></strong>
                    </div>
                </div>
                <button @click="showDetailModal = false" class="btn btn-ghost btn-sm" style="padding:4px;">
                    <i data-lucide="x" style="width:16px;height:16px;"></i>
                </button>
            </div>

            <template x-if="isLoadingDetail">
                <div style="padding:40px;text-align:center;color:var(--color-ink-mute);">
                    <i data-lucide="loader-2" class="animate-spin" style="width:32px;height:32px;margin:0 auto 8px auto;"></i>
                    <div>Memuat rincian faktur...</div>
                </div>
            </template>

            <template x-if="!isLoadingDetail && activeDetail">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    <!-- Profil Vendor & Pembayaran -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" style="background:var(--color-canvas-soft);padding:12px;border-radius:8px;font-size:12px;">
                        <div>
                            <div style="font-size:10.5px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Vendor Pemasok</div>
                            <div style="font-weight:700;font-size:13px;color:var(--color-ink);" x-text="activeDetail.purchase.nama_pemasok"></div>
                            <div style="color:var(--color-ink-secondary);font-size:11px;" x-text="activeDetail.purchase.kode_pemasok + ' • ' + (activeDetail.purchase.nomor_telepon || 'Tanpa Telp')"></div>
                            <template x-if="activeDetail.purchase.nama_bank">
                                <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;" x-text="activeDetail.purchase.nama_bank + ': ' + activeDetail.purchase.nomor_rekening + ' (a.n ' + activeDetail.purchase.atas_nama_rekening + ')'"></div>
                            </template>
                        </div>
                        <div>
                            <div style="font-size:10.5px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Informasi Kas &amp; Penerima</div>
                            <div style="color:var(--color-ink);">Dibuat oleh: <strong x-text="activeDetail.purchase.pembuat || 'Sistem'"></strong></div>
                            <template x-if="activeDetail.purchase.status_pembayaran === 'lunas'">
                                <div style="color:var(--color-primary);font-size:11.5px;margin-top:2px;">
                                    Sumber Kas: <strong x-text="activeDetail.purchase.akun_kas_nama || 'Kas/Bank'"></strong>
                                </div>
                            </template>
                            <div style="font-size:11px;color:var(--color-ink-mute);margin-top:2px;">
                                Status Penerimaan: <strong class="badge badge-info" style="font-size:10px;" x-text="activeDetail.purchase.status_penerimaan"></strong>
                            </div>
                        </div>
                    </div>

                    <!-- Tabel Item Rincian -->
                    <div style="border:1px solid var(--color-hairline);border-radius:8px;overflow:hidden;">
                        <table class="data-table" style="width:100%;font-size:12px;">
                            <thead>
                                <tr>
                                    <th>Nama Bahan / SKU</th>
                                    <th class="cell-center" style="width:90px;">Qty</th>
                                    <th class="cell-right" style="width:130px;">Harga Satuan</th>
                                    <th class="cell-right" style="width:140px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="item in activeDetail.items" :key="item.id">
                                    <tr>
                                        <td>
                                            <div style="font-weight:700;color:var(--color-ink);" x-text="item.nama_item"></div>
                                            <div style="font-size:10.5px;color:var(--color-ink-mute);" x-text="item.kode_sku + ' • ' + (item.tipe_item === 'bahan_mentah' ? 'Mentah' : 'Kemasan')"></div>
                                        </td>
                                        <td class="cell-center font-mono">
                                            <span style="font-weight:700;" x-text="item.kuantitas"></span>
                                            <span style="font-size:10.5px;color:var(--color-ink-mute);" x-text="item.satuan"></span>
                                        </td>
                                        <td class="cell-right font-mono" x-text="formatRupiah(item.harga_satuan)"></td>
                                        <td class="cell-right font-mono" style="font-weight:700;color:var(--color-primary-deep);" x-text="formatRupiah(item.subtotal)"></td>
                                    </tr>
                                </template>
                            </tbody>
                            <tfoot>
                                <tr style="background:var(--color-canvas-soft);font-weight:700;">
                                    <td colspan="3" class="cell-right">Total Belanja Bahan:</td>
                                    <td class="cell-right font-mono" style="font-size:14px;color:var(--color-primary-deep);" x-text="formatRupiah(activeDetail.purchase.total_biaya)"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Bukti Nota Fisik -->
                    <template x-if="activeDetail.purchase.url_foto_nota">
                        <div style="border:1px solid var(--color-hairline);border-radius:8px;padding:10px;background:var(--color-canvas-soft);">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                                <div style="font-size:11px;font-weight:700;color:var(--color-ink-mute);text-transform:uppercase;">Foto Bukti Nota Fisik Vendor</div>
                                <button type="button" @click="openReceiptPreview(activeDetail.purchase.url_foto_nota, activeDetail.purchase.nomor_faktur_pembelian)" class="btn btn-ghost btn-sm" style="font-size:11px;padding:2px 8px;color:var(--color-primary);display:inline-flex;align-items:center;gap:4px;">
                                    <i data-lucide="maximize-2" style="width:13px;height:13px;"></i>
                                    <span>Perbesar Nota</span>
                                </button>
                            </div>
                            <div style="cursor:pointer;display:inline-block;position:relative;border-radius:6px;overflow:hidden;" @click="openReceiptPreview(activeDetail.purchase.url_foto_nota, activeDetail.purchase.nomor_faktur_pembelian)" title="Klik untuk melihat foto nota dalam ukuran penuh">
                                <img :src="'<?= Router::url('/') ?>' + activeDetail.purchase.url_foto_nota.replace(/^\//, '')" 
                                     style="max-height:140px;max-width:100%;border-radius:6px;border:1px solid var(--color-hairline);display:block;" 
                                     alt="Nota Vendor"
                                     @error="$event.target.style.display='none'; if ($event.target.nextElementSibling) $event.target.nextElementSibling.style.display='flex'; $nextTick(() => { if (typeof lucide !== 'undefined') lucide.createIcons(); });">
                                <div style="display:none;align-items:center;gap:8px;padding:12px 14px;background:var(--color-surface);border:1px dashed var(--color-hairline);border-radius:6px;font-size:12px;color:var(--color-ink-mute);">
                                    <i data-lucide="image-off" style="width:16px;height:16px;color:#ef4444;flex-shrink:0;"></i>
                                    <span>Berkas foto fisik nota ini tidak ditemukan di server</span>
                                </div>
                            </div>
                        </div>
                    </template>

                    <!-- Catatan -->
                    <template x-if="activeDetail.purchase.catatan">
                        <div style="font-size:11.5px;color:var(--color-ink-secondary);padding:8px 10px;background:var(--color-canvas-soft);border-radius:6px;">
                            <strong>Catatan:</strong> <span x-text="activeDetail.purchase.catatan"></span>
                        </div>
                    </template>

                    <!-- Action Footer -->
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:6px;">
                        <button type="button" @click="showDetailModal = false" class="btn btn-secondary">Tutup</button>
                        
                        <!-- Quick Pay Button inside Detail Modal -->
                        <template x-if="activeDetail.purchase.status_pembayaran === 'belum_lunas'">
                            <button type="button" @click="showDetailModal = false; openPayModal(activeDetail.purchase)" class="btn btn-primary" style="background:#f59e0b;border-color:#f59e0b;font-weight:700;">
                                <i data-lucide="credit-card"></i>
                                <span>Bayar Hutang Sekarang</span>
                            </button>
                        </template>
                    </div>
                </div>
            </template>
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

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:6px;">
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

                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:6px;">
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
    <!-- STYLES KHUSUS RECEIPT PHOTO VIEWER (MOBILE-FIRST & GESTURES)              -->
    <!-- ========================================================================= -->
    <style>
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

                    <!-- Rotate 90° Clockwise -->
                    <button type="button" @click="rotateClockwise()" class="receipt-tool-btn" title="Putar Posisi 90°">
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
                    <span class="hidden sm:inline">Geser untuk memindahkan foto • Scroll mouse / Cubit 2 jari untuk zoom • Ketuk 2x untuk zoom cepat</span>
                    <span class="inline sm:hidden">Cubit 2 jari untuk zoom • Geser foto • Ketuk 2x zoom</span>
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
        suggestedPbSuffix: '<?= $suggestedPbSuffix ?>',

        searchQuery: '',
        filterStatus: 'semua',
        filterSupplier: 'semua',

        showModal: false,
        showDetailModal: false,
        showPayModal: false,
        showCancelModal: false,
        showReceiptModal: false,
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

        form: {
            pemasok_id: '',
            nomor_faktur_suffix: '',
            tanggal_pembelian: '<?= date('Y-m-d') ?>',
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
                    (p.catatan && p.catatan.toLowerCase().includes(q));

                const matchStatus = this.filterStatus === 'semua' || p.status_pembayaran === this.filterStatus;
                const matchSupplier = this.filterSupplier === 'semua' || String(p.pemasok_id) === String(this.filterSupplier);

                return matchQuery && matchStatus && matchSupplier;
            });
        },

        get formTotal() {
            return this.form.items.reduce((sum, it) => {
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

        onSupplierChange() {
            if (this.form.items.some(it => it.item_id)) {
                if (!confirm('Mengganti pemasok akan mereset daftar barang yang telah dipilih. Lanjutkan?')) {
                    return;
                }
            }
            this.form.items = [];
            this.addItemRow();
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
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
            this.onItemChange(idx);
        },

        toggleItemDropdown(row) {
            const wasOpen = row.dropdownOpen;
            this.form.items.forEach(r => r.dropdownOpen = false);
            row.dropdownOpen = !wasOpen;
            if (row.dropdownOpen) {
                row.search = '';
                this.$nextTick(() => {
                    if (typeof lucide !== 'undefined') lucide.createIcons();
                });
            }
        },

        openAddModal() {
            this.form = {
                pemasok_id: '',
                nomor_faktur_suffix: this.suggestedPbSuffix,
                tanggal_pembelian: '<?= date('Y-m-d') ?>',
                status_pembayaran: 'lunas',
                akun_kas_id: '<?= $cashAccounts[0]['id'] ?? '' ?>',
                catatan: '',
                items: []
            };
            this.showAllMaterials = false;
            this.clearPhoto();
            this.showModal = true;
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        addItemRow() {
            this.form.items.push({ 
                item_id: '', 
                qty: 1, 
                harga_satuan: '0', 
                subtotal: 0, 
                dropdownOpen: false, 
                search: '' 
            });
            this.$nextTick(() => {
                if (typeof lucide !== 'undefined') lucide.createIcons();
            });
        },

        removeItemRow(idx) {
            this.form.items.splice(idx, 1);
            if (this.form.items.length === 0) {
                this.addItemRow();
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
                        await window.AppAction.success('Hutang Berhasil Dilunasi! ✨', '', 1200);
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
                        await window.AppAction.success('Faktur Dibatalkan! ✨', '', 1200);
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

        async submitPurchase() {
            if (!this.form.pemasok_id) {
                toast.warning('Mohon pilih vendor pemasok terlebih dahulu!');
                return;
            }

            const validItems = this.form.items.filter(it => it.item_id && Number(it.qty) > 0);
            if (validItems.length === 0) {
                toast.warning('Mohon tambahkan minimal 1 item barang yang diterima dengan kuantitas > 0!');
                return;
            }

            if (this.form.status_pembayaran === 'lunas') {
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
                window.AppAction.show('Menyimpan faktur pembelian...');
            }

            try {
                const formData = new FormData();
                formData.append('pemasok_id', this.form.pemasok_id);
                formData.append('nomor_faktur', 'PB-' + (this.form.nomor_faktur_suffix || '').trim());
                formData.append('tanggal_pembelian', this.form.tanggal_pembelian);
                formData.append('status_pembayaran', this.form.status_pembayaran);
                formData.append('akun_kas_id', this.form.status_pembayaran === 'lunas' ? (this.form.akun_kas_id || '') : '');
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
                        await window.AppAction.success('Faktur Berhasil Disimpan! ✨', '', 1200);
                    } else {
                        toast.success('Faktur pembelian berhasil disimpan dan stok otomatis bertambah!');
                    }
                    try { sessionStorage.setItem('app_action_triggered', 'true'); } catch (e) {}
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    const msg = json.message || 'Gagal menyimpan faktur';
                    if (window.AppAction) {
                        await window.AppAction.error('Gagal Menyimpan Faktur', msg);
                    } else {
                        toast.error('Gagal: ' + msg);
                    }
                }
            } catch (err) {
                if (window.AppAction) {
                    await window.AppAction.error('Kesalahan Jaringan / Koneksi!', 'Terjadi kesalahan koneksi saat menyimpan faktur pembelian.');
                } else {
                    toast.error('Terjadi kesalahan koneksi saat menyimpan faktur pembelian.');
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


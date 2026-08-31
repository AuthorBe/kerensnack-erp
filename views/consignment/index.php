<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;
ob_start();
?>

<div x-data="consignmentAdminApp()" x-init="init()" class="space-y-6 pb-20">

    <!-- ========================================================================= -->
    <!-- PAGE HEADER                                                               -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body">
            <div class="page-header-icon is-amber">
                <i data-lucide="store"></i>
            </div>
            <div class="page-header-text">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:#f59e0b;"></span>
                    <span>Admin Logistik &amp; Piutang Konsinyasi</span>
                </div>
                <h1 class="page-title">Portal Konsinyasi Rak Toko</h1>
                <p class="page-subtitle">Monitoring Saldo Rak, Assignment Sales, Pantau Pengiriman, &amp; Penagihan Piutang</p>
            </div>
        </div>
        <div class="page-header-actions" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;">
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- RINGKASAN METRIK GLOBAL (KPI CARDS)                                       -->
    <!-- ========================================================================= -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Toko -->
        <div class="card p-4 flex flex-col justify-between" style="border-radius:16px;background:var(--color-surface);">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400">Total Toko Konsinyasi</span>
                <div class="w-8 h-8 rounded-lg bg-amber-500/15 text-amber-400 flex items-center justify-center">
                    <i data-lucide="store" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-slate-100 mt-2"><?= $totalStores ?> Toko</div>
            <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-1">
                <span class="text-rose-400 font-bold" x-text="overdueStoreCount + ' toko'"></span>
                <span>belum opname &gt;14 hari</span>
            </div>
        </div>

        <!-- Total Titip di Rak -->
        <div class="card p-4 flex flex-col justify-between" style="border-radius:16px;background:var(--color-surface);">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400">Total Stok di Rak Toko</span>
                <div class="w-8 h-8 rounded-lg bg-sky-500/15 text-sky-400 flex items-center justify-center">
                    <i data-lucide="boxes" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-sky-400 mt-2"><?= number_format($totalPcsTitip) ?> pcs</div>
            <div class="text-[11px] text-slate-400 mt-1">Tersebar di seluruh rak mitra</div>
        </div>

        <!-- Omzet Laku Bulan Ini -->
        <div class="card p-4 flex flex-col justify-between" style="border-radius:16px;background:var(--color-surface);">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400">Penjualan Bulan Ini</span>
                <div class="w-8 h-8 rounded-lg bg-emerald-500/15 text-emerald-400 flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-emerald-400 mt-2"><?= Format::rupiah($totalLakuBulanIni) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Hasil kunjungan settlement opname</div>
        </div>

        <!-- Outstanding Piutang -->
        <div class="card p-4 flex flex-col justify-between" style="border-radius:16px;background:var(--color-surface);">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-400">Outstanding Piutang</span>
                <div class="w-8 h-8 rounded-lg bg-rose-500/15 text-rose-400 flex items-center justify-center">
                    <i data-lucide="receipt" class="w-4 h-4"></i>
                </div>
            </div>
            <div class="text-2xl font-black text-rose-400 mt-2"><?= Format::rupiah($totalPiutangKonsinyasi) ?></div>
            <div class="text-[11px] text-slate-400 mt-1">Tagihan laku belum dilunasi</div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 5-TAB ADMIN PERSONA NAVIGATION                                            -->
    <!-- ========================================================================= -->
    <div class="no-scrollbar" style="display:flex;gap:8px;padding-bottom:4px;overflow-x:auto;-webkit-overflow-scrolling:touch;">
        <button type="button" 
                @click="switchTab('dashboard')"
                :class="activeTab === 'dashboard' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="layout-grid" style="width:15px;height:15px;"></i>
            <span>B1: Saldo Rak Semua Toko</span>
        </button>

        <button type="button" 
                @click="switchTab('assignment')"
                :class="activeTab === 'assignment' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="users" style="width:15px;height:15px;"></i>
            <span>B2: Assignment Sales</span>
        </button>

        <button type="button" 
                @click="switchTab('deliveries')"
                :class="activeTab === 'deliveries' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="truck" style="width:15px;height:15px;"></i>
            <span>B3: Pengiriman Berjalan</span>
            <?php 
                $pendingSJCount = count(array_filter($deliveries ?? [], fn($d) => in_array($d['status_surat_jalan'], ['draf_n8n', 'sedang_dikirim'])));
                if ($pendingSJCount > 0):
            ?>
            <span class="badge badge-warning text-[10px] px-1.5 py-0.2"><?= $pendingSJCount ?></span>
            <?php endif; ?>
        </button>

        <button type="button" 
                @click="switchTab('piutang')"
                :class="activeTab === 'piutang' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="wallet" style="width:15px;height:15px;"></i>
            <span>B4: Piutang &amp; Bayar</span>
            <?php if (!empty($unpaidInvoices)): ?>
            <span class="badge badge-danger text-[10px] px-1.5 py-0.2"><?= count($unpaidInvoices) ?></span>
            <?php endif; ?>
        </button>

        <button type="button" 
                @click="switchTab('history')"
                :class="activeTab === 'history' ? 'btn btn-primary' : 'btn btn-secondary'"
                style="display:flex;align-items:center;gap:8px;font-weight:800;padding:8px 16px;border-radius:10px;font-size:12.5px;white-space:nowrap;flex-shrink:0;">
            <i data-lucide="history" style="width:15px;height:15px;"></i>
            <span>B5: Riwayat Kunjungan</span>
        </button>
    </div>

    <!-- ========================================================================= -->
    <!-- TAB B1: DASHBOARD SALDO RAK SEMUA TOKO                                    -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'dashboard'" x-cloak class="space-y-4">
        
        <!-- Filter & Search Box -->
        <div class="card p-4 flex flex-col md:flex-row gap-3 items-center justify-between" style="border-radius:14px;background:var(--color-surface);">
            <div class="flex flex-1 gap-3 w-full flex-wrap">
                <!-- Search Toko / SKU -->
                <div style="position:relative;min-width:240px;" class="flex-1">
                    <i data-lucide="search" style="position:absolute;left:10px;top:50%;transform:translateY(-50%);width:16px;height:16px;color:var(--color-ink-muted);"></i>
                    <input type="text" 
                           x-model="searchShelf" 
                           placeholder="Cari nama toko, kode, atau produk..." 
                           class="form-input" 
                           style="padding-left:34px;height:38px;border-radius:8px;font-size:13px;background:var(--color-surface-soft);">
                </div>

                <!-- Filter Sales Driver -->
                <select x-model="filterDriver" class="form-select text-xs font-bold" style="height:38px;border-radius:8px;background:var(--color-surface-soft);min-width:180px;">
                    <option value="">Semua Sales Driver</option>
                    <?php foreach ($drivers as $d): ?>
                    <option value="<?= htmlspecialchars($d['nama_karyawan']) ?>"><?= htmlspecialchars($d['nama_karyawan']) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- Filter Status Overdue -->
                <select x-model="filterOverdue" class="form-select text-xs font-bold" style="height:38px;border-radius:8px;background:var(--color-surface-soft);min-width:160px;">
                    <option value="all">Semua Status Opname</option>
                    <option value="overdue">Perlu Opname (&gt;14 Hari)</option>
                    <option value="normal">Normal (&le;14 Hari)</option>
                </select>
            </div>

            <!-- Tombol Cetak Ringkasan -->
            <button type="button" @click="window.print()" class="btn btn-secondary text-xs font-bold shrink-0" style="height:38px;">
                <i data-lucide="printer" class="w-3.5 h-3.5"></i>
                <span>Cetak Ringkasan</span>
            </button>
        </div>

        <!-- Tabel Saldo Rak -->
        <div class="card overflow-hidden" style="border-radius:14px;background:var(--color-surface);">
            <div class="table-responsive">
                <table class="table w-full text-xs">
                    <thead>
                        <tr class="bg-slate-900/80 text-slate-300 font-extrabold uppercase tracking-wider border-b border-slate-800">
                            <th class="py-3 px-4 text-left">Toko Konsinyasi</th>
                            <th class="py-3 px-4 text-left">Sales Pemegang</th>
                            <th class="py-3 px-4 text-left">Nama Produk (SKU)</th>
                            <th class="py-3 px-4 text-center">Saldo Rak (Pcs)</th>
                            <th class="py-3 px-4 text-left">Terakhir Opname</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <template x-for="item in filteredShelfStocks" :key="item.id">
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-100">
                                    <div x-text="item.nama_toko"></div>
                                    <div class="text-[11px] text-slate-400 font-normal" x-text="item.kode_pelanggan"></div>
                                </td>
                                <td class="py-3 px-4 text-slate-300" x-text="item.nama_sales || '—'"></td>
                                <td class="py-3 px-4 font-semibold text-slate-200">
                                    <div x-text="item.nama_item"></div>
                                    <div class="text-[10.5px] text-slate-500 font-mono" x-text="item.kode_sku"></div>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <span class="font-black text-sm" 
                                          :class="item.stok_titip_saat_ini > 0 ? 'text-amber-400' : 'text-slate-500'"
                                          x-text="item.stok_titip_saat_ini + ' ' + (item.satuan_dasar || 'pcs')"></span>
                                </td>
                                <td class="py-3 px-4 text-slate-300" x-text="item.terakhir_opname_text"></td>
                                <td class="py-3 px-4 text-center">
                                    <template x-if="item.is_overdue">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                            <i data-lucide="alert-triangle" class="w-3 h-3"></i>
                                            <span x-text="item.days_since !== null ? item.days_since + ' hr lalu' : 'Belum Pernah'"></span>
                                        </span>
                                    </template>
                                    <template x-if="!item.is_overdue">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                            <i data-lucide="check" class="w-3 h-3"></i>
                                            <span>Normal</span>
                                        </span>
                                    </template>
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <a :href="'<?= Router::url('/consignment/opname') ?>?pelanggan_id=' + item.pelanggan_id" 
                                       class="btn btn-secondary py-1 px-2.5 text-[11px] font-bold" target="_blank">
                                        Opname Toko
                                    </a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div x-show="filteredShelfStocks.length === 0" class="p-8 text-center text-slate-400 text-xs">
                Tidak ada data saldo rak yang cocok dengan filter pencarian.
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB B2: ASSIGNMENT TOKO KE SALES-DRIVER                                   -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'assignment'" x-cloak class="space-y-4">
        
        <!-- Bulk Action Floating Bar -->
        <div x-show="selectedStoresForAssign.length > 0" 
             class="p-4 rounded-xl bg-amber-950/80 border border-amber-500/40 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="w-6 h-6 rounded-full bg-amber-500 text-slate-950 font-black flex items-center justify-center text-xs" x-text="selectedStoresForAssign.length"></span>
                <span class="font-bold text-amber-200">Toko Konsinyasi Terpilih</span>
            </div>

            <form action="<?= Router::url('/consignment/assign-driver') ?>" method="POST" class="flex items-center gap-2">
                <?= \App\Helpers\CSRF::field() ?>
                <template x-for="id in selectedStoresForAssign" :key="id">
                    <input type="hidden" name="store_ids[]" :value="id">
                </template>

                <select name="sales_driver_id" class="form-select text-xs font-bold" style="height:36px;border-radius:8px;background:var(--color-surface);" required>
                    <option value="">-- Pilih Sales Driver --</option>
                    <?php foreach ($drivers as $d): ?>
                    <option value="<?= htmlspecialchars($d['id']) ?>"><?= htmlspecialchars($d['nama_karyawan']) ?></option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary text-xs font-black py-2 px-4" style="background:#f59e0b;border-color:#f59e0b;color:#0f172a;">
                    Tugaskan Sales Terpilih
                </button>
            </form>
        </div>

        <!-- Tabel Toko & Assignment -->
        <div class="card overflow-hidden" style="border-radius:14px;background:var(--color-surface);">
            <div class="table-responsive">
                <table class="table w-full text-xs">
                    <thead>
                        <tr class="bg-slate-900/80 text-slate-300 font-extrabold uppercase tracking-wider border-b border-slate-800">
                            <th class="py-3 px-4 text-center w-10">
                                <input type="checkbox" @change="toggleSelectAllStores($event)" class="form-checkbox">
                            </th>
                            <th class="py-3 px-4 text-left">Nama Toko Mitra</th>
                            <th class="py-3 px-4 text-left">Wilayah / Rute</th>
                            <th class="py-3 px-4 text-left">Kontak Pemilik</th>
                            <th class="py-3 px-4 text-left">Sales Pemegang Saat Ini</th>
                            <th class="py-3 px-4 text-left">Ganti Penugasan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php foreach ($consignmentStores as $cs): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-3 px-4 text-center">
                                <input type="checkbox" 
                                       value="<?= htmlspecialchars($cs['id']) ?>" 
                                       x-model="selectedStoresForAssign"
                                       class="form-checkbox">
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-100">
                                <div><?= htmlspecialchars($cs['nama_toko']) ?></div>
                                <div class="text-[11px] text-slate-400 font-normal"><?= htmlspecialchars($cs['kode_pelanggan']) ?></div>
                            </td>
                            <td class="py-3 px-4 text-slate-300"><?= htmlspecialchars($cs['rute'] ?? '—') ?></td>
                            <td class="py-3 px-4 text-slate-300">
                                <div><?= htmlspecialchars($cs['nama_pemilik'] ?? '—') ?></div>
                                <div class="text-[11px] text-slate-500"><?= htmlspecialchars($cs['nomor_whatsapp'] ?? $cs['nomor_telepon'] ?? '—') ?></div>
                            </td>
                            <td class="py-3 px-4">
                                <?php if (!empty($cs['nama_sales'])): ?>
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-sky-500/20 text-sky-400 border border-sky-500/30">
                                    <i data-lucide="user-check" class="w-3 h-3"></i>
                                    <span><?= htmlspecialchars($cs['nama_sales']) ?></span>
                                </span>
                                <?php else: ?>
                                <span class="text-slate-500 italic">Belum di-assign</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4">
                                <form action="<?= Router::url('/consignment/assign-driver') ?>" method="POST" class="flex items-center gap-1.5">
                                    <?= \App\Helpers\CSRF::field() ?>
                                    <input type="hidden" name="pelanggan_id" value="<?= htmlspecialchars($cs['id']) ?>">
                                    <select name="sales_driver_id" class="form-select text-[11px] font-bold py-1 px-2" style="height:32px;border-radius:6px;background:var(--color-surface-soft);max-width:160px;" required>
                                        <option value="">-- Pilih Sales --</option>
                                        <?php foreach ($drivers as $d): ?>
                                        <option value="<?= htmlspecialchars($d['id']) ?>" <?= ($cs['sales_driver_id'] === $d['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($d['nama_karyawan']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-secondary py-1 px-2 text-[11px] font-bold" title="Simpan Perubahan Sales">
                                        Simpan
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB B3: KELOLA PENGIRIMAN BERJALAN                                        -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'deliveries'" x-cloak class="space-y-4">
        
        <div class="card overflow-hidden" style="border-radius:14px;background:var(--color-surface);">
            <div class="table-responsive">
                <table class="table w-full text-xs">
                    <thead>
                        <tr class="bg-slate-900/80 text-slate-300 font-extrabold uppercase tracking-wider border-b border-slate-800">
                            <th class="py-3 px-4 text-left">No. Surat Jalan</th>
                            <th class="py-3 px-4 text-left">Toko Tujuan</th>
                            <th class="py-3 px-4 text-left">Sales Driver</th>
                            <th class="py-3 px-4 text-left">Tanggal Pengajuan</th>
                            <th class="py-3 px-4 text-center">Total Titipan</th>
                            <th class="py-3 px-4 text-center">Status Pengiriman</th>
                            <th class="py-3 px-4 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($deliveries)): ?>
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400">Belum ada data pengiriman titip konsinyasi.</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($deliveries as $del): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-slate-200">
                                <?= htmlspecialchars($del['nomor_surat_jalan']) ?>
                            </td>
                            <td class="py-3 px-4 font-bold text-slate-100">
                                <div><?= htmlspecialchars($del['nama_toko']) ?></div>
                                <div class="text-[11px] text-slate-400 font-normal"><?= htmlspecialchars($del['kode_pelanggan']) ?></div>
                            </td>
                            <td class="py-3 px-4 text-slate-300"><?= htmlspecialchars($del['nama_sales']) ?></td>
                            <td class="py-3 px-4 text-slate-400"><?= date('d M Y H:i', strtotime($del['dibuat_pada'])) ?></td>
                            <td class="py-3 px-4 text-center">
                                <span class="font-extrabold text-sky-400"><?= (int)$del['total_pcs'] ?> pcs</span>
                                <span class="text-[10.5px] text-slate-500">(<?= (int)$del['total_sku'] ?> SKU)</span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                <?php match($del['status_surat_jalan']) {
                                    'draf_n8n' => print('<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">Menunggu Approval</span>'),
                                    'disetujui_owner' => print('<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30">Disetujui Owner</span>'),
                                    'sedang_dikirim' => print('<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30 animate-pulse">Sedang Dikirim</span>'),
                                    'selesai_diterima' => print('<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Selesai Diterima</span>'),
                                    'ditolak_owner' => print('<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10.5px] font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">Ditolak / Batal</span>'),
                                    default => print('<span class="badge">' . htmlspecialchars($del['status_surat_jalan']) . '</span>')
                                }; ?>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <?php if ($del['status_surat_jalan'] === 'draf_n8n'): ?>
                                <form action="<?= Router::url('/consignment/cancel-delivery') ?>" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan draft pengiriman ini?');">
                                    <?= \App\Helpers\CSRF::field() ?>
                                    <input type="hidden" name="surat_jalan_id" value="<?= htmlspecialchars($del['surat_jalan_id']) ?>">
                                    <button type="submit" class="btn btn-secondary text-rose-400 hover:text-rose-300 py-1 px-2.5 text-[11px] font-bold">
                                        Batalkan Draft
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="text-slate-600 text-xs">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB B4: LIST PIUTANG & CATAT PEMBAYARAN KONSINYASI                         -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'piutang'" x-cloak class="space-y-4">
        
        <div class="card overflow-hidden" style="border-radius:14px;background:var(--color-surface);">
            <div class="table-responsive">
                <table class="table w-full text-xs">
                    <thead>
                        <tr class="bg-slate-900/80 text-slate-300 font-extrabold uppercase tracking-wider border-b border-slate-800">
                            <th class="py-3 px-4 text-left">No. Nota Tagihan</th>
                            <th class="py-3 px-4 text-left">Tanggal Faktur</th>
                            <th class="py-3 px-4 text-left">Toko Mitra</th>
                            <th class="py-3 px-4 text-left">Sales Pemegang</th>
                            <th class="py-3 px-4 text-right">Total Netto</th>
                            <th class="py-3 px-4 text-right">Sudah Dibayar</th>
                            <th class="py-3 px-4 text-right">Sisa Tagihan</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-right">Aksi Bayar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($unpaidInvoices)): ?>
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400">
                                🎉 Luar biasa! Tidak ada piutang konsinyasi yang tertunda (Semua nota lunas).
                            </td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($unpaidInvoices as $inv): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-sky-400">
                                <?= htmlspecialchars($inv['nomor_nota']) ?>
                            </td>
                            <td class="py-3 px-4 text-slate-300"><?= date('d M Y', strtotime($inv['tanggal_pesanan'])) ?></td>
                            <td class="py-3 px-4 font-bold text-slate-100">
                                <div><?= htmlspecialchars($inv['nama_toko']) ?></div>
                                <div class="text-[11px] text-slate-400 font-normal"><?= htmlspecialchars($inv['kode_pelanggan']) ?></div>
                            </td>
                            <td class="py-3 px-4 text-slate-300"><?= htmlspecialchars($inv['nama_sales'] ?? '—') ?></td>
                            <td class="py-3 px-4 text-right font-semibold text-slate-200"><?= Format::rupiah((float)$inv['total_netto']) ?></td>
                            <td class="py-3 px-4 text-right text-emerald-400 font-semibold"><?= Format::rupiah((float)$inv['total_dibayar']) ?></td>
                            <td class="py-3 px-4 text-right font-black text-rose-400 text-sm"><?= Format::rupiah((float)$inv['sisa_tagihan']) ?></td>
                            <td class="py-3 px-4 text-center">
                                <?php if ($inv['status_pembayaran'] === 'sebagian'): ?>
                                <span class="badge badge-warning text-[10.5px]">Sebagian (Cicil)</span>
                                <?php else: ?>
                                <span class="badge badge-danger text-[10.5px]">Belum Lunas</span>
                                <?php endif; ?>
                            </td>
                            <td class="py-3 px-4 text-right">
                                <button type="button" 
                                        @click="openPaymentModal(<?= htmlspecialchars(json_encode([
                                            'pesanan_id' => $inv['pesanan_id'],
                                            'nomor_nota' => $inv['nomor_nota'],
                                            'nama_toko' => $inv['nama_toko'],
                                            'sisa_tagihan' => (float)$inv['sisa_tagihan']
                                        ])) ?>)"
                                        class="btn btn-primary py-1.5 px-3 text-xs font-bold"
                                        style="background:#059669;border-color:#059669;">
                                    <i data-lucide="wallet" class="w-3.5 h-3.5"></i>
                                    <span>Catat Bayar</span>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL CATAT PEMBAYARAN KONSINYASI -->
        <div x-show="showPaymentModal" 
             x-cloak 
             class="modal-backdrop"
             @keydown.escape.window="showPaymentModal = false">
            
            <div class="modal-box space-y-4" @click.outside="showPaymentModal = false">
                <div class="modal-header">
                    <div class="flex items-center gap-2">
                        <div style="width:32px;height:32px;border-radius:8px;background:rgba(16,185,129,0.12);color:var(--color-success);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M21 12V7H5a2 2 0 0 1 0-4h14v4"></path>
                                <path d="M3 5v14a2 2 0 0 0 2 2h16v-5"></path>
                                <path d="M18 12a2 2 0 0 0 0 4h4v-4Z"></path>
                            </svg>
                        </div>
                        <h3 class="modal-title" style="color:var(--color-ink);font-weight:800;font-size:15px;">Catat Pembayaran Tagihan</h3>
                    </div>
                    <button type="button" @click="showPaymentModal = false" class="modal-close-btn" aria-label="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>

                <form action="<?= Router::url('/consignment/pay-invoice') ?>" method="POST" class="space-y-4">
                    <?= \App\Helpers\CSRF::field() ?>
                    <input type="hidden" name="pesanan_id" :value="payTarget.pesanan_id">

                    <div class="p-3 rounded-xl space-y-1 text-xs" style="background:var(--color-canvas-soft);border:1px solid var(--color-hairline);">
                        <div class="flex justify-between">
                            <span style="color:var(--color-ink-mute);">No. Nota Tagihan:</span>
                            <span class="font-mono font-bold" style="color:var(--color-primary);" x-text="payTarget.nomor_nota"></span>
                        </div>
                        <div class="flex justify-between">
                            <span style="color:var(--color-ink-mute);">Toko Mitra:</span>
                            <span class="font-bold" style="color:var(--color-ink);" x-text="payTarget.nama_toko"></span>
                        </div>
                        <div class="flex justify-between pt-1 border-t" style="border-color:var(--color-hairline);">
                            <span style="color:var(--color-ink-mute);">Sisa Tagihan Saat Ini:</span>
                            <span class="font-black text-sm" style="color:var(--color-danger);" x-text="formatRupiah(payTarget.sisa_tagihan)"></span>
                        </div>
                    </div>

                    <!-- Pilih Akun Kas -->
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:var(--color-ink);margin-bottom:4px;">Setor ke Rekening / Kas Penerima *</label>
                        <select name="akun_kas_id" class="form-select w-full text-xs font-bold" style="height:40px;border-radius:8px;" required>
                            <?php foreach ($cashAccounts as $ca): ?>
                            <option value="<?= htmlspecialchars($ca['id']) ?>">
                                <?= htmlspecialchars($ca['nama_akun']) ?> (Saldo: <?= Format::rupiah((float)$ca['saldo_saat_ini']) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Nominal Bayar -->
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:var(--color-ink);margin-bottom:4px;">Nominal Pembayaran Diterima (Rp) *</label>
                        <input type="number" 
                               name="nominal" 
                               x-model.number="payTarget.nominal_bayar"
                               min="1" 
                               :max="payTarget.sisa_tagihan"
                               class="form-input text-base font-black" 
                               style="height:42px;border-radius:8px;color:var(--color-success);" 
                               required>
                        <div style="font-size:10.5px;color:var(--color-ink-mute);margin-top:4px;">Bisa diisi sebagian jika toko mencicil/angsuran.</div>
                    </div>

                    <!-- Keterangan -->
                    <div>
                        <label style="display:block;font-size:11px;font-weight:700;color:var(--color-ink);margin-bottom:4px;">Keterangan / Ref Bukti Transfer</label>
                        <input type="text" 
                               name="keterangan" 
                               placeholder="Misal: Transfer BCA a.n Toko..."
                               class="form-input text-xs" 
                               style="height:38px;border-radius:8px;">
                    </div>

                    <div style="display:flex;gap:8px;padding-top:8px;">
                        <button type="button" @click="showPaymentModal = false" class="btn btn-secondary" style="flex:1;font-weight:700;padding:10px;">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary" style="flex:1;font-weight:800;padding:10px;background:var(--color-success);border-color:var(--color-success);">
                            Simpan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

    <!-- ========================================================================= -->
    <!-- TAB B5: RIWAYAT KUNJUNGAN SEMUA SALES                                     -->
    <!-- ========================================================================= -->
    <div x-show="activeTab === 'history'" x-cloak class="space-y-4">
        
        <div class="card overflow-hidden" style="border-radius:14px;background:var(--color-surface);">
            <div class="table-responsive">
                <table class="table w-full text-xs">
                    <thead>
                        <tr class="bg-slate-900/80 text-slate-300 font-extrabold uppercase tracking-wider border-b border-slate-800">
                            <th class="py-3 px-4 text-left">No. Kunjungan</th>
                            <th class="py-3 px-4 text-left">Tanggal</th>
                            <th class="py-3 px-4 text-left">Sales Petugas</th>
                            <th class="py-3 px-4 text-left">Toko Mitra</th>
                            <th class="py-3 px-4 text-right">Total Laku (Rp)</th>
                            <th class="py-3 px-4 text-center">Laku (Pcs)</th>
                            <th class="py-3 px-4 text-center">Retur Bagus</th>
                            <th class="py-3 px-4 text-center">Retur Rusak (BS)</th>
                            <th class="py-3 px-4 text-right">Kerugian Rusak</th>
                            <th class="py-3 px-4 text-center">Nota Terbit</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        <?php if (empty($recentVisits)): ?>
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400">Belum ada riwayat kunjungan opname konsinyasi.</td>
                        </tr>
                        <?php endif; ?>

                        <?php foreach ($recentVisits as $rv): ?>
                        <tr class="hover:bg-slate-800/30 transition-colors">
                            <td class="py-3 px-4 font-mono font-bold text-slate-200">
                                <?= htmlspecialchars($rv['nomor_kunjungan']) ?>
                            </td>
                            <td class="py-3 px-4 text-slate-300"><?= date('d M Y', strtotime($rv['tanggal_kunjungan'])) ?></td>
                            <td class="py-3 px-4 font-semibold text-slate-200"><?= htmlspecialchars($rv['sales_driver']) ?></td>
                            <td class="py-3 px-4 font-bold text-slate-100">
                                <div><?= htmlspecialchars($rv['nama_toko']) ?></div>
                                <div class="text-[11px] text-slate-400 font-normal"><?= htmlspecialchars($rv['kode_pelanggan']) ?></div>
                            </td>
                            <td class="py-3 px-4 text-right font-black text-emerald-400 text-sm">
                                <?= Format::rupiah((float)$rv['total_laku_nominal']) ?>
                            </td>
                            <td class="py-3 px-4 text-center font-bold text-slate-200"><?= (int)$rv['total_qty_laku'] ?></td>
                            <td class="py-3 px-4 text-center text-sky-400 font-semibold"><?= (int)$rv['total_qty_retur_bagus'] ?></td>
                            <td class="py-3 px-4 text-center text-rose-400 font-semibold"><?= (int)$rv['total_qty_retur_rusak'] ?></td>
                            <td class="py-3 px-4 text-right text-rose-400 font-bold">
                                <?= Format::rupiah((float)$rv['total_kerugian_rusak']) ?>
                            </td>
                            <td class="py-3 px-4 text-center font-mono text-[11px]">
                                <?php if (!empty($rv['nota_faktur'])): ?>
                                <span class="text-sky-400 font-bold"><?= htmlspecialchars($rv['nota_faktur']) ?></span>
                                <?php else: ?>
                                <span class="text-slate-500">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>

<script>
function consignmentAdminApp() {
    return {
        activeTab: '<?= htmlspecialchars($activeTab ?? 'dashboard') ?>',
        searchShelf: '',
        filterDriver: '',
        filterOverdue: 'all',
        selectedStoresForAssign: [],
        showPaymentModal: false,
        payTarget: {
            pesanan_id: '',
            nomor_nota: '',
            nama_toko: '',
            sisa_tagihan: 0,
            nominal_bayar: 0
        },

        shelfStocks: <?= json_encode(array_map(function($s) {
            $last = $s['terakhir_opname_pada'] ?? null;
            $daysSince = null;
            $isOverdue = true;
            $lastText = 'Belum Pernah';

            if ($last) {
                $diff = (time() - strtotime($last)) / (60 * 60 * 24);
                $daysSince = max(0, (int)floor($diff));
                $isOverdue = $daysSince > 14;
                $lastText = date('d M Y', strtotime($last));
            }

            return [
                'id' => $s['id'],
                'pelanggan_id' => $s['pelanggan_id'],
                'nama_toko' => $s['nama_toko'],
                'kode_pelanggan' => $s['kode_pelanggan'],
                'nama_sales' => $s['nama_sales'],
                'nama_item' => $s['nama_item'],
                'kode_sku' => $s['kode_sku'],
                'satuan_dasar' => $s['satuan_dasar'] ?? 'pcs',
                'stok_titip_saat_ini' => (int)$s['stok_titip_saat_ini'],
                'terakhir_opname_text' => $lastText,
                'days_since' => $daysSince,
                'is_overdue' => $isOverdue
            ];
        }, $shelfStocks ?? [])) ?>,

        init() {
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        switchTab(tab) {
            this.activeTab = tab;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        get overdueStoreCount() {
            const uniqueStores = {};
            this.shelfStocks.forEach(s => {
                if (s.is_overdue) uniqueStores[s.pelanggan_id] = true;
            });
            return Object.keys(uniqueStores).length;
        },

        get filteredShelfStocks() {
            return this.shelfStocks.filter(s => {
                // Search
                if (this.searchShelf.trim()) {
                    const q = this.searchShelf.toLowerCase();
                    const match = (s.nama_toko && s.nama_toko.toLowerCase().includes(q)) ||
                                  (s.kode_pelanggan && s.kode_pelanggan.toLowerCase().includes(q)) ||
                                  (s.nama_item && s.nama_item.toLowerCase().includes(q)) ||
                                  (s.kode_sku && s.kode_sku.toLowerCase().includes(q));
                    if (!match) return false;
                }

                // Filter Driver
                if (this.filterDriver && s.nama_sales !== this.filterDriver) {
                    return false;
                }

                // Filter Overdue
                if (this.filterOverdue === 'overdue' && !s.is_overdue) return false;
                if (this.filterOverdue === 'normal' && s.is_overdue) return false;

                return true;
            });
        },

        toggleSelectAllStores(e) {
            if (e.target.checked) {
                this.selectedStoresForAssign = <?= json_encode(array_column($consignmentStores ?? [], 'id')) ?>;
            } else {
                this.selectedStoresForAssign = [];
            }
        },

        openPaymentModal(target) {
            this.payTarget = {
                pesanan_id: target.pesanan_id,
                nomor_nota: target.nomor_nota,
                nama_toko: target.nama_toko,
                sisa_tagihan: target.sisa_tagihan,
                nominal_bayar: target.sisa_tagihan
            };
            this.showPaymentModal = true;
            this.$nextTick(() => {
                if (window.lucide) lucide.createIcons();
            });
        },

        formatRupiah(num) {
            return 'Rp ' + (new Intl.NumberFormat('id-ID')).format(Math.round(num || 0));
        }
    };
}
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>
<?php
use App\Helpers\Format;
use App\Core\Router;
use App\Core\Auth;

ob_start();
?>

<div x-data="reportHubApp()" class="space-y-5">

    <!-- ========================================================================= -->
    <!-- 1. PAGE HEADER (DNA STANDAR RESMI KEREN ONE ERP)                         -->
    <!-- ========================================================================= -->
    <div class="page-header">
        <div class="page-header-body" style="min-width:0; flex:1;">
            <div class="page-header-icon is-rose" style="flex-shrink:0;">
                <i data-lucide="folder-down"></i>
            </div>
            <div class="page-header-text" style="min-width:0;">
                <div class="page-header-tag">
                    <span class="tag-dot" style="background-color:var(--primary, #881337);"></span>
                    <span>Portal Eksekutif &bull; Arsip &amp; Ekspor Terpusat</span>
                </div>
                <h1 class="page-title text-xl sm:text-2xl font-bold">
                    <?= htmlspecialchars($pageTitle ?? 'Pusat Unduh Laporan') ?>
                </h1>
                <p class="page-subtitle text-xs sm:text-sm">
                    <?= htmlspecialchars($pageSubtitle ?? 'Portal satu pintu unduh rekapitulasi data bisnis, analisis finansial, dan audit trail format Excel & PDF') ?>
                </p>
            </div>
        </div>

        <div class="page-header-actions" style="display:flex; gap:8px; align-items:center; flex-wrap:wrap; flex-shrink:0;">
            <div class="header-chip" style="height:32px; padding:0 12px; display:inline-flex; align-items:center; gap:6px; background:rgba(136,19,55,0.06); border:1px solid rgba(136,19,55,0.18); border-radius:8px; font-size:12px; font-weight:700; color:var(--primary, #881337);">
                <i data-lucide="shield-check" style="width:14px; height:14px; color:var(--primary, #881337); flex-shrink:0;"></i>
                <span>Akses Khusus Manajemen</span>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 2. GLOBAL PERIOD FILTER TOOLBAR (DNA RESPONSIVE CARD)                     -->
    <!-- ========================================================================= -->
    <div class="report-filter-card">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 lg:gap-4">
            <!-- Left: Preset Buttons (Segmented Grab-and-Scroll Dock) -->
            <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-2.5 min-w-0">
                <div class="flex items-center gap-1.5 flex-shrink-0">
                    <div class="w-6 h-6 rounded-md flex items-center justify-center flex-shrink-0" style="background:rgba(136,19,55,0.08); border:1px solid rgba(136,19,55,0.18); color:var(--primary, #881337);">
                        <i data-lucide="calendar" style="width:13px; height:13px;"></i>
                    </div>
                    <span class="text-[11px] sm:text-xs font-bold text-ink uppercase tracking-wider">Periode:</span>
                </div>
                <!-- Segmented Control Dock with Grab & Scroll + Touch Scroll -->
                <div x-ref="presetTrack" class="report-filter-dock" title="Klik dan geser untuk melihat semua pilihan periode">
                    <button type="button" class="report-filter-btn" :class="{ 'is-active': activePreset === 'today' }" @click="setPreset('today')">Hari Ini</button>
                    <button type="button" class="report-filter-btn" :class="{ 'is-active': activePreset === '7days' }" @click="setPreset('7days')">7 Hari</button>
                    <button type="button" class="report-filter-btn" :class="{ 'is-active': activePreset === 'this_month' }" @click="setPreset('this_month')">Bulan Ini</button>
                    <button type="button" class="report-filter-btn" :class="{ 'is-active': activePreset === 'last_month' }" @click="setPreset('last_month')">Bulan Lalu</button>
                    <button type="button" class="report-filter-btn" :class="{ 'is-active': activePreset === 'this_year' }" @click="setPreset('this_year')">Tahun Ini</button>
                </div>
            </div>

            <!-- Right: Integrated Custom Date Range Inputs -->
            <div class="w-full lg:w-auto">
                <!-- Desktop / Tablet Inline View (sm:flex) -->
                <div class="hidden sm:flex items-center gap-1.5 report-date-range-box">
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-ink-mute uppercase tracking-wider flex-shrink-0">Dari</span>
                        <input type="date" class="report-date-input w-[130px]" x-model="startDate" @change="activePreset = 'custom'">
                    </div>
                    <span class="text-xs font-bold text-ink-mute flex-shrink-0 px-0.5">&ndash;</span>
                    <div class="flex items-center gap-1.5">
                        <span class="text-[11px] font-bold text-ink-mute uppercase tracking-wider flex-shrink-0">S/D</span>
                        <input type="date" class="report-date-input w-[130px]" x-model="endDate" @change="activePreset = 'custom'">
                    </div>
                </div>

                <!-- Mobile 2-Column Grid View (sm:hidden) - Bebas Tabrakan & Rapi -->
                <div class="grid grid-cols-2 gap-2 sm:hidden w-full">
                    <div class="report-date-range-box-mobile">
                        <span class="text-[10px] font-bold text-ink-mute uppercase tracking-wider mb-1">Mulai Dari:</span>
                        <input type="date" class="report-date-input w-full" x-model="startDate" @change="activePreset = 'custom'">
                    </div>
                    <div class="report-date-range-box-mobile">
                        <span class="text-[10px] font-bold text-ink-mute uppercase tracking-wider mb-1">Sampai Dengan:</span>
                        <input type="date" class="report-date-input w-full" x-model="endDate" @change="activePreset = 'custom'">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- 3. REPORT CATALOG SECTIONS (7 KATEGORI LENGKAP)                            -->
    <!-- ========================================================================= -->
    <div class="space-y-6">

        <!-- KATEGORI 1: EKSEKUTIF & LABA RUGI -->
        <section class="space-y-3">
            <div class="flex items-center gap-2.5">
                <div class="section-icon-badge is-maroon">
                    <i data-lucide="crown"></i>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-ink uppercase tracking-wider">1. Laporan Eksekutif &amp; Kinerja Bisnis (P&amp;L)</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5 sm:gap-4">
                <!-- Card Master Rekap Multi-Kanal -->
                <div class="report-box" style="border-color:rgba(136,19,55,0.25);">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(136,19,55,0.08); color:var(--primary, #881337); border:1px solid rgba(136,19,55,0.18);">
                            <i data-lucide="layers"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="report-pill-badge">
                                <span class="badge-dot"></span>
                                <span>Master 3 Pilar Bisnis</span>
                            </div>
                            <h3 class="report-box-title">Rekap Penjualan Multi-Kanal (POS, B2B &amp; Konsinyasi)</h3>
                            <p class="report-box-desc">Konsolidasi analitik penjualan 3 pilar kanal (Kasir POS, Faktur Grosir B2B, Titip Jual Konsinyasi Rak), omzet total, status pelunasan, sisa piutang, dan laba kotor murni.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <span class="text-xs text-ink-mute">Format Dokumen: <strong>Excel (.xlsx 4-Sheet)</strong> &bull; <strong>PDF Eksekutif (.pdf)</strong></span>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/sales-consolidated-excel', {}, 'Rekap Penjualan Multi-Kanal Excel')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Excel (4-Sheet)</span>
                        </button>
                        <button type="button" @click="downloadReport('/reports/export/sales-consolidated-pdf', {}, 'Rekap Penjualan Multi-Kanal PDF')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:var(--primary, #881337); color:#fff; border-color:#700f2b; font-weight:700;">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>

                <!-- Card Laba Rugi -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(136,19,55,0.08); color:var(--primary, #881337); border:1px solid rgba(136,19,55,0.18);">
                            <i data-lucide="trending-up"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Ringkasan Kinerja &amp; Laba Rugi Eksekutif</h3>
                            <p class="report-box-desc">Agregasi seluruh omzet (POS, B2B, Konsinyasi), Total HPP &amp; Kerugian Retur, Beban Operasional Kas, serta Estimasi Laba Bersih.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <span class="text-xs text-ink-mute">Format Dokumen: <strong>Excel (.xlsx)</strong> &bull; <strong>PDF Cetak (.pdf)</strong></span>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/pnl-excel', {}, 'Laporan Laba Rugi Eksekutif Excel')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Excel</span>
                        </button>
                        <button type="button" @click="downloadReport('/reports/export/pnl-pdf', {}, 'Laporan Laba Rugi Eksekutif PDF')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:var(--primary, #881337); color:#fff; border-color:#700f2b; font-weight:700;">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- KATEGORI 2: KEUANGAN & KAS -->
        <section class="space-y-3">
            <div class="flex items-center gap-2.5">
                <div class="section-icon-badge is-emerald">
                    <i data-lucide="landmark"></i>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-ink uppercase tracking-wider">2. Keuangan &amp; Buku Kas</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5 sm:gap-4">
                <!-- Card Cash Flow -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(16,185,129,0.08); color:#059669; border:1px solid rgba(16,185,129,0.18);">
                            <i data-lucide="activity"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Laporan Arus Kas (Cash Flow)</h3>
                            <p class="report-box-desc">Saldo awal periode, rincian arus kas masuk, arus kas keluar per kategori beban operasional, dan saldo akhir kas &amp; bank.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-ink-mute flex-shrink-0">Akun:</span>
                            <select class="form-input text-xs py-1 px-2 h-7 flex-1 min-w-0" x-model="cashFlowAccount" style="border-radius:6px;">
                                <option value="all">Semua Rekening &amp; Kas Tunai</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= htmlspecialchars((string)$acc['id']) ?>"><?= htmlspecialchars((string)$acc['nama_akun']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/cash-flow', { account_id: cashFlowAccount }, 'Laporan Arus Kas Excel')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Excel</span>
                        </button>
                        <button type="button" @click="downloadReport('/reports/export/cash-flow-pdf', { account_id: cashFlowAccount }, 'Laporan Arus Kas PDF')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:var(--primary, #881337); color:#fff; border-color:#700f2b; font-weight:700;">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>

                <!-- Card Mutasi Transaksi -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(16,185,129,0.08); color:#059669; border:1px solid rgba(16,185,129,0.18);">
                            <i data-lucide="arrow-left-right"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Rekap Mutasi Transaksi Kas &amp; Bank</h3>
                            <p class="report-box-desc">Buku besar mutasi kas masuk, pengeluaran beban operasional, dan transfer internal lengkap dengan nomor bukti kas.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <div class="grid grid-cols-2 gap-2">
                            <select class="form-input text-xs py-1 px-2 h-7 w-full min-w-0" x-model="cashTxAccount" style="border-radius:6px;">
                                <option value="all">Semua Akun</option>
                                <?php foreach ($accounts as $acc): ?>
                                    <option value="<?= htmlspecialchars((string)$acc['id']) ?>"><?= htmlspecialchars((string)$acc['nama_akun']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <select class="form-input text-xs py-1 px-2 h-7 w-full min-w-0" x-model="cashTxType" style="border-radius:6px;">
                                <option value="all">Semua Jenis</option>
                                <option value="masuk">Kas Masuk</option>
                                <option value="keluar">Kas Keluar</option>
                                <option value="transfer_keluar">Transfer</option>
                            </select>
                        </div>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/cash-transactions', { account_id: cashTxAccount, type: cashTxType }, 'Rekap Mutasi Transaksi Kas')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- KATEGORI 3: PENJUALAN B2B -->
        <section class="space-y-3">
            <div class="flex items-center gap-2.5">
                <div class="section-icon-badge is-blue">
                    <i data-lucide="shopping-bag"></i>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-ink uppercase tracking-wider">3. Penjualan &amp; Pesanan Grosir B2B</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5 sm:gap-4">
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(59,130,246,0.08); color:#2563eb; border:1px solid rgba(59,130,246,0.18);">
                            <i data-lucide="receipt"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Rekap Faktur &amp; Pesanan B2B</h3>
                            <p class="report-box-desc">Daftar seluruh pesanan penjualan grosir, nama toko pelanggan, sales PIC, subtotal, diskon, ongkir, sisa piutang, status bayar &amp; kirim.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <div class="grid grid-cols-2 gap-2">
                            <select class="form-input text-xs py-1 px-2 h-7 w-full min-w-0" x-model="orderStatusBayar" style="border-radius:6px;">
                                <option value="all">Semua Bayar</option>
                                <option value="lunas">Lunas</option>
                                <option value="sebagian">Sebagian (DP)</option>
                                <option value="belum_lunas">Belum Lunas</option>
                            </select>
                            <select class="form-input text-xs py-1 px-2 h-7 w-full min-w-0" x-model="orderStatusKirim" style="border-radius:6px;">
                                <option value="all">Semua Kirim</option>
                                <option value="terkirim">Terkirim</option>
                                <option value="dalam_pengiriman">Dalam Kirim</option>
                                <option value="siap_kirim">Siap Kirim</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/customer-orders', { status_bayar: orderStatusBayar, status_kirim: orderStatusKirim }, 'Rekap Pesanan Pelanggan B2B')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- KATEGORI 4: KONSINYASI -->
        <section class="space-y-3">
            <div class="flex items-center gap-2.5">
                <div class="section-icon-badge is-amber">
                    <i data-lucide="handshake"></i>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-ink uppercase tracking-wider">4. Konsinyasi (Titip Jual Display Rak)</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5 sm:gap-4">
                <!-- Penjualan Konsinyasi -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(245,158,11,0.08); color:#d97706; border:1px solid rgba(245,158,11,0.18);">
                            <i data-lucide="store"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Penjualan Rak per Toko &amp; Produk</h3>
                            <p class="report-box-desc">Rekap pergerakan stok rak toko mitra, stok awal, stok akhir, kuantitas terjual, harga satuan, retur baik, dan total omzet.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-ink-mute flex-shrink-0">Toko:</span>
                            <select class="form-input text-xs py-1 px-2 h-7 flex-1 min-w-0" x-model="consignmentStore" style="border-radius:6px;">
                                <option value="all">Semua Toko Mitra</option>
                                <?php foreach ($stores as $st): ?>
                                    <option value="<?= htmlspecialchars((string)$st['id']) ?>"><?= htmlspecialchars((string)$st['nama_toko']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/consignment-sales', { store_id: consignmentStore }, 'Laporan Penjualan Konsinyasi')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>

                <!-- Kerugian Rusak -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(239,68,68,0.08); color:#dc2626; border:1px solid rgba(239,68,68,0.18);">
                            <i data-lucide="package-x"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Kerugian &amp; Barang Rusak/Basi</h3>
                            <p class="report-box-desc">Rekap produk kedaluwarsa, bungkus rusak, atau barang ditarik di toko konsinyasi beserta estimasi nilai kerugian finansial.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <span class="text-xs text-ink-mute">Filter otomatis mengikuti rentang tanggal aktif toolbar.</span>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/consignment-loss', {}, 'Laporan Kerugian Barang Rusak Konsinyasi')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>

                <!-- Tagihan Piutang -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(245,158,11,0.08); color:#d97706; border:1px solid rgba(245,158,11,0.18);">
                            <i data-lucide="clipboard-list"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Rekap Tagihan &amp; Umur Piutang Toko</h3>
                            <p class="report-box-desc">Daftar tagihan jatuh tempo (aging receivables), nama pemilik toko, nomor kontak WhatsApp, total tagihan, dan sisa piutang.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-ink-mute flex-shrink-0">Status:</span>
                            <select class="form-input text-xs py-1 px-2 h-7 flex-1 min-w-0" x-model="invoiceStatus" style="border-radius:6px;">
                                <option value="all">Semua Status</option>
                                <option value="belum_lunas">Belum Lunas &amp; Sebagian</option>
                                <option value="lunas">Sudah Lunas</option>
                            </select>
                        </div>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/consignment-invoices', { status: invoiceStatus }, 'Rekap Tagihan Piutang Excel')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Excel</span>
                        </button>
                        <button type="button" @click="downloadReport('/reports/export/consignment-invoices-pdf', { status: invoiceStatus }, 'Rekap Tagihan Piutang PDF')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:var(--primary, #881337); color:#fff; border-color:#700f2b; font-weight:700;">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>

                <!-- Komisi Salesman -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(245,158,11,0.08); color:#d97706; border:1px solid rgba(245,158,11,0.18);">
                            <i data-lucide="coins"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Rekap Komisi &amp; Performa Sales</h3>
                            <p class="report-box-desc">Kalkulasi omzet gabungan penjualan konsinyasi dan pesanan B2B per karyawan sales beserta estimasi nominal bonus komisi.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <span class="text-xs text-ink-mute">Filter otomatis mengikuti rentang tanggal aktif toolbar.</span>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/sales-commissions', {}, 'Rekap Komisi Salesman')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>

                <!-- Riwayat Kunjungan -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(245,158,11,0.08); color:#d97706; border:1px solid rgba(245,158,11,0.18);">
                            <i data-lucide="map-pin"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Rekap Log Kunjungan Sales Lapangan</h3>
                            <p class="report-box-desc">Log kehadiran sales di toko mitra, nomor kunjungan, tipe rolling nota, status transaksi bayar langsung, dan omzet per kunjungan.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-ink-mute flex-shrink-0">Sales:</span>
                            <select class="form-input text-xs py-1 px-2 h-7 flex-1 min-w-0" x-model="salesVisitUser" style="border-radius:6px;">
                                <option value="all">Semua Salesman</option>
                                <?php foreach ($salesUsers as $su): ?>
                                    <option value="<?= htmlspecialchars((string)$su['id']) ?>"><?= htmlspecialchars((string)$su['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/sales-visits', { sales_id: salesVisitUser }, 'Rekap Log Kunjungan Sales')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- KATEGORI 5: GUDANG & PEMBELIAN -->
        <section class="space-y-3">
            <div class="flex items-center gap-2.5">
                <div class="section-icon-badge is-purple">
                    <i data-lucide="warehouse"></i>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-ink uppercase tracking-wider">5. Gudang, Stok &amp; Pembelian Vendor</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5 sm:gap-4">
                <!-- Stok & Valuasi -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(139,92,246,0.08); color:#7c3aed; border:1px solid rgba(139,92,246,0.18);">
                            <i data-lucide="boxes"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Katalog &amp; Valuasi Stok Persediaan Gudang</h3>
                            <p class="report-box-desc">Snapshot stok fisik barang jadi, bahan baku, dan kemasan, batas stok minimum, harga pokok (HPP), dan total valuasi aset gudang.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-ink-mute flex-shrink-0">Kategori:</span>
                            <select class="form-input text-xs py-1 px-2 h-7 flex-1 min-w-0" x-model="stockCategory" style="border-radius:6px;">
                                <option value="all">Semua Jenis Item</option>
                                <option value="produk">Produk Jadi (Finish Goods)</option>
                                <option value="bahan_baku">Bahan Baku Makanan</option>
                                <option value="kemasan">Bahan Kemasan / Plastik</option>
                            </select>
                        </div>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/inventory-stock', { kategori: stockCategory }, 'Valuasi Stok Gudang Excel')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Excel</span>
                        </button>
                        <button type="button" @click="downloadReport('/reports/export/inventory-stock-pdf', { kategori: stockCategory }, 'Valuasi Stok Gudang PDF')" class="btn btn-secondary btn-sm flex-1" style="height:34px; background:var(--primary, #881337); color:#fff; border-color:#700f2b; font-weight:700;">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <span>PDF</span>
                        </button>
                    </div>
                </div>

                <!-- Riwayat Opname -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(139,92,246,0.08); color:#7c3aed; border:1px solid rgba(139,92,246,0.18);">
                            <i data-lucide="clipboard-check"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Rekap Riwayat Audit Stock Opname</h3>
                            <p class="report-box-desc">Rekapitulasi seluruh dokumen hasil audit fisik gudang, nama petugas pemeriksa, total item selisih, dan nilai penyesuaian selisih HPP.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <span class="text-xs text-ink-mute">Filter otomatis mengikuti rentang tanggal aktif toolbar.</span>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/opname-history', {}, 'Rekap Riwayat Stock Opname')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>

                <!-- Pembelian Vendor -->
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(139,92,246,0.08); color:#7c3aed; border:1px solid rgba(139,92,246,0.18);">
                            <i data-lucide="package-plus"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Rekap Pembelian Vendor &amp; Hutang Dagang</h3>
                            <p class="report-box-desc">Daftar transaksi pesanan pembelian (PO) ke pemasok, total nilai belanja, status pembayaran, sisa hutang dagang, dan status barang masuk.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-ink-mute flex-shrink-0">Vendor:</span>
                            <select class="form-input text-xs py-1 px-2 h-7 flex-1 min-w-0" x-model="purchaseSupplier" style="border-radius:6px;">
                                <option value="all">Semua Pemasok</option>
                                <?php foreach ($suppliers as $sup): ?>
                                    <option value="<?= htmlspecialchars((string)$sup['id']) ?>"><?= htmlspecialchars((string)$sup['nama_pemasok']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/vendor-purchases', { supplier_id: purchaseSupplier }, 'Rekap Pembelian Vendor')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- KATEGORI 6: LOGISTIK & PENGIRIMAN -->
        <section class="space-y-3">
            <div class="flex items-center gap-2.5">
                <div class="section-icon-badge is-sky">
                    <i data-lucide="truck"></i>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-ink uppercase tracking-wider">6. Logistik &amp; Pengiriman Armada</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5 sm:gap-4">
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(14,165,233,0.08); color:#0284c7; border:1px solid rgba(14,165,233,0.18);">
                            <i data-lucide="file-check"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Rekap Log Surat Jalan Driver</h3>
                            <p class="report-box-desc">Rekapitulasi surat jalan pengantaran barang, nama driver pengantar, nomor faktur pesanan, toko tujuan, status kirim, dan waktu terima.</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-semibold text-ink-mute flex-shrink-0">Driver:</span>
                            <select class="form-input text-xs py-1 px-2 h-7 flex-1 min-w-0" x-model="deliveryDriver" style="border-radius:6px;">
                                <option value="all">Semua Driver Armada</option>
                                <?php foreach ($driverUsers as $du): ?>
                                    <option value="<?= htmlspecialchars((string)$du['id']) ?>"><?= htmlspecialchars((string)$du['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/deliveries', { driver_id: deliveryDriver }, 'Rekap Surat Jalan Pengiriman')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <!-- KATEGORI 7: SISTEM & AUDIT LOG -->
        <section class="space-y-3">
            <div class="flex items-center gap-2.5">
                <div class="section-icon-badge is-slate">
                    <i data-lucide="shield"></i>
                </div>
                <h2 class="text-sm sm:text-base font-bold text-ink uppercase tracking-wider">7. Sistem &amp; Audit Trail</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3.5 sm:gap-4">
                <div class="report-box">
                    <div class="report-box-top">
                        <div class="report-box-icon" style="background:rgba(100,116,139,0.08); color:#475569; border:1px solid rgba(100,116,139,0.18);">
                            <i data-lucide="scroll-text"></i>
                        </div>
                        <div class="min-w-0 flex-1">
                            <h3 class="report-box-title">Rekap Log Aktivitas &amp; Jejak Pengguna</h3>
                            <p class="report-box-desc">Log jejak digital pengguna (audit trail), timestamp waktu, username, peran, modul aksi, dan deskripsi detail perubahan data (Maks 5.000 log).</p>
                        </div>
                    </div>
                    <div class="report-box-meta">
                        <span class="text-xs text-ink-mute">Filter otomatis mengikuti rentang tanggal aktif toolbar.</span>
                    </div>
                    <div class="report-box-actions">
                        <button type="button" @click="downloadReport('/reports/export/activity-logs', {}, 'Log Aktivitas Sistem')" class="btn btn-secondary btn-sm w-full" style="height:34px; background:#10b981; color:#fff; border-color:#059669; font-weight:700;">
                            <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                            <span>Download Excel (.xlsx)</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

    </div>

</div>

<style>
/* Section Category Badges */
.section-icon-badge {
    width: 28px;
    height: 28px;
    border-radius: 7px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.section-icon-badge i, .section-icon-badge svg {
    width: 15px !important;
    height: 15px !important;
    stroke-width: 2 !important;
}
.section-icon-badge.is-maroon {
    background: rgba(136, 19, 55, 0.08);
    color: var(--primary, #881337);
    border: 1px solid rgba(136, 19, 55, 0.18);
}
.section-icon-badge.is-emerald {
    background: rgba(16, 185, 129, 0.08);
    color: #059669;
    border: 1px solid rgba(16, 185, 129, 0.18);
}
.section-icon-badge.is-blue {
    background: rgba(59, 130, 246, 0.08);
    color: #2563eb;
    border: 1px solid rgba(59, 130, 246, 0.18);
}
.section-icon-badge.is-amber {
    background: rgba(245, 158, 11, 0.08);
    color: #d97706;
    border: 1px solid rgba(245, 158, 11, 0.18);
}
.section-icon-badge.is-purple {
    background: rgba(139, 92, 246, 0.08);
    color: #7c3aed;
    border: 1px solid rgba(139, 92, 246, 0.18);
}
.section-icon-badge.is-sky {
    background: rgba(14, 165, 233, 0.08);
    color: #0284c7;
    border: 1px solid rgba(14, 165, 233, 0.18);
}
.section-icon-badge.is-slate {
    background: rgba(100, 116, 139, 0.08);
    color: #475569;
    border: 1px solid rgba(100, 116, 139, 0.18);
}

.dark .section-icon-badge.is-maroon {
    background: rgba(251, 113, 133, 0.12);
    color: #fb7185;
    border-color: rgba(251, 113, 133, 0.25);
}
.dark .section-icon-badge.is-emerald {
    background: rgba(16, 185, 129, 0.12);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.25);
}
.dark .section-icon-badge.is-blue {
    background: rgba(59, 130, 246, 0.12);
    color: #60a5fa;
    border-color: rgba(59, 130, 246, 0.25);
}
.dark .section-icon-badge.is-amber {
    background: rgba(245, 158, 11, 0.12);
    color: #fbbf24;
    border-color: rgba(245, 158, 11, 0.25);
}
.dark .section-icon-badge.is-purple {
    background: rgba(139, 92, 246, 0.12);
    color: #a78bfa;
    border-color: rgba(139, 92, 246, 0.25);
}
.dark .section-icon-badge.is-sky {
    background: rgba(14, 165, 233, 0.12);
    color: #38bdf8;
    border-color: rgba(14, 165, 233, 0.25);
}
.dark .section-icon-badge.is-slate {
    background: rgba(148, 163, 184, 0.12);
    color: #94a3b8;
    border-color: rgba(148, 163, 184, 0.25);
}

/* Filter Toolbar Premium DNA */
.report-filter-card {
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: var(--rounded-lg, 12px);
    padding: 12px 14px;
    box-shadow: var(--shadow-1);
}
.report-filter-dock {
    display: inline-flex;
    align-items: center;
    gap: 3px;
    padding: 3px;
    background: var(--color-canvas-soft, #f1f5f9);
    border: 1px solid var(--color-hairline);
    border-radius: 9px;
    overflow-x: auto;
    scrollbar-width: none;
    -webkit-overflow-scrolling: touch;
    max-width: 100%;
    cursor: grab;
    cursor: -webkit-grab;
    user-select: none;
}
.report-filter-dock::-webkit-scrollbar {
    display: none;
}
.report-filter-dock.is-dragging {
    cursor: grabbing !important;
    cursor: -webkit-grabbing !important;
    user-select: none;
}
.report-filter-dock.is-dragging * {
    cursor: grabbing !important;
    cursor: -webkit-grabbing !important;
    user-select: none;
}
.report-filter-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 5px 11px;
    font-size: 11.5px;
    font-weight: 700;
    border-radius: 6px;
    border: 1px solid transparent;
    white-space: nowrap;
    cursor: pointer;
    background: transparent;
    color: var(--color-ink-mute);
    transition: all 0.15s ease;
    user-select: none;
    height: 28px;
    flex-shrink: 0;
}
.report-filter-btn:hover {
    color: var(--color-ink);
    background: rgba(255, 255, 255, 0.7);
}
.dark .report-filter-btn:hover {
    background: rgba(255, 255, 255, 0.08);
}
.report-filter-btn.is-active {
    background: var(--primary, #881337) !important;
    color: #ffffff !important;
    border-color: var(--primary, #881337) !important;
    box-shadow: 0 1px 4px rgba(136, 19, 55, 0.28) !important;
}
.report-date-range-box {
    display: flex;
    align-items: center;
    gap: 6px;
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline);
    border-radius: 9px;
    padding: 3px 8px;
}
.report-date-range-box-mobile {
    background: var(--color-canvas-soft, #f8fafc);
    border: 1px solid var(--color-hairline);
    border-radius: 8px;
    padding: 6px 8px;
    display: flex;
    flex-direction: column;
}
.report-date-input {
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: 6px;
    height: 28px;
    padding: 0 8px;
    font-size: 11.5px;
    font-weight: 600;
    font-family: var(--font-mono, monospace);
    color: var(--color-ink);
    outline: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.report-date-input:focus {
    border-color: var(--primary, #881337);
    box-shadow: 0 0 0 2px rgba(136, 19, 55, 0.15);
}

/* Badge Master Rekap */
.report-pill-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 24px;
    padding: 0 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    line-height: 1;
    white-space: nowrap;
    width: fit-content;
    max-width: 100%;
    margin-bottom: 6px;
    background: rgba(136, 19, 55, 0.08);
    border: 1px solid rgba(136, 19, 55, 0.2);
    color: var(--primary, #881337);
    box-sizing: border-box;
}
.report-pill-badge .badge-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--primary, #881337);
    flex-shrink: 0;
}
.dark .report-pill-badge {
    background: rgba(251, 113, 133, 0.12);
    border-color: rgba(251, 113, 133, 0.28);
    color: #fb7185;
}
.dark .report-pill-badge .badge-dot {
    background: #fb7185;
}

/* Card Boxes */
.report-box {
    background: var(--color-canvas);
    border: 1px solid var(--color-hairline);
    border-radius: var(--rounded-lg, 12px);
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    gap: 14px;
    box-shadow: var(--shadow-1);
    transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}
.report-box:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-2, 0 6px 16px -2px rgba(0,0,0,0.08));
    border-color: rgba(136, 19, 55, 0.3);
}
.report-box-top {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}
.report-box-icon {
    width: 36px;
    height: 36px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.report-box-icon i, .report-box-icon svg {
    width: 18px !important;
    height: 18px !important;
    stroke-width: 2 !important;
}
.report-box-title {
    font-size: 13.5px;
    font-weight: 700;
    color: var(--color-ink);
    line-height: 1.35;
    margin-bottom: 3px;
}
.report-box-desc {
    font-size: 11.5px;
    color: var(--color-ink-mute);
    line-height: 1.45;
    margin: 0;
}
.report-box-meta {
    padding-top: 4px;
    min-height: 28px;
}
.report-box-actions {
    display: flex;
    gap: 8px;
    align-items: center;
    padding-top: 10px;
    border-top: 1px solid var(--color-hairline);
}

@media (max-width: 640px) {
    .report-box {
        padding: 14px 14px;
        gap: 12px;
    }
    .report-box-title {
        font-size: 13px;
    }
    .report-box-desc {
        font-size: 11px;
    }
    .report-box-actions {
        padding-top: 8px;
    }
}
</style>

<script>
function reportHubApp() {
    return {
        activePreset: 'this_month',
        startDate: '<?= $thisMonthStart ?>',
        endDate: '<?= $today ?>',

        // Specific Card Filters
        cashFlowAccount: 'all',
        cashTxAccount: 'all',
        cashTxType: 'all',
        orderStatusBayar: 'all',
        orderStatusKirim: 'all',
        consignmentStore: 'all',
        invoiceStatus: 'belum_lunas',
        salesVisitUser: 'all',
        stockCategory: 'all',
        purchaseSupplier: 'all',
        deliveryDriver: 'all',

        init() {
            this.setupPresetDragScroll();
        },

        setupPresetDragScroll() {
            this.$nextTick(() => {
                const track = this.$refs.presetTrack;
                if (!track) return;

                let isDown = false;
                let startX = 0;
                let scrollLeft = 0;
                let hasMoved = false;

                track.addEventListener('mousedown', (e) => {
                    isDown = true;
                    hasMoved = false;
                    startX = e.pageX - track.offsetLeft;
                    scrollLeft = track.scrollLeft;
                    track.classList.add('is-dragging');
                });

                window.addEventListener('mousemove', (e) => {
                    if (!isDown) return;
                    const x = e.pageX - track.offsetLeft;
                    const walk = (x - startX) * 1.25;
                    if (Math.abs(walk) > 3) {
                        hasMoved = true;
                        e.preventDefault();
                        track.scrollLeft = scrollLeft - walk;
                    }
                });

                window.addEventListener('mouseup', () => {
                    if (!isDown) return;
                    isDown = false;
                    track.classList.remove('is-dragging');
                    if (hasMoved) {
                        setTimeout(() => { hasMoved = false; }, 50);
                    }
                });

                track.addEventListener('click', (e) => {
                    if (hasMoved) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                    }
                }, true);

                // Mouse wheel horizontal scroll support
                track.addEventListener('wheel', (e) => {
                    if (e.deltaY !== 0 && track.scrollWidth > track.clientWidth) {
                        e.preventDefault();
                        track.scrollLeft += e.deltaY;
                    }
                }, { passive: false });
            });
        },

        setPreset(preset) {
            this.activePreset = preset;
            const today = '<?= $today ?>';
            
            if (preset === 'today') {
                this.startDate = today;
                this.endDate = today;
            } else if (preset === '7days') {
                const d = new Date();
                d.setDate(d.getDate() - 6);
                this.startDate = d.toISOString().split('T')[0];
                this.endDate = today;
            } else if (preset === 'this_month') {
                this.startDate = '<?= $thisMonthStart ?>';
                this.endDate = today;
            } else if (preset === 'last_month') {
                this.startDate = '<?= $lastMonthStart ?>';
                this.endDate = '<?= $lastMonthEnd ?>';
            } else if (preset === 'this_year') {
                this.startDate = '<?= $thisYearStart ?>';
                this.endDate = today;
            }
        },

        url(baseRoute, extraParams = {}) {
            const params = new URLSearchParams({
                start_date: this.startDate,
                end_date: this.endDate,
                ...extraParams
            });
            const base = '<?= rtrim(Router::url(""), "/") ?>';
            const cleanRoute = '/' + String(baseRoute).replace(/^\/+/, '');
            return (base ? base : '') + cleanRoute + '?' + params.toString();
        },

        async downloadReport(baseRoute, extraParams = {}, reportLabel = 'Laporan') {
            const fullUrl = this.url(baseRoute, extraParams);
            
            if (window.AppAction && typeof window.AppAction.show === 'function') {
                window.AppAction.show('Menyiapkan ' + reportLabel + '...', 'Mengompilasi data dan memproses berkas...');
            }

            try {
                const response = await fetch(fullUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const contentType = response.headers.get('content-type') || '';

                if (!response.ok || contentType.includes('application/json')) {
                    let errorMsg = 'Terjadi kesalahan saat memproses laporan.';
                    try {
                        const errData = await response.json();
                        errorMsg = errData.message || errorMsg;
                    } catch (e) {
                        const txt = await response.text();
                        if (txt) errorMsg = txt;
                    }
                    if (window.AppAction && typeof window.AppAction.error === 'function') {
                        window.AppAction.error('Gagal Mengunduh!', errorMsg, 3500);
                    } else {
                        alert('Gagal Mengunduh: ' + errorMsg);
                    }
                    return;
                }

                // Tentukan ekstensi default jika tidak terdeteksi dari header
                const defaultExt = (baseRoute.includes('pdf') || contentType.includes('pdf')) ? '.pdf' : '.xlsx';
                let filename = reportLabel + defaultExt;
                
                const disposition = response.headers.get('content-disposition');
                if (disposition) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i.exec(disposition);
                    if (matches != null && matches[1]) {
                        filename = matches[1].replace(/['"]/g, '').trim();
                    }
                }

                const blob = await response.blob();
                const blobUrl = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.style.display = 'none';
                a.href = blobUrl;
                a.download = filename;
                document.body.appendChild(a);
                a.click();
                setTimeout(() => {
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(blobUrl);
                }, 300);

                if (window.AppAction && typeof window.AppAction.success === 'function') {
                    window.AppAction.success('Berhasil Diunduh! ✨', filename, 1800);
                }
            } catch (err) {
                if (window.AppAction && typeof window.AppAction.error === 'function') {
                    window.AppAction.error('Gagal Mengunduh!', err.message || 'Koneksi terputus saat mengunduh berkas.', 3500);
                } else {
                    alert('Gagal Mengunduh: ' + err.message);
                }
            }
        }
    };
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.lucide) {
        lucide.createIcons();
    }
});
</script>

<?php
$content = ob_get_clean();
require ROOT_PATH . '/views/layouts/master.php';
?>

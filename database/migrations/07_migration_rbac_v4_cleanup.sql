-- ==============================================================================
-- KEREN SNACK ERP - RBAC MIGRATION V4 (CLEANUP & RESTRUCTURING)
-- File: database/07_migration_rbac_v4_cleanup.sql
-- ==============================================================================

-- 1. Hapus izin_peran dan izin_pengguna yang merujuk pada kode izin yang akan dihapus / diganti
DELETE FROM public.izin_peran 
WHERE izin_id IN (
    SELECT id FROM public.izin 
    WHERE kode_izin IN (
        'absensi.create', 'payroll.close',
        'kas.approve', 'kas.create', 'kas.view', 'piutang.collect',
        'surat_jalan.manifest', 'surat_jalan.update',
        'pesanan.approve', 'pesanan.create',
        'produksi.create', 'production.view_all', 'production.create', 'production.edit',
        'cash.view_assigned', 'consignment.confirm',
        'pos.view', 'pos.checkout'
    )
);

DELETE FROM public.izin_pengguna 
WHERE izin_id IN (
    SELECT id FROM public.izin 
    WHERE kode_izin IN (
        'absensi.create', 'payroll.close',
        'kas.approve', 'kas.create', 'kas.view', 'piutang.collect',
        'surat_jalan.manifest', 'surat_jalan.update',
        'pesanan.approve', 'pesanan.create',
        'produksi.create', 'production.view_all', 'production.create', 'production.edit',
        'cash.view_assigned', 'consignment.confirm',
        'pos.view', 'pos.checkout'
    )
);

-- 2. Hapus baris izin legacy dari master public.izin
DELETE FROM public.izin 
WHERE kode_izin IN (
    'absensi.create', 'payroll.close',
    'kas.approve', 'kas.create', 'kas.view', 'piutang.collect',
    'surat_jalan.manifest', 'surat_jalan.update',
    'pesanan.approve', 'pesanan.create',
    'produksi.create', 'production.view_all', 'production.create', 'production.edit',
    'cash.view_assigned', 'consignment.confirm',
    'pos.view', 'pos.checkout'
);

-- 3. Upsert 63 Izin Standar Bersih (10 Grup Terpadu)
INSERT INTO public.izin (kode_izin, nama_izin, grup_izin, deskripsi) VALUES
-- Grup 1: Penjualan & Kasir POS (3 izin)
('pos.pos', 'Akses Kasir POS & Transaksi', 'Penjualan & Kasir POS', 'Buka antarmuka kasir ritel, scan barcode, dan proses pembayaran struk'),
('pos.discount', 'Diskon Manual Kasir', 'Penjualan & Kasir POS', 'Menginput diskon manual nominal atau persentase di kasir POS'),
('pos.void_item', 'Hapus Item di Keranjang Kasir', 'Penjualan & Kasir POS', 'Menghapus/membatalkan item yang telah di-scan pada transaksi berjalan'),

-- Grup 2: Pesanan Pelanggan (B2B) (9 izin)
('orders.view_all', 'Lihat Semua Pesanan B2B', 'Pesanan Pelanggan (B2B)', 'Melihat daftar seluruh pesanan/faktur toko dari semua wilayah'),
('orders.view_assigned', 'Lihat Pesanan Toko Binaan', 'Pesanan Pelanggan (B2B)', 'Hanya melihat pesanan dari toko pelanggan binaan sendiri'),
('orders.create', 'Buat Pesanan B2B', 'Pesanan Pelanggan (B2B)', 'Membuat pesanan baru / draft faktur penjualan toko pelanggan'),
('orders.edit_all', 'Edit Semua Pesanan B2B', 'Pesanan Pelanggan (B2B)', 'Mengubah draf faktur pesanan toko pelanggan mana saja sebelum dikirim'),
('orders.edit_assigned', 'Edit Pesanan Toko Binaan', 'Pesanan Pelanggan (B2B)', 'Mengubah draf faktur pesanan hanya untuk toko binaan sendiri sebelum dikirim'),
('orders.pay', 'Catat Pelunasan Faktur', 'Pesanan Pelanggan (B2B)', 'Mencatat pembayaran/cicilan tunai atau transfer faktur penjualan'),
('orders.cancel', 'Batalkan Faktur (Void)', 'Pesanan Pelanggan (B2B)', 'Membatalkan / void faktur pesanan penjualan toko'),
('orders.delivery_status', 'Ubah Status Pengiriman Pesanan', 'Pesanan Pelanggan (B2B)', 'Mengubah alur status pesanan (Packing, Dikirim, Selesai)'),
('orders.print_invoice', 'Cetak Faktur / Invoice', 'Pesanan Pelanggan (B2B)', 'Mencetak dokumen resmi faktur / invoice penjualan'),

-- Grup 3: Konsinyasi Rak Toko (12 izin)
('consignment.view_all', 'Lihat Semua Rak Konsinyasi', 'Konsinyasi Rak Toko', 'Melihat portal & monitoring rak seluruh toko mitra'),
('consignment.view_assigned', 'Lihat Rak Toko Binaan', 'Konsinyasi Rak Toko', 'Hanya memantau rak toko mitra yang dialokasikan ke dirinya'),
('consignment.opname_all', 'Opname Rak Semua Toko', 'Konsinyasi Rak Toko', 'Melakukan opname fisik rak di toko mitra manapun'),
('consignment.opname_assigned', 'Opname Rak Toko Binaan', 'Konsinyasi Rak Toko', 'Melakukan opname fisik hanya pada toko binaan sendiri'),
('consignment.piutang', 'Catat Bayar Piutang Konsinyasi', 'Konsinyasi Rak Toko', 'Menerima pembayaran tagihan laku konsinyasi dari toko'),
('consignment.assignment', 'Atur Penugasan Rute & Sales', 'Konsinyasi Rak Toko', 'Mengatur alokasi pembagian toko konsinyasi ke Sales-Driver'),
('consignment.reports_all', 'Laporan Laku Semua Toko', 'Konsinyasi Rak Toko', 'Melihat rekap omset dan kuantiti laku titip seluruh toko'),
('consignment.reports_assigned', 'Laporan Laku Toko Binaan', 'Konsinyasi Rak Toko', 'Melihat rekap omset & laku hanya untuk toko binaan sendiri'),
('consignment.komisi_all', 'Rekap Komisi Semua Sales', 'Konsinyasi Rak Toko', 'Melihat total komisi penjualan seluruh sales'),
('consignment.komisi_self', 'Cek Komisi Penjualan Sendiri', 'Konsinyasi Rak Toko', 'Melihat perhitungan komisi penjualan milik sendiri'),
('consignment.kerugian', 'Rekap Barang Rusak/Kadaluarsa', 'Konsinyasi Rak Toko', 'Merekap nilai kerugian snack rusak/expired di rak mitra'),
('consignment.early_warning', 'Notifikasi Early Warning', 'Konsinyasi Rak Toko', 'Memantau peringatan stok macet dan mendekati tanggal expired'),

-- Grup 4: Logistik & Distribusi (6 izin)
('deliveries.view_all', 'Lihat Semua Pengiriman', 'Logistik & Distribusi', 'Memantau seluruh surat jalan dan pengiriman semua armada'),
('deliveries.view_assigned', 'Lihat Tugas Kirim Sendiri', 'Logistik & Distribusi', 'Hanya melihat surat jalan di mana dirinya menjadi kurir/driver'),
('deliveries.create', 'Buat Surat Jalan Baru', 'Logistik & Distribusi', 'Menyusun manifest rute dan muatan barang kiriman (loading)'),
('deliveries.update_all', 'Update POD Semua Surat Jalan', 'Logistik & Distribusi', 'Mengubah status kirim & upload foto POD untuk surat jalan apa saja'),
('deliveries.update_assigned', 'Update POD Tugas Sendiri', 'Logistik & Distribusi', 'Mengubah status & upload bukti POD hanya untuk tugas miliknya'),
('deliveries.print', 'Cetak Surat Jalan', 'Logistik & Distribusi', 'Mencetak dokumen fisik surat jalan resmi pengiriman'),

-- Grup 5: Gudang & Persediaan (3 izin)
('inventory.view_all', 'Monitoring Stok Gudang', 'Gudang & Persediaan', 'Melihat katalog persediaan bahan mentah & barang jadi realtime'),
('inventory.opname', 'Penyesuaian Stok Opname', 'Gudang & Persediaan', 'Koreksi selisih stok fisik vs sistem di gudang pusat'),
('inventory.waste', 'Catat Barang Rusak/Waste', 'Gudang & Persediaan', 'Input bahan rusak, plastik gagal segel, atau sampel operasional'),

-- Grup 6: Pembelian & Vendor (3 izin)
('purchases.view', 'Riwayat Pembelian Vendor', 'Pembelian & Vendor', 'Melihat daftar faktur PO pengadaan bahan baku'),
('purchases.create', 'Input Pembelian Bahan Baru', 'Pembelian & Vendor', 'Mencatat pembelian bahan baku mentah, kemasan, dan bumbu'),
('purchases.edit', 'Edit Faktur Pembelian', 'Pembelian & Vendor', 'Mengubah data faktur pembelian bahan dari supplier'),

-- Grup 7: Keuangan & Kas (6 izin)
('cash.view_all', 'Lihat Semua Akun Kas & Bank', 'Keuangan & Kas', 'Melihat saldo seluruh buku kas tunai dan rekening bank'),
('cash.inflow', 'Input Kas Masuk', 'Keuangan & Kas', 'Mencatat penerimaan uang kas operasional di luar POS'),
('cash.outflow', 'Input Kas Keluar', 'Keuangan & Kas', 'Mengajukan pengeluaran biaya operasional, bensin, dan tol'),
('cash.transfer', 'Transfer Antar Rekening Kas', 'Keuangan & Kas', 'Memindahkan dana antar rekening kas internal atau bank'),
('cash.reports', 'Laporan Arus Kas & Valuasi', 'Keuangan & Kas', 'Melihat laporan mutasi arus kas, valuasi aset, dan laba berjalan'),
('cash.manage_accounts', 'Kelola Akun Kas / Bank', 'Keuangan & Kas', 'Menambah, mengubah nama, atau menonaktifkan akun kas/bank'),

-- Grup 8: Master Data (12 izin)
('master.products_view', 'Lihat Master Produk & BOM', 'Master Data', 'Melihat katalog barang jadi, pretelan, dan resep BOM'),
('master.products_manage', 'Kelola Resep Produk Jual', 'Master Data', 'Menambah, mengedit, menghapus produk barang jadi & BOM'),
('master.materials_manage', 'Kelola Bahan Baku & Kemasan', 'Master Data', 'Mengelola master bahan curah kiloan, bumbu, plastik, cup'),
('master.pricing_view', 'Lihat Matriks Level Harga', 'Master Data', 'Melihat tabel harga jual bertingkat level 1 s/d 28'),
('master.pricing_manage', 'Kelola Matriks Level Harga', 'Master Data', 'Mengubah nominal harga tiap level dan diskon grup pelanggan'),
('master.customers_view_all', 'Lihat Semua Toko Pelanggan', 'Master Data', 'Melihat seluruh direktori mitra toko pelanggan & wilayah rute'),
('master.customers_view_assigned', 'Lihat Toko Binaan Sendiri', 'Master Data', 'Hanya melihat profil toko pelanggan yang dibina sendiri'),
('master.customers_manage', 'Kelola Toko & Wilayah Rute', 'Master Data', 'Menambah, mengedit mitra toko, grup diskon, dan master rute'),
('master.suppliers_view', 'Lihat Data Pemasok Vendor', 'Master Data', 'Melihat direktori pemasok vendor bahan baku'),
('master.suppliers_manage', 'Kelola Data Pemasok Vendor', 'Master Data', 'Menambah dan mengedit data vendor supplier'),
('master.employees_view', 'Lihat Data Karyawan & Gaji', 'Master Data', 'Melihat direktori data karyawan & skema tarif borongan'),
('master.employees_manage', 'Kelola Karyawan & Upah', 'Master Data', 'Menambah, mengedit data pegawai, tarif upah & posisi tugas'),

-- Grup 9: Manajemen Eksekutif (3 izin)
('owner.dashboard', 'Dashboard Eksekutif & Ringkasan Laba', 'Manajemen Eksekutif', 'Akses dashboard omset total, profit margin, dan live stream'),
('owner.approval_cash', 'Approval Kas Keluar', 'Manajemen Eksekutif', 'Menyetujui atau menolak pengajuan pengeluaran kas operasional'),
('owner.approval_delivery', 'Approval Pengiriman Khusus', 'Manajemen Eksekutif', 'Otorisasi surat jalan bernilai besar atau penyesuaian khusus'),

-- Grup 10: Hak Akses & Sistem (6 izin)
('rbac.users_view', 'Lihat Daftar Pengguna', 'Hak Akses & Sistem', 'Melihat daftar seluruh akun pengguna sistem'),
('rbac.users_manage', 'Kelola Akun Pengguna', 'Hak Akses & Sistem', 'Membuat user baru, edit data/password, suspend & hapus akun'),
('rbac.roles_manage', 'Kelola Master Role', 'Hak Akses & Sistem', 'Mengelola nama dan deskripsi jabatan (role dinamis)'),
('rbac.permissions_manage', 'Loket Izin & Matriks RBAC', 'Hak Akses & Sistem', 'Mengatur loket izin kustom pengguna, default role & edit massal'),
('system.blueprint', 'Akses System Blueprint', 'Hak Akses & Sistem', 'Melihat arsitektur AI, visual ERD & prompt generator'),
('system.activity_log', 'Audit Trail & Log Aktivitas Sistem', 'Hak Akses & Sistem', 'Memeriksa histori mutasi data dan log aktivitas seluruh pengguna')
ON CONFLICT (kode_izin) DO UPDATE SET
    nama_izin = EXCLUDED.nama_izin,
    grup_izin = EXCLUDED.grup_izin,
    deskripsi = EXCLUDED.deskripsi;

-- 4. Pemetaan Ulang Default Role Matrix (izin_peran)
DO $$
DECLARE
    v_owner_id UUID;
    v_admin_id UUID;
    v_sales_id UUID;
    v_driver_id UUID;
BEGIN
    SELECT id INTO v_owner_id FROM public.peran WHERE nama_peran = 'owner';
    SELECT id INTO v_admin_id FROM public.peran WHERE nama_peran = 'admin';
    SELECT id INTO v_sales_id FROM public.peran WHERE nama_peran = 'sales';
    SELECT id INTO v_driver_id FROM public.peran WHERE nama_peran = 'driver';

    -- Bersihkan izin_peran lama untuk default role
    DELETE FROM public.izin_peran WHERE peran_id IN (v_owner_id, v_admin_id, v_sales_id, v_driver_id);

    -- ── A. DEFAULT ROLE: OWNER ──
    IF v_owner_id IS NOT NULL THEN
        INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
        SELECT v_owner_id, id, TRUE FROM public.izin
        WHERE kode_izin IN (
            'pos.pos', 'pos.discount', 'pos.void_item',
            'orders.view_all', 'orders.create', 'orders.edit_all', 'orders.pay', 'orders.cancel', 'orders.delivery_status', 'orders.print_invoice',
            'consignment.view_all', 'consignment.opname_all', 'consignment.piutang', 'consignment.assignment', 'consignment.reports_all', 'consignment.komisi_all', 'consignment.kerugian', 'consignment.early_warning',
            'deliveries.view_all', 'deliveries.create', 'deliveries.update_all', 'deliveries.print',
            'inventory.view_all', 'inventory.opname', 'inventory.waste',
            'purchases.view', 'purchases.create', 'purchases.edit',
            'cash.view_all', 'cash.inflow', 'cash.outflow', 'cash.transfer', 'cash.reports', 'cash.manage_accounts',
            'master.products_view', 'master.products_manage', 'master.materials_manage', 'master.pricing_view', 'master.pricing_manage',
            'master.customers_view_all', 'master.customers_manage', 'master.suppliers_view', 'master.suppliers_manage', 'master.employees_view', 'master.employees_manage',
            'owner.dashboard', 'owner.approval_cash', 'owner.approval_delivery',
            'rbac.users_view', 'rbac.users_manage', 'rbac.roles_manage', 'rbac.permissions_manage', 'system.blueprint', 'system.activity_log'
        );
    END IF;

    -- ── B. DEFAULT ROLE: ADMIN ──
    IF v_admin_id IS NOT NULL THEN
        INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
        SELECT v_admin_id, id, TRUE FROM public.izin
        WHERE kode_izin IN (
            'pos.pos', 'pos.discount', 'pos.void_item',
            'orders.view_all', 'orders.create', 'orders.edit_all', 'orders.pay', 'orders.delivery_status', 'orders.print_invoice',
            'consignment.view_all', 'consignment.opname_all', 'consignment.piutang', 'consignment.assignment', 'consignment.reports_all', 'consignment.komisi_all', 'consignment.kerugian', 'consignment.early_warning',
            'deliveries.view_all', 'deliveries.create', 'deliveries.update_all', 'deliveries.print',
            'inventory.view_all', 'inventory.opname', 'inventory.waste',
            'purchases.view', 'purchases.create', 'purchases.edit',
            'cash.view_all', 'cash.inflow', 'cash.outflow', 'cash.transfer', 'cash.reports',
            'master.products_view', 'master.materials_manage', 'master.pricing_view',
            'master.customers_view_all', 'master.customers_manage', 'master.suppliers_view', 'master.employees_view',
            'rbac.users_view', 'system.activity_log'
        );
    END IF;

    -- ── C. DEFAULT ROLE: SALES ──
    IF v_sales_id IS NOT NULL THEN
        INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
        SELECT v_sales_id, id, TRUE FROM public.izin
        WHERE kode_izin IN (
            'pos.pos',
            'orders.view_assigned', 'orders.create', 'orders.edit_assigned', 'orders.pay', 'orders.print_invoice',
            'consignment.view_assigned', 'consignment.opname_assigned', 'consignment.piutang', 'consignment.reports_assigned', 'consignment.komisi_self', 'consignment.early_warning',
            'deliveries.view_assigned', 'deliveries.update_assigned', 'deliveries.print',
            'master.products_view', 'master.pricing_view', 'master.customers_view_assigned', 'master.customers_manage'
        );
    END IF;

    -- ── D. DEFAULT ROLE: DRIVER ──
    IF v_driver_id IS NOT NULL THEN
        INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
        SELECT v_driver_id, id, TRUE FROM public.izin
        WHERE kode_izin IN (
            'deliveries.view_assigned', 'deliveries.update_assigned', 'deliveries.print',
            'consignment.view_assigned', 'consignment.opname_assigned', 'consignment.piutang',
            'orders.view_assigned', 'orders.pay',
            'master.customers_view_assigned'
        );
    END IF;

END $$;

-- ==============================================================================
-- SEED DATA 01: PERAN & IZIN (RBAC) - KEREN SNACK
-- Catatan: Sales & Driver adalah 1 peran terpadu (Sales-Driver / Canvaser)
-- ==============================================================================

-- 1. Insert Peran Standar
INSERT INTO public.peran (id, nama_peran, deskripsi) VALUES
('11111111-1111-1111-1111-111111111101', 'owner', 'Owner & Super Administrator Sistem KEREN Snack'),
('11111111-1111-1111-1111-111111111102', 'admin', 'Administrator Toko & Kasir Penjualan'),
('11111111-1111-1111-1111-111111111103', 'mandor', 'Mandor Produksi & Pengawas Pengemasan'),
('11111111-1111-1111-1111-111111111104', 'sales_driver', 'Sales-Driver / Canvaser Logistik & Penjualan Rute Toko')
ON CONFLICT (nama_peran) DO UPDATE SET deskripsi = EXCLUDED.deskripsi;

-- 2. Insert Master Izin Granular
INSERT INTO public.izin (kode_izin, nama_izin, grup_izin, deskripsi) VALUES
('kas.approve', 'Persetujuan Arus Kas Keluar', 'Keuangan', 'Menyetujui draf pengeluaran kas operasional'),
('kas.view', 'Melihat Laporan Arus Kas', 'Keuangan', 'Melihat rekap kas dan saldo akun kas'),
('kas.create', 'Input Pengeluaran Kas', 'Keuangan', 'Mengajukan draf biaya bensin/tol/lapangan'),
('pesanan.create', 'Membuat Pesanan Penjualan', 'Penjualan', 'Membuat draft transaksi invoice penjualan di rute toko'),
('pesanan.approve', 'Menyetujui Pesanan Penjualan', 'Penjualan', 'Menyetujui penjualan tempo atau piutang'),
('surat_jalan.update', 'Update Status Pengiriman & POD', 'Logistik', 'Sales-Driver konfirmasi barang sampai & upload foto bukti'),
('surat_jalan.manifest', 'Menerima Manifest Rute Harian', 'Logistik', 'Menerima list rute & loading barang harian di bot Telegram'),
('piutang.collect', 'Catat Tagihan Toko', 'Keuangan', 'Sales-Driver menerima uang pelunasan piutang di toko'),
('produksi.create', 'Catat Hasil Produksi Borongan', 'Produksi', 'Mandor input hasil packing harian karyawan'),
('absensi.create', 'Catat Presensi Karyawan', 'HR', 'Mandor input kehadiran harian'),
('payroll.close', 'Tutup Buku Gaji', 'HR', 'Owner melakukan tutup buku payroll mingguan/bulanan'),
('stok.opname', 'Penyesuaian Stok Opname', 'Gudang', 'Input selisih atau barang rusak/hilang'),
('stok.view', 'Cek Stok Real-Time', 'Gudang', 'Cek sisa stok via chat bot di rute')
ON CONFLICT (kode_izin) DO NOTHING;

-- 3. Insert Akun Kas Default
INSERT INTO public.akun_kas (id, nama_akun, nomor_rekening, atas_nama, saldo_saat_ini) VALUES
('22222222-2222-2222-2222-222222222201', 'Kasir Utama Toko (Tunai)', '-', 'Kasir KEREN Snack', 0.00),
('22222222-2222-2222-2222-222222222202', 'BCA Bisnis KEREN Snack', '1234567890', 'Owner KEREN Snack', 0.00)
ON CONFLICT DO NOTHING;

-- 4. Pengaturan Sistem Default
INSERT INTO public.pengaturan_sistem (kunci, nilai, deskripsi) VALUES
('nama_toko', 'KEREN SNACK', 'Nama usaha resmi'),
('jam_masuk_kerja', '08:00', 'Jam standar masuk kerja harian'),
('komisi_sales_driver_default', '2.50', 'Persentase default komisi penjualan sales-driver (%)'),
('versi_schema', '1.0.0', 'Versi skema database Supabase')
ON CONFLICT (kunci) DO NOTHING;

-- ==============================================================================
-- SEED DATA 01: PERAN & IZIN (RBAC)
-- Status: Data referensial sistem — aman untuk public release
-- ==============================================================================

-- 1. Insert Peran Standar
INSERT INTO public.peran (id, nama_peran, deskripsi) VALUES
('11111111-1111-1111-1111-111111111101', 'owner',  'Owner & Pimpinan Bisnis'),
('11111111-1111-1111-1111-111111111102', 'admin',  'Administrator Operasional & Kasir'),
('11111111-1111-1111-1111-111111111103', 'mandor', 'Mandor Produksi & Pengawas Pengemasan'),
('41676225-d31e-42b8-80d5-26d93762e014', 'sales',  'Sales Lapangan & Penjualan Rute Toko'),
('51676225-d31e-42b8-80d5-26d93762e015', 'driver', 'Driver Pengantaran Logistik Armada')
ON CONFLICT (nama_peran) DO UPDATE SET deskripsi = EXCLUDED.deskripsi;

-- 2. Insert Master Izin Granular
INSERT INTO public.izin (kode_izin, nama_izin, grup_izin, deskripsi) VALUES
('kas.approve',        'Persetujuan Arus Kas Keluar',     'Keuangan', 'Menyetujui draf pengeluaran kas operasional'),
('kas.view',           'Melihat Laporan Arus Kas',         'Keuangan', 'Melihat rekap kas dan saldo akun kas'),
('kas.create',         'Input Pengeluaran Kas',            'Keuangan', 'Mengajukan draf biaya bensin/tol/lapangan'),
('pesanan.create',     'Membuat Pesanan Penjualan',        'Penjualan', 'Membuat draft transaksi invoice penjualan di rute toko'),
('pesanan.approve',    'Menyetujui Pesanan Penjualan',     'Penjualan', 'Menyetujui penjualan tempo atau piutang'),
('surat_jalan.update', 'Update Status Pengiriman & POD',   'Logistik',  'Konfirmasi barang sampai & upload foto bukti'),
('surat_jalan.manifest','Menerima Manifest Rute Harian',  'Logistik',  'Menerima list rute & loading barang harian'),
('piutang.collect',    'Catat Tagihan Toko',               'Keuangan', 'Menerima uang pelunasan piutang di toko'),
('produksi.create',    'Catat Hasil Produksi Borongan',    'Produksi',  'Mandor input hasil packing harian karyawan'),
('absensi.create',     'Catat Presensi Karyawan',          'HR',        'Mandor input kehadiran harian'),
('payroll.close',      'Tutup Buku Gaji',                  'HR',        'Owner melakukan tutup buku payroll mingguan/bulanan'),
('stok.opname',        'Penyesuaian Stok Opname',          'Gudang',    'Input selisih atau barang rusak/hilang'),
('stok.view',          'Cek Stok Real-Time',               'Gudang',    'Cek sisa stok via aplikasi')
ON CONFLICT (kode_izin) DO NOTHING;

-- 3. Insert Akun Kas Default (placeholder — ubah via Settings setelah setup)
INSERT INTO public.akun_kas (id, nama_akun, nomor_rekening, atas_nama, saldo_saat_ini) VALUES
('22222222-2222-2222-2222-222222222201', 'Kasir Utama Toko (Tunai)', '-',    'Kasir Toko',    0.00),
('22222222-2222-2222-2222-222222222202', 'Rekening Bank Usaha',      '-',    'Pemilik Usaha', 0.00)
ON CONFLICT (id) DO UPDATE SET
    nama_akun      = EXCLUDED.nama_akun,
    nomor_rekening = EXCLUDED.nomor_rekening,
    atas_nama      = EXCLUDED.atas_nama,
    saldo_saat_ini = 0.00;

-- 4. Pengaturan Sistem Default
INSERT INTO public.pengaturan_sistem (kunci, nilai, deskripsi) VALUES
('jam_masuk_kerja',      '08:00', 'Jam standar masuk kerja harian'),
('komisi_sales_default', '2.50',  'Persentase default komisi penjualan sales (%)'),
('versi_schema',         '1.0.0', 'Versi skema database')
ON CONFLICT (kunci) DO NOTHING;

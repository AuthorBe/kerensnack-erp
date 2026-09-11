-- ==============================================================================
-- SEED DATA 02: KELOMPOK UPAH BORONGAN & MASTER KARYAWAN
-- Posisi Lapangan: Sales-Driver (Canvaser Terpadu)
-- ==============================================================================

-- 1. Kelompok Upah Borongan Pengemasan
INSERT INTO public.kelompok_upah_borongan (id, id_legacy, nama_kelompok, upah_per_bungkus, keterangan) VALUES
('33333333-3333-3333-3333-333333333305', 5, 'Kelompok 600', 600.00, 'Tarif borongan pack bungkus Rp 600/pcs'),
('33333333-3333-3333-3333-333333333306', 6, 'Kelompok 500', 500.00, 'Tarif borongan pack bungkus Rp 500/pcs'),
('33333333-3333-3333-3333-333333333307', 7, 'Kelompok 400', 400.00, 'Tarif borongan pack bungkus Rp 400/pcs'),
('33333333-3333-3333-3333-333333333308', 8, 'Kelompok 350', 350.00, 'Tarif borongan pack bungkus Rp 350/pcs'),
('33333333-3333-3333-3333-333333333309', 9, 'Kelompok 300', 300.00, 'Tarif borongan pack bungkus Rp 300/pcs')
ON CONFLICT (nama_kelompok) DO NOTHING;

-- 2. Master Karyawan Operasional (Data Dummy / Contoh untuk Seeding)
INSERT INTO public.karyawan (id_legacy, nama_karyawan, posisi, tipe_penggajian, uang_kehadiran_harian, tunjangan_bulanan, gaji_pokok_bulanan, persentase_komisi_sales, status_aktif) VALUES
(17, 'Operator Packing 01', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(18, 'Operator Packing 02', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(19, 'Operator Packing 03', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(20, 'Operator Packing 04', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(21, 'Operator Packing 05', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(22, 'Operator Packing 06', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(23, 'Operator Packing 07', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(24, 'Operator Packing 08', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(25, 'Operator Packing 09', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(26, 'Operator Packing 10', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(27, 'Operator Packing 11', 'pengemasan', 'borongan', 15000.00, 100000.00, 0.00, 0.00, TRUE),
(28, 'Staff Admin 01', 'admin', 'bulanan', 50000.00, 200000.00, 1500000.00, 0.00, TRUE),
(29, 'Staff Admin 02', 'admin', 'bulanan', 50000.00, 200000.00, 1500000.00, 0.00, TRUE),
(30, 'Mandor Produksi 01', 'mandor', 'bulanan', 50000.00, 300000.00, 2000000.00, 0.00, TRUE),
(31, 'Staff Kasir 01', 'admin', 'bulanan', 50000.00, 200000.00, 1500000.00, 0.00, TRUE),
(32, 'Sales Driver 01', 'sales_driver', 'bulanan', 0.00, 0.00, 1500000.00, 2.50, TRUE),
(33, 'Sales Driver 02', 'sales_driver', 'bulanan', 0.00, 0.00, 1500000.00, 2.50, TRUE),
(34, 'Sales Driver 03', 'sales_driver', 'bulanan', 0.00, 0.00, 1500000.00, 2.50, TRUE),
(39, 'Mandor Lapangan', 'mandor', 'bulanan', 0.00, 0.00, 2500000.00, 0.00, TRUE)
ON CONFLICT (id_legacy) DO UPDATE SET 
    posisi = EXCLUDED.posisi, 
    nama_karyawan = EXCLUDED.nama_karyawan, 
    gaji_pokok_bulanan = EXCLUDED.gaji_pokok_bulanan,
    persentase_komisi_sales = EXCLUDED.persentase_komisi_sales;

-- Inisialisasi Master Saldo Tabungan
INSERT INTO public.tabungan (karyawan_id, saldo)
SELECT id, 0.00 FROM public.karyawan
ON CONFLICT (karyawan_id) DO NOTHING;

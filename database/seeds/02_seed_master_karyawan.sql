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

-- 2. Master Karyawan Operasional
INSERT INTO public.karyawan (id_legacy, nama_karyawan, posisi, tipe_penggajian, uang_kehadiran_harian, tunjangan_bulanan, gaji_pokok_bulanan, persentase_komisi_sales, status_aktif) VALUES
(17, 'Teh ika', 'pengemasan', 'borongan', 15000.00, 200000.00, 0.00, 0.00, TRUE),
(18, 'Mpo asiah', 'pengemasan', 'borongan', 12000.00, 100000.00, 0.00, 0.00, TRUE),
(19, 'Mba erni', 'pengemasan', 'borongan', 15000.00, 150000.00, 0.00, 0.00, TRUE),
(20, 'Mba mona', 'pengemasan', 'borongan', 15000.00, 250000.00, 0.00, 0.00, TRUE),
(21, 'Bu yanti', 'pengemasan', 'borongan', 10000.00, 50000.00, 0.00, 0.00, TRUE),
(22, 'Bu husnul', 'pengemasan', 'borongan', 10000.00, 50000.00, 0.00, 0.00, TRUE),
(23, 'Teh tati', 'pengemasan', 'borongan', 10000.00, 50000.00, 0.00, 0.00, TRUE),
(24, 'Nida', 'pengemasan', 'borongan', 10000.00, 50000.00, 0.00, 0.00, TRUE),
(25, 'Nabila', 'pengemasan', 'borongan', 10000.00, 50000.00, 0.00, 0.00, TRUE),
(26, 'Bu maryati', 'pengemasan', 'borongan', 10000.00, 50000.00, 0.00, 0.00, TRUE),
(27, 'Nur', 'pengemasan', 'borongan', 10000.00, 50000.00, 0.00, 0.00, TRUE),
(28, 'Bu ira', 'admin', 'bulanan', 50000.00, 100000.00, 0.00, 0.00, TRUE),
(29, 'ka janah', 'admin', 'bulanan', 50000.00, 100000.00, 0.00, 0.00, TRUE),
(30, 'Ka karyati', 'mandor', 'bulanan', 50000.00, 600000.00, 0.00, 0.00, TRUE),
(31, 'Nazala', 'admin', 'bulanan', 50000.00, 200000.00, 0.00, 0.00, TRUE),
(32, 'Pak Slamet', 'sales_driver', 'bulanan', 0.00, 0.00, 1700000.00, 2.50, TRUE),
(33, 'Yayat', 'sales_driver', 'bulanan', 0.00, 0.00, 1500000.00, 2.50, TRUE),
(34, 'Asep', 'sales_driver', 'bulanan', 0.00, 0.00, 700000.00, 2.50, TRUE),
(39, 'Mpo Wiwi', 'mandor', 'bulanan', 0.00, 0.00, 5200000.00, 0.00, TRUE)
ON CONFLICT (id_legacy) DO UPDATE SET 
    posisi = EXCLUDED.posisi, 
    nama_karyawan = EXCLUDED.nama_karyawan, 
    gaji_pokok_bulanan = EXCLUDED.gaji_pokok_bulanan,
    persentase_komisi_sales = EXCLUDED.persentase_komisi_sales;

-- Inisialisasi Master Saldo Tabungan
INSERT INTO public.tabungan (karyawan_id, saldo)
SELECT id, 0.00 FROM public.karyawan
ON CONFLICT (karyawan_id) DO NOTHING;

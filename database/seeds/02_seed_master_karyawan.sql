-- ==============================================================================
-- SEED DATA 02: KELOMPOK UPAH BORONGAN & MASTER KARYAWAN (PROFIL & PENGGAJIAN)
-- Posisi Terpadu: Pengemasan (Borongan), Staff Toko & Admin, Mandor, Sales, Driver
-- Status: 100% Selaras Penuh dengan Skema Terpadu Pengguna & Karyawan (Post-Merge)
-- ==============================================================================

-- 1. Kelompok Upah Borongan Pengemasan
INSERT INTO public.kelompok_upah_borongan (id, id_legacy, nama_kelompok, upah_per_bungkus, keterangan) VALUES
('33333333-3333-3333-3333-333333333305', 5, 'Kelompok 600', 600.00, 'Tarif borongan pack bungkus Rp 600/pcs'),
('33333333-3333-3333-3333-333333333306', 6, 'Kelompok 500', 500.00, 'Tarif borongan pack bungkus Rp 500/pcs'),
('33333333-3333-3333-3333-333333333307', 7, 'Kelompok 400', 400.00, 'Tarif borongan pack bungkus Rp 400/pcs'),
('33333333-3333-3333-3333-333333333308', 8, 'Kelompok 350', 350.00, 'Tarif borongan pack bungkus Rp 350/pcs'),
('33333333-3333-3333-3333-333333333309', 9, 'Kelompok 300', 300.00, 'Tarif borongan pack bungkus Rp 300/pcs')
ON CONFLICT (nama_kelompok) DO NOTHING;

-- 2. Master Akun / Profil Karyawan Operasional pada public.pengguna
INSERT INTO public.pengguna (nama_lengkap, posisi, karyawan_legacy_id, status_aktif) VALUES
('Teh ika', 'pengemasan', 17, TRUE),
('Mpo asiah', 'pengemasan', 18, TRUE),
('Mba erni', 'pengemasan', 19, TRUE),
('Mba mona', 'pengemasan', 20, TRUE),
('Bu yanti', 'pengemasan', 21, TRUE),
('Bu husnul', 'pengemasan', 22, TRUE),
('Teh tati', 'pengemasan', 23, TRUE),
('Nida', 'pengemasan', 24, TRUE),
('Nabila', 'pengemasan', 25, TRUE),
('Bu maryati', 'pengemasan', 26, TRUE),
('Nur', 'pengemasan', 27, TRUE),
('Bu ira', 'admin', 28, TRUE),
('ka janah', 'admin', 29, TRUE),
('Ka karyati', 'mandor', 30, TRUE),
('Nazala', 'admin', 31, TRUE),
('Pak Slamet', 'sales', 32, TRUE),
('Yayat', 'sales', 33, TRUE),
('Asep', 'sales', 34, TRUE),
('Mpo Wiwi', 'mandor', 39, TRUE),
('Pak Joko (Driver)', 'driver', NULL, TRUE)
ON CONFLICT (karyawan_legacy_id) DO UPDATE SET
    nama_lengkap = EXCLUDED.nama_lengkap,
    posisi = EXCLUDED.posisi,
    status_aktif = EXCLUDED.status_aktif;

-- 3. Master Relasi Penggajian & Kontrak Kerja Karyawan pada public.karyawan
INSERT INTO public.karyawan (pengguna_id, tipe_penggajian, uang_kehadiran_harian, tunjangan_bulanan, gaji_pokok_bulanan, persentase_komisi_sales)
SELECT p.id, d.tipe_penggajian, d.uang_kehadiran, d.tunjangan, d.gaji_pokok, d.komisi
FROM (
    VALUES
    (17, 'borongan', 15000.00, 200000.00, 0.00, 0.00),
    (18, 'borongan', 12000.00, 100000.00, 0.00, 0.00),
    (19, 'borongan', 15000.00, 150000.00, 0.00, 0.00),
    (20, 'borongan', 15000.00, 250000.00, 0.00, 0.00),
    (21, 'borongan', 10000.00, 50000.00, 0.00, 0.00),
    (22, 'borongan', 10000.00, 50000.00, 0.00, 0.00),
    (23, 'borongan', 10000.00, 50000.00, 0.00, 0.00),
    (24, 'borongan', 10000.00, 50000.00, 0.00, 0.00),
    (25, 'borongan', 10000.00, 50000.00, 0.00, 0.00),
    (26, 'borongan', 10000.00, 50000.00, 0.00, 0.00),
    (27, 'borongan', 10000.00, 50000.00, 0.00, 0.00),
    (28, 'bulanan', 50000.00, 100000.00, 0.00, 0.00),
    (29, 'bulanan', 50000.00, 100000.00, 0.00, 0.00),
    (30, 'bulanan', 50000.00, 600000.00, 0.00, 0.00),
    (31, 'bulanan', 50000.00, 200000.00, 0.00, 0.00),
    (32, 'bulanan', 0.00, 0.00, 1700000.00, 2.50),
    (33, 'bulanan', 0.00, 0.00, 1500000.00, 2.50),
    (34, 'bulanan', 0.00, 0.00, 700000.00, 2.50),
    (39, 'bulanan', 0.00, 0.00, 5200000.00, 0.00)
) AS d(legacy_id, tipe_penggajian, uang_kehadiran, tunjangan, gaji_pokok, komisi)
JOIN public.pengguna p ON p.karyawan_legacy_id = d.legacy_id
ON CONFLICT (pengguna_id) DO UPDATE SET
    tipe_penggajian = EXCLUDED.tipe_penggajian,
    uang_kehadiran_harian = EXCLUDED.uang_kehadiran_harian,
    tunjangan_bulanan = EXCLUDED.tunjangan_bulanan,
    gaji_pokok_bulanan = EXCLUDED.gaji_pokok_bulanan,
    persentase_komisi_sales = EXCLUDED.persentase_komisi_sales;

-- 4. Penggajian Driver Khusus (Pak Joko)
INSERT INTO public.karyawan (pengguna_id, tipe_penggajian, uang_kehadiran_harian, tunjangan_bulanan, gaji_pokok_bulanan, persentase_komisi_sales)
SELECT p.id, 'bulanan', 25000.00, 50000.00, 2000000.00, 0.00
FROM public.pengguna p
WHERE p.nama_lengkap = 'Pak Joko (Driver)'
ON CONFLICT (pengguna_id) DO NOTHING;

-- 5. Inisialisasi Master Saldo Tabungan Karyawan
INSERT INTO public.tabungan (karyawan_id, saldo)
SELECT id, 0.00 FROM public.karyawan
ON CONFLICT (karyawan_id) DO NOTHING;
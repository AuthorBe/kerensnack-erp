-- ==============================================================================
-- SEED DATA 02: KELOMPOK UPAH BORONGAN & MASTER KARYAWAN (PROFIL & PENGGAJIAN)
-- Posisi Terpadu: Pengemasan (Borongan), Staff Toko & Admin, Mandor, Sales, Driver
-- Status: Template untuk onboarding klien baru — data contoh (anonim)
-- CATATAN: Ganti nama dan data di sini sesuai data karyawan aktual perusahaan.
-- ==============================================================================

-- 1. Kelompok Upah Borongan Pengemasan
-- Sesuaikan tarif upah per bungkus sesuai kebijakan perusahaan
INSERT INTO public.kelompok_upah_borongan (id, id_legacy, nama_kelompok, upah_per_bungkus, keterangan) VALUES
('33333333-3333-3333-3333-333333333305', 5, 'Kelompok 600', 600.00, 'Tarif borongan pack bungkus Rp 600/pcs'),
('33333333-3333-3333-3333-333333333306', 6, 'Kelompok 500', 500.00, 'Tarif borongan pack bungkus Rp 500/pcs'),
('33333333-3333-3333-3333-333333333307', 7, 'Kelompok 400', 400.00, 'Tarif borongan pack bungkus Rp 400/pcs'),
('33333333-3333-3333-3333-333333333308', 8, 'Kelompok 350', 350.00, 'Tarif borongan pack bungkus Rp 350/pcs'),
('33333333-3333-3333-3333-333333333309', 9, 'Kelompok 300', 300.00, 'Tarif borongan pack bungkus Rp 300/pcs')
ON CONFLICT (nama_kelompok) DO NOTHING;

-- ==============================================================================
-- CATATAN UNTUK ONBOARDING KLIEN BARU:
-- Gunakan fitur "Import Data" atau tambahkan karyawan satu per satu
-- melalui menu HR > Karyawan di aplikasi web.
-- Atau, copy bagian di bawah ini dan ganti dengan data karyawan aktual.
-- ==============================================================================

-- Contoh: Insert karyawan borongan pengemasan
-- INSERT INTO public.pengguna (nama_lengkap, posisi, karyawan_legacy_id, status_aktif) VALUES
-- ('Nama Karyawan 1', 'pengemasan', 1, TRUE),
-- ('Nama Karyawan 2', 'pengemasan', 2, TRUE)
-- ON CONFLICT (karyawan_legacy_id) DO UPDATE SET
--     nama_lengkap = EXCLUDED.nama_lengkap,
--     posisi = EXCLUDED.posisi,
--     status_aktif = EXCLUDED.status_aktif;

-- Contoh: Insert relasi penggajian
-- INSERT INTO public.karyawan (pengguna_id, tipe_penggajian, uang_kehadiran_harian, tunjangan_bulanan, gaji_pokok_bulanan)
-- SELECT p.id, 'borongan', 15000.00, 200000.00, 0.00
-- FROM public.pengguna p WHERE p.karyawan_legacy_id = 1
-- ON CONFLICT (pengguna_id) DO UPDATE SET
--     tipe_penggajian = EXCLUDED.tipe_penggajian,
--     uang_kehadiran_harian = EXCLUDED.uang_kehadiran_harian,
--     tunjangan_bulanan = EXCLUDED.tunjangan_bulanan,
--     gaji_pokok_bulanan = EXCLUDED.gaji_pokok_bulanan;
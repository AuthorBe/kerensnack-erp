-- ==============================================================================
-- SEED DATA 04: MASTER WILAYAH, GRUP PELANGGAN, PELANGGAN & PEMASOK v2.0
-- ==============================================================================

-- 1. Master Wilayah Distribusi
INSERT INTO public.wilayah (kode_rute, nama_wilayah, provinsi, kota_kabupaten) VALUES
('RUTE-JABODETABEK', 'Jabodetabek & Sekitarnya', 'DKI Jakarta / Banten', 'Jabodetabek'),
('RUTE-TNG-TIMUR', 'Tangerang Timur (Ciledug & Cipondoh)', 'Banten', 'Kota Tangerang'),
('RUTE-JAKBAR', 'Jakarta Barat & Sekitarnya', 'DKI Jakarta', 'Jakarta Barat')
ON CONFLICT (kode_rute) DO NOTHING;

-- 2. Master Grup Pelanggan (Pemegang Default Level Harga 1-28 & Diskon)
INSERT INTO public.grup_pelanggan (id, kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default) VALUES
('44444444-4444-4444-4444-444444444401', 'GRP-UMUM-RITEL', 'Grup Ritel Standar', 1, 0.00, 0.00),
('44444444-4444-4444-4444-444444444402', 'GRP-MITRA-A', 'Grup Mitra Warung A', 8, 5.00, 0.00),
('44444444-4444-4444-4444-444444444403', 'GRP-GROSIR-B', 'Grup Grosir Pasar B', 12, 0.00, 500.00),
('44444444-4444-4444-4444-444444444404', 'GRP-KONSINYASI', 'Grup Toko Titip Jual', 5, 0.00, 0.00)
ON CONFLICT (kode_grup) DO NOTHING;

-- 3. Master Pemasok (Vendor Bahan Mentah & Kemasan - Data Dummy)
INSERT INTO public.pemasok (kode_pemasok, nama_pemasok, alamat_lengkap, nomor_telepon) VALUES
('SUP-001', 'Pemasok Bahan Curah A', 'Sentra Bahan Kerupuk & Curah', '081200000001'),
('SUP-002', 'Pemasok Bumbu & Sayur B', 'Kawasan Sentra Rempah', '081200000002'),
('SUP-003', 'Pemasok Tepung Sagu C', 'Sentra Sagu & Tepung', '081200000003'),
('SUP-004', 'Mitra Kemasan Plastik D', 'Kawasan Industri Kemasan', '081200000004')
ON CONFLICT (kode_pemasok) DO NOTHING;

-- 4. Master Pelanggan (Toko Langganan Reguler & Toko Konsinyasi - Data Dummy)
INSERT INTO public.pelanggan (kode_pelanggan, nama_toko, grup_pelanggan_id, is_konsinyasi, alamat_lengkap, nomor_telepon, tipe_pembayaran_default, plafon_piutang) VALUES
('CUST-001', 'Toko Umum / Walk-in Cash', '44444444-4444-4444-4444-444444444401', FALSE, 'Toko Langsung / Walk-in', '', 'cash', 0.00),
('CUST-002', 'Toko Konsinyasi Contoh A', '44444444-4444-4444-4444-444444444404', TRUE, 'Jl. Contoh Raya No. 10', '081200000011', 'konsinyasi', 5000000.00),
('CUST-003', 'Toko Konsinyasi Contoh B', '44444444-4444-4444-4444-444444444404', TRUE, 'Jl. Contoh Boulevard No. 25', '081200000012', 'konsinyasi', 5000000.00),
('CUST-004', 'Toko Grosir Berkah C', '44444444-4444-4444-4444-444444444402', FALSE, 'Pasar Grosir Blok A No. 12', '081200000013', 'tempo_7_hari', 3000000.00),
('CUST-005', 'Toko Swalayan Maju D', '44444444-4444-4444-4444-444444444403', FALSE, 'Jl. Pertokoan Raya No. 88', '081200000014', 'tempo_14_hari', 10000000.00)
ON CONFLICT (kode_pelanggan) DO UPDATE SET 
    nama_toko = EXCLUDED.nama_toko, 
    grup_pelanggan_id = EXCLUDED.grup_pelanggan_id,
    is_konsinyasi = EXCLUDED.is_konsinyasi,
    alamat_lengkap = EXCLUDED.alamat_lengkap, 
    nomor_telepon = EXCLUDED.nomor_telepon, 
    plafon_piutang = EXCLUDED.plafon_piutang;
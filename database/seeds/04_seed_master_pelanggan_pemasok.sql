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

-- 3. Master Pemasok (Vendor Bahan Mentah & Kemasan)
INSERT INTO public.pemasok (kode_pemasok, nama_pemasok, alamat_lengkap, nomor_telepon) VALUES
('SUP-001', 'MM JAYA', 'Sentra Bahan Kerupuk & Curah', '081234567890'),
('SUP-002', 'JENGKOL SUPPLIER', 'Pasar Induk Kramat Jati', '081398765432'),
('SUP-003', 'TETEH SAGU', 'Sentra Sagu & Tepung', '081512345678'),
('SUP-004', 'PT SUMBER PLASTIK KEMASAN', 'Kawasan Industri Tangerang', '082188889999')
ON CONFLICT (kode_pemasok) DO NOTHING;

-- 4. Master Pelanggan (Toko Langganan Reguler & Toko Konsinyasi)
INSERT INTO public.pelanggan (kode_pelanggan, nama_toko, grup_pelanggan_id, is_konsinyasi, alamat_lengkap, nomor_telepon, tipe_pembayaran_default, plafon_piutang) VALUES
('CUST-001', 'UMUM/CASH', '44444444-4444-4444-4444-444444444401', FALSE, 'Toko Langsung / Walk-in', '', 'cash', 0.00),
('CUST-002', 'MAJESTYK CIPUTAT', '44444444-4444-4444-4444-444444444404', TRUE, 'Jl. Raya Ciputat No. 45', '081299991111', 'konsinyasi', 5000000.00),
('CUST-003', 'MAJESTYK M KAHFI', '44444444-4444-4444-4444-444444444404', TRUE, 'Jl. Moch Kahfi Jagakarsa', '081388882222', 'konsinyasi', 5000000.00),
('CUST-004', 'TOKO BERKAH CILEDUG', '44444444-4444-4444-4444-444444444402', FALSE, 'Pasar Ciledug Blok A No. 12', '081577773333', 'tempo_7_hari', 3000000.00),
('CUST-005', 'TOKO MAJU JAYA CIPONDOH', '44444444-4444-4444-4444-444444444403', FALSE, 'Jl. KH Hasyim Ashari No. 88', '081766664444', 'tempo_14_hari', 10000000.00)
ON CONFLICT (kode_pelanggan) DO UPDATE SET 
    nama_toko = EXCLUDED.nama_toko, 
    grup_pelanggan_id = EXCLUDED.grup_pelanggan_id,
    is_konsinyasi = EXCLUDED.is_konsinyasi,
    alamat_lengkap = EXCLUDED.alamat_lengkap, 
    nomor_telepon = EXCLUDED.nomor_telepon, 
    plafon_piutang = EXCLUDED.plafon_piutang;
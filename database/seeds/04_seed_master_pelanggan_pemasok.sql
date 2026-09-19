-- ==============================================================================
-- SEED DATA 04: MASTER WILAYAH, GRUP PELANGGAN, PELANGGAN & PEMASOK
-- Status: Template untuk onboarding klien baru — data default aman
-- CATATAN: Ganti data wilayah, pelanggan, dan pemasok sesuai bisnis aktual.
-- ==============================================================================

-- 1. Master Wilayah Distribusi (opsional — sesuaikan dengan area operasional)
-- INSERT INTO public.wilayah (kode_rute, nama_wilayah, provinsi, kota_kabupaten) VALUES
-- ('RUTE-001', 'Wilayah Distribusi 1', 'Nama Provinsi', 'Nama Kota')
-- ON CONFLICT (kode_rute) DO NOTHING;

-- 2. Master Grup Pelanggan (Default 4 grup standar — bisa disesuaikan)
INSERT INTO public.grup_pelanggan (id, kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default) VALUES
('44444444-4444-4444-4444-444444444401', 'GRP-UMUM-RITEL',   'Grup Ritel Standar',       1,  0.00, 0.00),
('44444444-4444-4444-4444-444444444402', 'GRP-MITRA-A',      'Grup Mitra Warung A',       8,  5.00, 0.00),
('44444444-4444-4444-4444-444444444403', 'GRP-GROSIR-B',     'Grup Grosir Pasar B',       12, 0.00, 500.00),
('44444444-4444-4444-4444-444444444404', 'GRP-KONSINYASI',   'Grup Toko Titip Jual',      5,  0.00, 0.00)
ON CONFLICT (kode_grup) DO NOTHING;

-- 3. Pelanggan default: Walk-in / Kasir (diperlukan sistem POS)
INSERT INTO public.pelanggan (kode_pelanggan, nama_toko, grup_pelanggan_id, is_konsinyasi, alamat_lengkap, tipe_pembayaran_default, plafon_piutang) VALUES
('CUST-001', 'Toko Umum / Walk-in Cash', '44444444-4444-4444-4444-444444444401', FALSE, 'Langsung / Walk-in', 'cash', 0.00)
ON CONFLICT (kode_pelanggan) DO UPDATE SET
    nama_toko             = EXCLUDED.nama_toko,
    grup_pelanggan_id     = EXCLUDED.grup_pelanggan_id,
    is_konsinyasi         = EXCLUDED.is_konsinyasi,
    alamat_lengkap        = EXCLUDED.alamat_lengkap,
    tipe_pembayaran_default = EXCLUDED.tipe_pembayaran_default,
    plafon_piutang        = EXCLUDED.plafon_piutang;

-- ==============================================================================
-- CATATAN UNTUK ONBOARDING KLIEN BARU:
--
-- Tambahkan pelanggan dan pemasok via:
--   - Menu Master Data > Pelanggan > Tambah Pelanggan
--   - Menu Master Data > Pemasok > Tambah Pemasok
--   - Atau gunakan fitur Import Data untuk input massal
-- ==============================================================================

-- Contoh: Insert Pemasok
-- INSERT INTO public.pemasok (kode_pemasok, nama_pemasok, alamat_lengkap, nomor_telepon) VALUES
-- ('SUP-001', 'Nama Pemasok A', 'Alamat Pemasok A', '08xx-xxxx-xxxx')
-- ON CONFLICT (kode_pemasok) DO NOTHING;

-- Contoh: Insert Pelanggan Tambahan
-- INSERT INTO public.pelanggan (kode_pelanggan, nama_toko, grup_pelanggan_id, is_konsinyasi, alamat_lengkap, nomor_whatsapp, tipe_pembayaran_default, plafon_piutang) VALUES
-- ('CUST-002', 'Nama Toko B', '44444444-4444-4444-4444-444444444402', FALSE, 'Alamat Toko B', '08xx-xxxx', 'cash', 0.00)
-- ON CONFLICT (kode_pelanggan) DO UPDATE SET nama_toko = EXCLUDED.nama_toko;
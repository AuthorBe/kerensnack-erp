-- ==============================================================================
-- SEED DATA 03: GRUP PRODUK, PRICING DINAMIS & MASTER SKU
-- Status: Template untuk onboarding klien baru — data contoh (anonim)
-- CATATAN: Ganti grup produk, SKU, dan barcode sesuai katalog aktual perusahaan.
-- ==============================================================================

-- ==============================================================================
-- CATATAN UNTUK ONBOARDING KLIEN BARU:
--
-- 1. Grup Produk = Pengelompokan produk berdasarkan kemasan/ukuran
--    Setiap grup punya barcode universal (dipakai oleh semua varian rasa di grup itu)
--
-- 2. Item/SKU = Varian rasa spesifik dalam satu grup produk
--    Contoh: Grup "Kerupuk Singkong 150gr" bisa punya varian: Asin, Pedas, Manis
--
-- 3. Harga Level = Matriks harga per grup (Level 1 = eceran, Level 5 = konsinyasi, dll)
--    Gunakan menu Produk > Pengaturan Harga untuk setup harga via UI.
--
-- Untuk input produk massal, gunakan fitur:
--   - Menu Produk > Import Data
--   - Atau tambahkan manual satu per satu di Menu Produk > Tambah Produk
-- ==============================================================================

-- Contoh: Insert Grup Produk (ganti dengan katalog aktual)
-- INSERT INTO public.grup_produk (kode_grup, nama_grup, barcode_universal, satuan_dasar) VALUES
-- ('GRP-001', 'Nama Produk Grup 1 (misal: Kerupuk Singkong 150gr)', 'barcode123', 'pcs'),
-- ('GRP-002', 'Nama Produk Grup 2 (misal: Keripik Jagung 100gr)',   'barcode456', 'pcs')
-- ON CONFLICT (kode_grup) DO UPDATE SET nama_grup = EXCLUDED.nama_grup, barcode_universal = EXCLUDED.barcode_universal;

-- Contoh: Insert Harga Level Default per Grup (Level 1 - Ritel Standar)
-- INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, nama_level, harga_jual_pcs)
-- SELECT gp.id, 1, 'Level 1 - Ritel Standar (Konsumen Umum / POS)', 15000.00 FROM public.grup_produk gp
-- ON CONFLICT (grup_produk_id, level_harga) DO NOTHING;

-- Contoh: Insert Item/SKU Varian Rasa
-- INSERT INTO public.item (grup_id, kode_sku, barcode, nama_item, varian_rasa, tipe_item, satuan_dasar, stok_fisik_saat_ini, status_jual)
-- VALUES
-- ((SELECT id FROM public.grup_produk WHERE kode_grup = 'GRP-001'), 'SKU-001', 'barcode123', 'Nama Item - Rasa A', 'Rasa A', 'barang_jadi', 'pcs', 0, TRUE),
-- ((SELECT id FROM public.grup_produk WHERE kode_grup = 'GRP-001'), 'SKU-002', 'barcode123', 'Nama Item - Rasa B', 'Rasa B', 'barang_jadi', 'pcs', 0, TRUE)
-- ON CONFLICT (kode_sku) DO UPDATE SET nama_item = EXCLUDED.nama_item, barcode = EXCLUDED.barcode, stok_fisik_saat_ini = EXCLUDED.stok_fisik_saat_ini;

-- ==============================================================================
-- KEREN SNACK - DATABASE MIGRATION
-- Fix Inventory Stock Types & Prevent Race Conditions
-- ==============================================================================

-- 1. Mengubah tipe kolom stok pada tabel `item` dari INT menjadi NUMERIC(15, 4) 
--    untuk mendukung bahan baku fraksional secara presisi (mencegah bug pembulatan dari BOM).
ALTER TABLE public.item 
    ALTER COLUMN stok_fisik_saat_ini TYPE NUMERIC(15, 4) USING stok_fisik_saat_ini::NUMERIC,
    ALTER COLUMN stok_minimum_peringatan TYPE NUMERIC(15, 4) USING stok_minimum_peringatan::NUMERIC;

-- 2. Menambahkan constraint anti-minus pada tabel `item` untuk mencegah
--    race condition stok minus akibat query konkuren.
ALTER TABLE public.item
    ADD CONSTRAINT chk_stok_fisik_positif CHECK (stok_fisik_saat_ini >= 0);

-- 3. Mengubah tipe kolom pada tabel `riwayat_stok` menjadi NUMERIC(15, 4)
ALTER TABLE public.riwayat_stok
    ALTER COLUMN jumlah_perubahan TYPE NUMERIC(15, 4) USING jumlah_perubahan::NUMERIC,
    ALTER COLUMN stok_sebelum TYPE NUMERIC(15, 4) USING stok_sebelum::NUMERIC,
    ALTER COLUMN stok_sesudah TYPE NUMERIC(15, 4) USING stok_sesudah::NUMERIC;

-- 4. Mengubah tipe kolom pada tabel `stok_konsinyasi_toko` menjadi NUMERIC(15, 4)
ALTER TABLE public.stok_konsinyasi_toko
    ALTER COLUMN stok_titip_saat_ini TYPE NUMERIC(15, 4) USING stok_titip_saat_ini::NUMERIC;

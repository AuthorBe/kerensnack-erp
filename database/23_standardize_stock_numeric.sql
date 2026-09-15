-- ============================================================================
-- Migration 23: Standardize stock quantity columns to NUMERIC(15,2)
-- Kerensnack ERP - Inventaris & Gudang
-- ============================================================================

-- 1. Tabel Item: Ubah tipe stok fisik dan minimum peringatan ke NUMERIC(15,2)
ALTER TABLE public.item 
    ALTER COLUMN stok_fisik_saat_ini TYPE NUMERIC(15,2),
    ALTER COLUMN stok_minimum_peringatan TYPE NUMERIC(15,2);

-- 2. Tabel Riwayat Stok (Buku Besar Mutasi): Ubah kuantitas ke NUMERIC(15,2)
ALTER TABLE public.riwayat_stok 
    ALTER COLUMN jumlah_perubahan TYPE NUMERIC(15,2),
    ALTER COLUMN stok_sebelum TYPE NUMERIC(15,2),
    ALTER COLUMN stok_sesudah TYPE NUMERIC(15,2);

-- 3. Verifikasi komentar kolom
COMMENT ON COLUMN public.item.stok_fisik_saat_ini IS 'Saldo stok fisik riil di gudang, mendukung desimal untuk bahan baku (kg/liter)';
COMMENT ON COLUMN public.riwayat_stok.jumlah_perubahan IS 'Kuantitas mutasi stok bertambah/berkurang dalam satuan dasar';
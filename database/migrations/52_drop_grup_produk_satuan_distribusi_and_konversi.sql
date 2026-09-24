-- ==============================================================================
-- KEREN SNACK ERP - DATABASE MIGRATION 52: DROP SATUAN DISTRIBUSI & KONVERSI BAL
-- ==============================================================================
-- Deskripsi:
-- Menghapus kolom 'satuan_distribusi' dan 'konversi_bal_ke_pcs' dari tabel 'public.grup_produk'
-- karena seluruh transaksi penjualan, inventori, dan pricing telah disederhanakan
-- berbasis satuan fisik tunggal murni ('pcs').
-- ==============================================================================

BEGIN;

-- 1. Hapus kolom satuan_distribusi dari public.grup_produk
ALTER TABLE public.grup_produk 
DROP COLUMN IF EXISTS satuan_distribusi;

-- 2. Hapus kolom konversi_bal_ke_pcs dari public.grup_produk
ALTER TABLE public.grup_produk 
DROP COLUMN IF EXISTS konversi_bal_ke_pcs;

COMMIT;

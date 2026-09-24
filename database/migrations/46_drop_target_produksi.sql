-- ==============================================================================
-- 46_drop_target_produksi.sql
-- Penghapusan Tabel Target Produksi yang Tidak Digunakan
-- Sesuai Arahan: Pembersihan sisa tabel target_produksi dari skema database.
-- ==============================================================================

BEGIN;

-- 1. DROP TABEL TARGET PRODUKSI BESERTA DEPENDENSI/RLS
DROP TABLE IF EXISTS public.target_produksi CASCADE;

COMMIT;

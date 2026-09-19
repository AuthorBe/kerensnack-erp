BEGIN;

-- ====================================================================
-- Migrasi 57: Drop Kolom Upah Borongan Manual (upah_per_bungkus) dari Tabel Item
-- Selaras dengan arsitektur master data: Tarif upah borongan murni
-- mengikuti relasi kelompok_borongan_id -> kelompok_upah_borongan.
-- ====================================================================

ALTER TABLE public.item DROP COLUMN IF EXISTS upah_per_bungkus;

COMMIT;

-- database/56_drop_nomor_telepon_from_pemasok.sql
-- Migration 56: Menghapus kolom redundan nomor_telepon dari tabel public.pemasok
-- Memusatkan seluruh komunikasi & koordinasi kontak vendor pada nomor_whatsapp

BEGIN;

-- 1. Pindahkan nomor_telepon ke nomor_whatsapp jika nomor_whatsapp masih kosong
UPDATE public.pemasok
SET nomor_whatsapp = TRIM(nomor_telepon)
WHERE (nomor_whatsapp IS NULL OR TRIM(nomor_whatsapp) = '')
  AND (nomor_telepon IS NOT NULL AND TRIM(nomor_telepon) != '');

-- 2. Hapus kolom nomor_telepon secara aman dari tabel pemasok
ALTER TABLE public.pemasok DROP COLUMN IF EXISTS nomor_telepon;

COMMIT;

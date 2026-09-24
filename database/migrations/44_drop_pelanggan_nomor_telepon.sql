-- ==============================================================================
-- MIGRASI 44: PENYATUAN KONTAK PELANGGAN (DROP COLUMN nomor_telepon)
-- ==============================================================================
-- 1. Preservasi Data: Salin data dari nomor_telepon ke nomor_whatsapp jika
--    kolom nomor_whatsapp saat ini masih kosong / NULL.
UPDATE public.pelanggan
SET nomor_whatsapp = TRIM(nomor_telepon)
WHERE (nomor_whatsapp IS NULL OR TRIM(nomor_whatsapp) = '')
  AND (nomor_telepon IS NOT NULL AND TRIM(nomor_telepon) != '');

-- 2. Hapus kolom redundan nomor_telepon dari skema tabel public.pelanggan
ALTER TABLE public.pelanggan DROP COLUMN IF EXISTS nomor_telepon;

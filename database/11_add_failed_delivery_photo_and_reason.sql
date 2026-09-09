-- ============================================================================
-- 11_add_failed_delivery_photo_and_reason.sql
-- Menambahkan kolom foto bukti gagal kirim, alasan, dan catatan pada surat_jalan
-- ============================================================================

ALTER TABLE public.surat_jalan 
ADD COLUMN IF NOT EXISTS foto_bukti_gagal TEXT,
ADD COLUMN IF NOT EXISTS alasan_gagal VARCHAR(255),
ADD COLUMN IF NOT EXISTS catatan_gagal TEXT;

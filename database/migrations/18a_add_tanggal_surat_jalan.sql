-- database/18_add_tanggal_surat_jalan.sql
-- Menambahkan kolom tanggal_surat_jalan pada tabel public.surat_jalan

ALTER TABLE public.surat_jalan 
ADD COLUMN IF NOT EXISTS tanggal_surat_jalan DATE DEFAULT CURRENT_DATE;

-- Backfill data yang sudah ada sebelumnya
UPDATE public.surat_jalan 
SET tanggal_surat_jalan = COALESCE(waktu_berangkat::date, dibuat_pada::date, CURRENT_DATE) 
WHERE tanggal_surat_jalan IS NULL;

-- Index untuk mempercepat query pencarian dan filter tanggal pengiriman
CREATE INDEX IF NOT EXISTS idx_surat_jalan_tanggal ON public.surat_jalan (tanggal_surat_jalan);

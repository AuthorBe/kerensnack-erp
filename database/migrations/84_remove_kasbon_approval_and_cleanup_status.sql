-- ==============================================================================
-- 84_remove_kasbon_approval_and_cleanup_status.sql
-- Penghapusan Total Kolom & Alur Approval pada Modul Kasbon
-- Sesuai Arahan: Approval kasbon ditiadakan (kasbon langsung aktif dicatat admin).
-- Status kasbon hanya 'aktif' dan 'lunas'. Kolom disetujui_oleh dihapus.
-- ==============================================================================

BEGIN;

-- 1. Bersihkan record kasbon berstatus 'dibatalkan' jika ada
DELETE FROM public.kasbon WHERE status_kasbon = 'dibatalkan';

-- 2. Hapus foreign key dan kolom disetujui_oleh dari public.kasbon
ALTER TABLE public.kasbon DROP CONSTRAINT IF EXISTS kasbon_disetujui_oleh_fkey;
ALTER TABLE public.kasbon DROP COLUMN IF EXISTS disetujui_oleh;

-- 3. Perbarui check constraint status_kasbon agar hanya ('aktif', 'lunas')
ALTER TABLE public.kasbon DROP CONSTRAINT IF EXISTS kasbon_status_kasbon_check;
ALTER TABLE public.kasbon ADD CONSTRAINT kasbon_status_kasbon_check 
    CHECK (((status_kasbon)::text = ANY ((ARRAY['aktif'::character varying, 'lunas'::character varying])::text[])));

COMMIT;

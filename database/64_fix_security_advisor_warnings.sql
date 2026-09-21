-- ==============================================================================
-- KEREN SNACK ERP - DATABASE MIGRATION 64: PERBAIKAN SUPABASE SECURITY ADVISOR
-- ==============================================================================
-- 1. Mengunci RLS Policy tabel grup_pelanggan_level_merek hanya untuk service_role
--    (Menghapus public_all_gplm yang membuka akses bebas ke role public/anon/authenticated)
-- 2. Mencabut izin eksekusi API REST/RPC (anon & authenticated) pada trigger function
--    SECURITY DEFINER (fn_trg_grup_pelanggan_after_insert & fn_trg_merek_after_insert)
-- ==============================================================================

BEGIN;

-- 1. Perbaikan RLS Policy pada grup_pelanggan_level_merek
ALTER TABLE public.grup_pelanggan_level_merek ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS public_all_gplm ON public.grup_pelanggan_level_merek;
DROP POLICY IF EXISTS service_role_all_gplm ON public.grup_pelanggan_level_merek;

CREATE POLICY service_role_all_gplm 
ON public.grup_pelanggan_level_merek 
FOR ALL 
TO service_role 
USING (true) 
WITH CHECK (true);

-- 2. Pencabutan izin EXECUTE publik/anon/authenticated pada trigger function internal
REVOKE EXECUTE ON FUNCTION public.fn_trg_grup_pelanggan_after_insert() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_trg_merek_after_insert() FROM PUBLIC, anon, authenticated;

-- 3. Berikan hak akses eksekusi hanya kepada backend internal (postgres & service_role)
GRANT EXECUTE ON FUNCTION public.fn_trg_grup_pelanggan_after_insert() TO postgres, service_role;
GRANT EXECUTE ON FUNCTION public.fn_trg_merek_after_insert() TO postgres, service_role;

COMMIT;

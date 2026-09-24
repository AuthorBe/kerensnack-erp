-- database/60_fix_v_karyawan_info_security_invoker.sql
-- Migrasi keamanan: Mengaktifkan SECURITY INVOKER pada view public.v_karyawan_info
-- Mencegah bypass RLS dan menyelesaikan peringatan Supabase Security Advisor (Security Definer View)

BEGIN;

ALTER VIEW public.v_karyawan_info SET (security_invoker = true);

COMMIT;

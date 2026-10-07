-- database/migrations/97_lockdown_rls_auto_enable_rpc.sql
-- Penguncian Hak Eksekusi RPC Publik pada Event Trigger Function rls_auto_enable()
-- Menyelesaikan peringatan Supabase Security Advisor (Linter 0028 & 0029: Public/Authenticated Can Execute SECURITY DEFINER)
-- Mencegah akses eksekusi tidak sah via Supabase REST API /rest/v1/rpc/rls_auto_enable

BEGIN;

DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM pg_proc p 
        JOIN pg_namespace n ON n.oid = p.pronamespace 
        WHERE n.nspname = 'public' AND p.proname = 'rls_auto_enable'
    ) THEN
        EXECUTE 'REVOKE EXECUTE ON FUNCTION public.rls_auto_enable() FROM PUBLIC, anon, authenticated;';
        EXECUTE 'GRANT EXECUTE ON FUNCTION public.rls_auto_enable() TO postgres, service_role;';
    END IF;
END $$;

COMMIT;

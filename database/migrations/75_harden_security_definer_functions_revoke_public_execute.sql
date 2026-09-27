-- =============================================================================
-- Migration 75: Harden SECURITY DEFINER Functions & Revoke Public Execution
-- Repositori: KEREN ONE ERP
-- Tujuan: Menghilangkan Supabase Security Advisor Warnings (anon/authenticated
--         executable on SECURITY DEFINER functions) dan membatasi izin eksekusi
--         hanya untuk role postgres dan service_role.
-- =============================================================================

BEGIN;

-- 1. Cabut izin EXECUTE dari PUBLIC, anon, dan authenticated
REVOKE EXECUTE ON FUNCTION public.fn_buat_tagihan_kunjungan_konsinyasi(uuid[], uuid) FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_proses_kunjungan_konsinyasi(uuid, uuid, jsonb, text, text, uuid, uuid) FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_revisi_dan_rekonsiliasi_piutang_pelanggan(uuid) FROM PUBLIC, anon, authenticated;

-- 2. Pastikan role backend resmi (postgres & service_role) memiliki izin EXECUTE
GRANT EXECUTE ON FUNCTION public.fn_buat_tagihan_kunjungan_konsinyasi(uuid[], uuid) TO postgres, service_role;
GRANT EXECUTE ON FUNCTION public.fn_proses_kunjungan_konsinyasi(uuid, uuid, jsonb, text, text, uuid, uuid) TO postgres, service_role;
GRANT EXECUTE ON FUNCTION public.fn_revisi_dan_rekonsiliasi_piutang_pelanggan(uuid) TO postgres, service_role;

COMMIT;

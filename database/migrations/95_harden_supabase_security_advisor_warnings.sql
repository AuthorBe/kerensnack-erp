-- ==============================================================================
-- KEREN ONE MIGRATION: 95_harden_supabase_security_advisor_warnings.sql
-- Resolusi Penuh Supabase Security Advisor & Total Database Hardening:
-- 1. Mengaktifkan RLS & mengunci izin tabel schema_migrations
-- 2. Mengaktifkan security_invoker & mencabut grant publik pada view v_karyawan_info
-- 3. Mencabut hak EXECUTE publik (anon/authenticated) pada seluruh trigger guard internal
-- 4. Mengunci search_path statis (SET search_path = public, pg_temp) pada seluruh fungsi aplikasi
-- ==============================================================================

BEGIN;

-- ==============================================================================
-- 1. HARDENING TABEL schema_migrations (RLS & AKSES EKSKLUSIF INTERNAL)
-- ==============================================================================
ALTER TABLE public.schema_migrations ENABLE ROW LEVEL SECURITY;

REVOKE ALL ON public.schema_migrations FROM PUBLIC, anon, authenticated;
GRANT ALL ON public.schema_migrations TO postgres, service_role;

DROP POLICY IF EXISTS service_role_all_schema_migrations ON public.schema_migrations;
CREATE POLICY service_role_all_schema_migrations 
ON public.schema_migrations 
FOR ALL 
TO service_role 
USING (true) 
WITH CHECK (true);

-- ==============================================================================
-- 2. HARDENING VIEW v_karyawan_info (SECURITY INVOKER & REVOKE PUBLIK)
-- ==============================================================================
REVOKE ALL ON public.v_karyawan_info FROM PUBLIC, anon, authenticated;
GRANT SELECT ON public.v_karyawan_info TO service_role;

-- Kompatibilitas Lintas Versi: PG 15+ (Cloud Supabase) mengaktifkan security_invoker,
-- sedangkan PG 14 (Local Laragon) melewati blok ini tanpa error syntax.
DO $$
BEGIN
    IF current_setting('server_version_num')::int >= 150000 THEN
        EXECUTE 'ALTER VIEW public.v_karyawan_info SET (security_invoker = true)';
    END IF;
END $$;

-- ==============================================================================
-- 3. PENCABUTAN HAK EKSEKUSI API RPC PUBLIK PADA SELURUH FUNGSI GUARD INTERNAL
--    (Mencegah pemanggilan tak sah via /rest/v1/rpc/*)
-- ==============================================================================
REVOKE EXECUTE ON FUNCTION public.fn_guard_locked_hr_transactions() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_guard_locked_hr_transactions() TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_guard_locked_hr_rincian_transactions() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_guard_locked_hr_rincian_transactions() TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_guard_escrow_cash_account() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_guard_escrow_cash_account() TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_guard_escrow_account_mutation() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_guard_escrow_account_mutation() TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_guard_transaksi_tabungan_escrow() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_guard_transaksi_tabungan_escrow() TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_guard_arus_kas_tabungan_escrow() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_guard_arus_kas_tabungan_escrow() TO postgres, service_role;

-- ==============================================================================
-- 4. HARDENING SEARCH_PATH STATIS PADA SELURUH FUNGSI AKTIF APLIKASI
--    (Mencegah Search Path Hijacking)
-- ==============================================================================
-- A. 7 Fungsi Linter Temuan Langsung
ALTER FUNCTION public.fn_cari_item_by_barcode(character varying) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_escrow_cash_account() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_escrow_account_mutation() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_transaksi_tabungan_escrow() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_arus_kas_tabungan_escrow() SET search_path = public, pg_temp;
ALTER FUNCTION public.trg_sync_pemasok_item_to_item_hpp() SET search_path = public, pg_temp;
ALTER FUNCTION public.trg_sync_grup_barcode_default_to_grup_produk() SET search_path = public, pg_temp;

-- B. Fungsi Trigger Guard Payroll HR
ALTER FUNCTION public.fn_guard_locked_hr_transactions() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_locked_hr_rincian_transactions() SET search_path = public, pg_temp;

-- C. Seluruh Fungsi Sistem Lainnya (Zero Anomaly Hardening 1000%)
ALTER FUNCTION public.fn_guard_pelanggan_sales_driver() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_protect_default_customer() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_developer_account() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_hitung_harga_jual_item(uuid, uuid) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_hitung_tier_komisi_sales(numeric) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_proses_kunjungan_konsinyasi(uuid, uuid, jsonb, text, text, uuid, uuid) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_buat_tagihan_konsinyasi(uuid[], uuid) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_catat_pembayaran_konsinyasi(uuid, uuid, numeric, uuid, text, date, numeric, text) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_rekonsiliasi_piutang_pelanggan(uuid) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_catat_log_aktivitas(uuid, character varying, character varying, character varying, character varying, character varying, character varying, uuid, text, jsonb, jsonb) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_grup_pelanggan_after_insert() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_merek_after_insert() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_potongan_kasbon_update_saldo() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_produksi_harian_after_delete() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_produksi_harian_after_insert() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_produksi_harian_after_update() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_proses_pengiriman_konsinyasi() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_transaksi_tabungan_update_saldo() SET search_path = public, pg_temp;

COMMIT;

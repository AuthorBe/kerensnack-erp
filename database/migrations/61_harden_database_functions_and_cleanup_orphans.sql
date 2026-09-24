-- database/61_harden_database_functions_and_cleanup_orphans.sql
-- 1. Pembersihan fungsi tidak terpakai (Dead / Orphan Functions)
-- 2. Hardening search_path pada seluruh fungsi aktif PostgreSQL
-- 3. Pencabutan izin EXECUTE publik/anon/authenticated pada fungsi database internal
-- 4. Penguncian RLS Policy tabel merek dan opname_gudang ke service_role

BEGIN;

-- ==============================================================================
-- 1. PEMBERSIHAN FUNGSI DEAD / ORPHAN
-- ==============================================================================
DROP FUNCTION IF EXISTS public.fn_trg_proteksi_developer() CASCADE;
DROP FUNCTION IF EXISTS public.rls_auto_enable() CASCADE;

-- ==============================================================================
-- 2. HARDENING RLS POLICY (merek, opname_gudang, opname_gudang_item)
-- ==============================================================================
DROP POLICY IF EXISTS public_all_merek ON public.merek;
DROP POLICY IF EXISTS service_role_all_merek ON public.merek;
CREATE POLICY service_role_all_merek ON public.merek FOR ALL TO service_role USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS service_role_all_opname_gudang ON public.opname_gudang;
CREATE POLICY service_role_all_opname_gudang ON public.opname_gudang FOR ALL TO service_role USING (true) WITH CHECK (true);

DROP POLICY IF EXISTS service_role_all_opname_gudang_item ON public.opname_gudang_item;
CREATE POLICY service_role_all_opname_gudang_item ON public.opname_gudang_item FOR ALL TO service_role USING (true) WITH CHECK (true);

-- ==============================================================================
-- 3. HARDENING SEARCH_PATH PADA SELURUH FUNGSI AKTIF (SET search_path = public, pg_temp)
-- ==============================================================================
ALTER FUNCTION public.fn_trg_transaksi_tabungan_update_saldo() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_produksi_harian_after_insert() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_potongan_kasbon_update_saldo() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_catat_log_aktivitas(uuid, character varying, character varying, character varying, character varying, character varying, character varying, uuid, text, jsonb, jsonb, bigint) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_cari_item_by_barcode(character varying) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_proses_pengiriman_konsinyasi() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_developer_account() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_rekonsiliasi_piutang_pelanggan(uuid) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_protect_default_customer() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_catat_pembayaran_konsinyasi(uuid, uuid, numeric, uuid, text, date) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_proses_kunjungan_konsinyasi(uuid, uuid, jsonb, text, text, uuid) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_guard_pelanggan_sales_driver() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_hitung_harga_jual_item(uuid, uuid) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_produksi_harian_after_update() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_trg_produksi_harian_after_delete() SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_hitung_tier_komisi_sales(numeric) SET search_path = public, pg_temp;
ALTER FUNCTION public.fn_buat_tagihan_konsinyasi(uuid[], uuid) SET search_path = public, pg_temp;

-- ==============================================================================
-- 4. PENGUNCIAN HAK AKSES EKSEKUSI API SUPABASE (REVOKE anon & authenticated)
-- ==============================================================================
REVOKE EXECUTE ON FUNCTION public.fn_trg_transaksi_tabungan_update_saldo() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_trg_produksi_harian_after_insert() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_trg_potongan_kasbon_update_saldo() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_catat_log_aktivitas(uuid, character varying, character varying, character varying, character varying, character varying, character varying, uuid, text, jsonb, jsonb, bigint) FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_cari_item_by_barcode(character varying) FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_trg_proses_pengiriman_konsinyasi() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_guard_developer_account() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_rekonsiliasi_piutang_pelanggan(uuid) FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_guard_protect_default_customer() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_catat_pembayaran_konsinyasi(uuid, uuid, numeric, uuid, text, date) FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_proses_kunjungan_konsinyasi(uuid, uuid, jsonb, text, text, uuid) FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_guard_pelanggan_sales_driver() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_hitung_harga_jual_item(uuid, uuid) FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_trg_produksi_harian_after_update() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_trg_produksi_harian_after_delete() FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_hitung_tier_komisi_sales(numeric) FROM PUBLIC, anon, authenticated;
REVOKE EXECUTE ON FUNCTION public.fn_buat_tagihan_konsinyasi(uuid[], uuid) FROM PUBLIC, anon, authenticated;

-- Berikan akses eksekusi hanya kepada role backend internal
GRANT EXECUTE ON ALL FUNCTIONS IN SCHEMA public TO postgres, service_role;

COMMIT;

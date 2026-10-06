-- ==============================================================================
-- KEREN ONE MIGRATION: 96_complete_table_rls_and_rpc_lockdown.sql
-- Resolusi Penuh Audit Keamanan Database & Supabase Hardening 100%:
-- 1. Mengaktifkan RLS & mengunci policy untuk seluruh tabel schema public tersisa
--    (grup_produk_barcode, merek, pelanggan_grup_barcode, skema_komisi_sales)
-- 2. Mengunci hak akses EXECUTE (REVOKE PUBLIC, anon, authenticated) dan hanya
--    mengizinkan postgres & service_role pada seluruh fungsi aplikasi aktif
-- ==============================================================================

BEGIN;

-- ==============================================================================
-- 1. HARDENING RLS PADA TABEL-TABEL PUBLIK TERSISA (100% TABLE RLS COMPLIANCE)
-- ==============================================================================
ALTER TABLE public.grup_produk_barcode ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.grup_produk_barcode FROM PUBLIC, anon, authenticated;
GRANT ALL ON public.grup_produk_barcode TO postgres, service_role;
DROP POLICY IF EXISTS service_role_all_grup_produk_barcode ON public.grup_produk_barcode;
CREATE POLICY service_role_all_grup_produk_barcode ON public.grup_produk_barcode FOR ALL TO service_role USING (true) WITH CHECK (true);

ALTER TABLE public.merek ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.merek FROM PUBLIC, anon, authenticated;
GRANT ALL ON public.merek TO postgres, service_role;
DROP POLICY IF EXISTS service_role_all_merek ON public.merek;
CREATE POLICY service_role_all_merek ON public.merek FOR ALL TO service_role USING (true) WITH CHECK (true);

ALTER TABLE public.pelanggan_grup_barcode ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.pelanggan_grup_barcode FROM PUBLIC, anon, authenticated;
GRANT ALL ON public.pelanggan_grup_barcode TO postgres, service_role;
DROP POLICY IF EXISTS service_role_all_pelanggan_grup_barcode ON public.pelanggan_grup_barcode;
CREATE POLICY service_role_all_pelanggan_grup_barcode ON public.pelanggan_grup_barcode FOR ALL TO service_role USING (true) WITH CHECK (true);

ALTER TABLE public.skema_komisi_sales ENABLE ROW LEVEL SECURITY;
REVOKE ALL ON public.skema_komisi_sales FROM PUBLIC, anon, authenticated;
GRANT ALL ON public.skema_komisi_sales TO postgres, service_role;
DROP POLICY IF EXISTS service_role_all_skema_komisi_sales ON public.skema_komisi_sales;
CREATE POLICY service_role_all_skema_komisi_sales ON public.skema_komisi_sales FOR ALL TO service_role USING (true) WITH CHECK (true);

-- ==============================================================================
-- 2. PENCABUTAN HAK EKSEKUSI API RPC PUBLIK PADA SELURUH FUNGSI APLIKASI
--    (Mencegah pemanggilan tak sah via Supabase REST API /rest/v1/rpc/*)
-- ==============================================================================
REVOKE EXECUTE ON FUNCTION public.fn_buat_tagihan_konsinyasi(uuid[], uuid) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_buat_tagihan_konsinyasi(uuid[], uuid) TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_cari_item_by_barcode(character varying) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_cari_item_by_barcode(character varying) TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_catat_log_aktivitas(uuid, character varying, character varying, character varying, character varying, character varying, character varying, uuid, text, jsonb, jsonb) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_catat_log_aktivitas(uuid, character varying, character varying, character varying, character varying, character varying, character varying, uuid, text, jsonb, jsonb) TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_catat_pembayaran_konsinyasi(uuid, uuid, numeric, uuid, text, date, numeric, text) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_catat_pembayaran_konsinyasi(uuid, uuid, numeric, uuid, text, date, numeric, text) TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_guard_developer_account() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_guard_developer_account() TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_guard_pelanggan_sales_driver() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_guard_pelanggan_sales_driver() TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_hitung_harga_jual_item(uuid, uuid) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_hitung_harga_jual_item(uuid, uuid) TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_hitung_tier_komisi_sales(numeric) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_hitung_tier_komisi_sales(numeric) TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_rekonsiliasi_piutang_pelanggan(uuid) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_rekonsiliasi_piutang_pelanggan(uuid) TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_trg_potongan_kasbon_update_saldo() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_trg_potongan_kasbon_update_saldo() TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_trg_proses_pengiriman_konsinyasi() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_trg_proses_pengiriman_konsinyasi() TO postgres, service_role;

REVOKE EXECUTE ON FUNCTION public.fn_trg_transaksi_tabungan_update_saldo() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_trg_transaksi_tabungan_update_saldo() TO postgres, service_role;

COMMIT;

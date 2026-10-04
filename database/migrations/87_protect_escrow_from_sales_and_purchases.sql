-- =============================================================================
-- Migration: 87_protect_escrow_from_sales_and_purchases.sql
-- Description: Enforce strict financial protection on Escrow / Savings cash accounts
--              1. Disallow setting is_default_pos = TRUE on escrow accounts
--              2. Database trigger on public.arus_kas blocking commercial sales & purchase
--                 transactions from touching escrow accounts
-- =============================================================================

BEGIN;

-- 1. Check constraint: Rekening escrow tidak boleh menjadi Default POS
ALTER TABLE public.akun_kas
    DROP CONSTRAINT IF EXISTS chk_akun_kas_escrow_no_default_pos;

ALTER TABLE public.akun_kas
    ADD CONSTRAINT chk_akun_kas_escrow_no_default_pos
    CHECK (NOT (is_escrow IS TRUE AND is_default_pos IS TRUE));

-- 2. Trigger proteksi arus kas untuk rekening titipan escrow
CREATE OR REPLACE FUNCTION public.fn_guard_escrow_cash_account()
RETURNS TRIGGER AS $$
DECLARE
    v_is_escrow BOOLEAN;
    v_nama_akun TEXT;
BEGIN
    SELECT is_escrow, nama_akun INTO v_is_escrow, v_nama_akun
    FROM public.akun_kas
    WHERE id = NEW.akun_kas_id;

    IF v_is_escrow IS TRUE THEN
        -- Kategori transaksi komersial (penjualan / pembelian) yang mutlak dilarang pada akun escrow:
        IF NEW.kategori IN (
            'penjualan', 'penjualan_pos', 'penjualan_pesanan', 'pelunasan_piutang', 'pembayaran_konsinyasi',
            'uang_muka_penjualan', 'retur_penjualan',
            'pembelian', 'pembelian_bahan', 'pelunasan_hutang_pembelian', 'uang_muka_pembelian', 'pembelian_aset',
            'biaya_pengadaan', 'kasbon', 'penarikan_gaji', 'beban_operasional', 'biaya_operasional'
        ) OR NEW.referensi_tabel IN (
            'pesanan', 'pesanan_penjualan', 'pembayaran_pesanan', 'pembelian', 'rincian_pembelian', 'pembayaran_pembelian',
            'surat_jalan', 'tagihan_kunjungan', 'kunjungan_konsinyasi'
        ) THEN
            RAISE EXCEPTION 'Proteksi Finansial: Akun kas ''%'' adalah rekening titipan tabungan karyawan (escrow) dan dilarang digunakan untuk transaksi penjualan maupun pembelian.', v_nama_akun;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_guard_escrow_cash_account ON public.arus_kas;
CREATE TRIGGER trg_guard_escrow_cash_account
    BEFORE INSERT OR UPDATE ON public.arus_kas
    FOR EACH ROW
    EXECUTE FUNCTION public.fn_guard_escrow_cash_account();

COMMIT;

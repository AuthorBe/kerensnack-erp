-- =============================================================================
-- Migration: 89_protect_transaksi_tabungan_escrow_only.sql
-- Description: Enforce that all savings transactions (transaksi_tabungan & arus_kas)
--              are strictly restricted to the dedicated Escrow savings cash account (is_escrow = TRUE).
-- =============================================================================

BEGIN;

-- 1. Trigger guard pada public.transaksi_tabungan
CREATE OR REPLACE FUNCTION public.fn_guard_transaksi_tabungan_escrow()
RETURNS TRIGGER AS $$
DECLARE
    v_is_escrow BOOLEAN;
    v_nama_akun TEXT;
BEGIN
    IF NEW.akun_kas_id IS NOT NULL THEN
        SELECT is_escrow, nama_akun INTO v_is_escrow, v_nama_akun
        FROM public.akun_kas
        WHERE id = NEW.akun_kas_id;

        IF v_is_escrow IS DISTINCT FROM TRUE THEN
            RAISE EXCEPTION 'Proteksi Database: Transaksi tabungan hanya diperbolehkan melalui Akun Kas Tabungan Karyawan (is_escrow = TRUE). Akun ''%'' bukan rekening tabungan/escrow.', COALESCE(v_nama_akun, 'Unknown');
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_guard_transaksi_tabungan_escrow ON public.transaksi_tabungan;
CREATE TRIGGER trg_guard_transaksi_tabungan_escrow
    BEFORE INSERT OR UPDATE ON public.transaksi_tabungan
    FOR EACH ROW
    EXECUTE FUNCTION public.fn_guard_transaksi_tabungan_escrow();

-- 2. Trigger guard pada public.arus_kas khusus kategori tabungan
CREATE OR REPLACE FUNCTION public.fn_guard_arus_kas_tabungan_escrow()
RETURNS TRIGGER AS $$
DECLARE
    v_is_escrow BOOLEAN;
    v_nama_akun TEXT;
BEGIN
    IF NEW.kategori IN ('setoran_tabungan', 'penarikan_tabungan') OR NEW.referensi_tabel = 'transaksi_tabungan' THEN
        SELECT is_escrow, nama_akun INTO v_is_escrow, v_nama_akun
        FROM public.akun_kas
        WHERE id = NEW.akun_kas_id;

        IF v_is_escrow IS DISTINCT FROM TRUE THEN
            RAISE EXCEPTION 'Proteksi Finansial: Arus kas transaksi tabungan karyawan hanya diperbolehkan pada Akun Kas Tabungan (is_escrow = TRUE). Akun ''%'' bukan rekening tabungan/escrow.', COALESCE(v_nama_akun, 'Unknown');
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_guard_arus_kas_tabungan_escrow ON public.arus_kas;
CREATE TRIGGER trg_guard_arus_kas_tabungan_escrow
    BEFORE INSERT OR UPDATE ON public.arus_kas
    FOR EACH ROW
    EXECUTE FUNCTION public.fn_guard_arus_kas_tabungan_escrow();

COMMIT;

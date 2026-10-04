-- =============================================================================
-- Migration: 88_protect_escrow_deactivation_with_balance.sql
-- Description: Enforce strict financial protection on Escrow cash accounts:
--              1. Disallow deactivating (status_aktif = FALSE) an escrow account while saldo_saat_ini > 0
--              2. Disallow deleting the system-critical escrow savings cash account
-- =============================================================================

BEGIN;

-- 1. Check constraint: Rekening escrow yang memiliki saldo berjalan > 0 wajib berstatus aktif
ALTER TABLE public.akun_kas
    DROP CONSTRAINT IF EXISTS chk_akun_kas_escrow_active_if_balance;

ALTER TABLE public.akun_kas
    ADD CONSTRAINT chk_akun_kas_escrow_active_if_balance
    CHECK (NOT (is_escrow IS TRUE AND status_aktif IS FALSE AND saldo_saat_ini > 0));

-- 2. Trigger proteksi mutasi akun kas escrow (blokir penghapusan & penonaktifan saat bersaldo)
CREATE OR REPLACE FUNCTION public.fn_guard_escrow_account_mutation()
RETURNS TRIGGER AS $$
BEGIN
    -- Blokir penghapusan akun escrow master sistem
    IF TG_OP = 'DELETE' THEN
        IF OLD.is_escrow IS TRUE THEN
            RAISE EXCEPTION 'Proteksi Finansial: Akun ''%'' adalah rekening titipan escrow tabungan karyawan master sistem dan tidak dapat dihapus.', OLD.nama_akun;
        END IF;
        RETURN OLD;
    END IF;

    -- Blokir penonaktifan akun escrow selagi masih ada saldo berjalan
    IF TG_OP = 'UPDATE' THEN
        IF NEW.is_escrow IS TRUE AND NEW.status_aktif IS FALSE AND (NEW.saldo_saat_ini > 0 OR OLD.saldo_saat_ini > 0) THEN
            RAISE EXCEPTION 'Proteksi Finansial: Akun ''%'' adalah rekening titipan tabungan karyawan (escrow) dan tidak dapat dinonaktifkan selagi masih memiliki saldo berjalan (Rp %). Lakukan pencairan seluruh saldo tabungan terlebih dahulu sebelum menonaktifkan akun.', NEW.nama_akun, to_char(COALESCE(NEW.saldo_saat_ini, OLD.saldo_saat_ini), 'FM999,999,999,999');
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_guard_escrow_account_mutation ON public.akun_kas;
CREATE TRIGGER trg_guard_escrow_account_mutation
    BEFORE UPDATE OF status_aktif, is_escrow, saldo_saat_ini OR DELETE ON public.akun_kas
    FOR EACH ROW
    EXECUTE FUNCTION public.fn_guard_escrow_account_mutation();

COMMIT;

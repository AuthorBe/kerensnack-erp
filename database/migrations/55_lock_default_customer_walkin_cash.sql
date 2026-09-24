-- ==============================================================================
-- MIGRASI 55: PENGUNCIAN PERMANEN PELANGGAN DEFAULT (CUST-001 / WALK-IN CASH)
-- Standar: Zero Persistent Mock Data (AGENTS.md)
-- Deskripsi: Mencegah penghapusan, penggantian kode, atau penonaktifan pelanggan 
--            default CUST-001 di level database (PostgreSQL Trigger).
-- ==============================================================================

BEGIN;

CREATE OR REPLACE FUNCTION public.fn_guard_protect_default_customer()
RETURNS TRIGGER AS $$
BEGIN
    -- 1. Blokir mutlak operasi DELETE untuk CUST-001
    IF TG_OP = 'DELETE' THEN
        IF OLD.kode_pelanggan = 'CUST-001' OR UPPER(TRIM(OLD.nama_toko)) LIKE '%WALK-IN CASH%' THEN
            RAISE EXCEPTION 'Pelanggan default sistem (CUST-001 / Toko Umum / Walk-in Cash) terkunci permanen dan tidak dapat dihapus.';
        END IF;
        RETURN OLD;
    END IF;

    -- 2. Blokir perubahan kode atau penonaktifan untuk CUST-001 saat UPDATE
    IF TG_OP = 'UPDATE' THEN
        IF OLD.kode_pelanggan = 'CUST-001' THEN
            IF NEW.kode_pelanggan != 'CUST-001' THEN
                RAISE EXCEPTION 'Kode pelanggan default sistem (CUST-001) terkunci dan tidak boleh diubah.';
            END IF;
            IF NEW.status_aktif = FALSE THEN
                RAISE EXCEPTION 'Pelanggan default sistem (CUST-001) wajib tetap aktif untuk operasional kasir POS.';
            END IF;
        END IF;
        RETURN NEW;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_guard_protect_default_customer ON public.pelanggan;
CREATE TRIGGER trg_guard_protect_default_customer
BEFORE UPDATE OR DELETE ON public.pelanggan
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_protect_default_customer();

COMMIT;

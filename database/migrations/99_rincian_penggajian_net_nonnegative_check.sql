-- Migration 99: Tambah CHECK constraint untuk memastikan gaji bersih diterima tidak negatif
-- Mengimplementasikan pengaman Lapis 5 (Database Level) untuk integritas finansial payroll

BEGIN;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 
        FROM pg_constraint 
        WHERE conname = 'chk_rincian_penggajian_net_nonneg' 
          AND conrelid = 'public.rincian_penggajian'::regclass
    ) THEN
        ALTER TABLE public.rincian_penggajian
            ADD CONSTRAINT chk_rincian_penggajian_net_nonneg 
            CHECK (gaji_bersih_diterima >= 0);
    END IF;
END $$;

COMMIT;

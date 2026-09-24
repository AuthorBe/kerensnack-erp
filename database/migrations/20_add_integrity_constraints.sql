-- ==============================================================================
-- Migrasi 20: Database Integrity - Add Numeric Check Constraints
-- Ref: PRD Pembersihan Proyek (TASK-013 / Fase 3)
-- ==============================================================================

-- 1. Constraint sisa_tagihan pesanan tidak boleh bernilai negatif (>= 0)
ALTER TABLE public.pesanan 
    DROP CONSTRAINT IF EXISTS chk_pesanan_sisa_tagihan_non_negative,
    ADD CONSTRAINT chk_pesanan_sisa_tagihan_non_negative CHECK (sisa_tagihan >= 0);

-- 2. Constraint total_dibayar pesanan tidak boleh bernilai negatif (>= 0)
ALTER TABLE public.pesanan 
    DROP CONSTRAINT IF EXISTS chk_pesanan_total_dibayar_non_negative,
    ADD CONSTRAINT chk_pesanan_total_dibayar_non_negative CHECK (total_dibayar >= 0);

-- 3. Constraint total_netto pesanan tidak boleh bernilai negatif (>= 0)
ALTER TABLE public.pesanan 
    DROP CONSTRAINT IF EXISTS chk_pesanan_total_netto_non_negative,
    ADD CONSTRAINT chk_pesanan_total_netto_non_negative CHECK (total_netto >= 0);

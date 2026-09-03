-- =============================================================================
-- MIGRATION 08: TAMBAH TIPE PEMBAYARAN 'qris' & 'transfer'
-- Mengatasi Check Violation: pesanan_tipe_pembayaran_check
-- =============================================================================

-- 1. Perbarui check constraint pada tabel pesanan
ALTER TABLE public.pesanan DROP CONSTRAINT IF EXISTS pesanan_tipe_pembayaran_check;

ALTER TABLE public.pesanan ADD CONSTRAINT pesanan_tipe_pembayaran_check 
CHECK (tipe_pembayaran IN (
    'cash', 
    'qris', 
    'transfer', 
    'tempo_7_hari', 
    'tempo_14_hari', 
    'tempo_30_hari', 
    'konsinyasi', 
    'sebagian', 
    'kredit'
));

-- 2. Perbarui check constraint default pembayaran pada tabel pelanggan
ALTER TABLE public.pelanggan DROP CONSTRAINT IF EXISTS pelanggan_tipe_pembayaran_default_check;

ALTER TABLE public.pelanggan ADD CONSTRAINT pelanggan_tipe_pembayaran_default_check 
CHECK (tipe_pembayaran_default IN (
    'cash', 
    'qris', 
    'transfer', 
    'tempo_7_hari', 
    'tempo_14_hari', 
    'tempo_30_hari', 
    'konsinyasi'
));

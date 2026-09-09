-- ==============================================================================
-- 10_add_pos_cash_change_fields.sql
-- Migrasi Database: Tambah kolom uang_diterima dan kembalian pada tabel pesanan
-- Untuk audit trail pembayaran tunai & kontrol kembalian kasir POS.
-- ==============================================================================

-- 1. Tambah kolom uang_diterima jika belum ada
ALTER TABLE public.pesanan 
ADD COLUMN IF NOT EXISTS uang_diterima NUMERIC(15, 2) DEFAULT 0.00;

-- 2. Tambah kolom kembalian jika belum ada
ALTER TABLE public.pesanan 
ADD COLUMN IF NOT EXISTS kembalian NUMERIC(15, 2) DEFAULT 0.00;

-- 3. Berikan komentar pada kolom untuk dokumentasi schema
COMMENT ON COLUMN public.pesanan.uang_diterima IS 'Nominal uang tunai fisik yang diserahkan pelanggan pada transaksi kasir POS';
COMMENT ON COLUMN public.pesanan.kembalian IS 'Nominal uang kembalian yang harus diberikan kasir ke pelanggan';

-- 4. Backfill untuk data yang sudah ada (default uang_diterima = total_dibayar, kembalian = 0)
UPDATE public.pesanan 
SET uang_diterima = total_dibayar, kembalian = 0.00 
WHERE uang_diterima IS NULL OR uang_diterima = 0.00;

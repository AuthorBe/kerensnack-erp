-- ==============================================================================
-- KEREN SNACK - DATABASE MIGRATION
-- Add Missing Indexes for Performance Optimization
-- ==============================================================================

-- 1. Index on riwayat_stok(item_id)
-- Ledger mutasi stok ini akan membesar seiring berjalannya waktu.
CREATE INDEX IF NOT EXISTS idx_riwayat_stok_item ON public.riwayat_stok(item_id);

-- 2. Index on item_pesanan(pesanan_id)
-- Relasi detail item terhadap pesanan sering diquery saat membuka nota/faktur.
CREATE INDEX IF NOT EXISTS idx_item_pesanan_pesanan ON public.item_pesanan(pesanan_id);

-- 3. Index on rincian_pembelian(pembelian_id)
CREATE INDEX IF NOT EXISTS idx_rincian_pembelian_pembelian ON public.rincian_pembelian(pembelian_id);

-- 4. Index on item(grup_id)
CREATE INDEX IF NOT EXISTS idx_item_grup ON public.item(grup_id);

-- 5. Index on arus_kas(akun_kas_id)
CREATE INDEX IF NOT EXISTS idx_arus_kas_akun ON public.arus_kas(akun_kas_id);

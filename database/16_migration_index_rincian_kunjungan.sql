-- Migration 16: Penambahan Index Performa pada Rincian Kunjungan Konsinyasi
-- Menghilangkan Full Table Scan berulang pada riwayat kunjungan dan agregasi opname

CREATE INDEX IF NOT EXISTS idx_rkk_kunjungan_id ON public.rincian_kunjungan_konsinyasi (kunjungan_id);
CREATE INDEX IF NOT EXISTS idx_rkk_item_id ON public.rincian_kunjungan_konsinyasi (item_id);

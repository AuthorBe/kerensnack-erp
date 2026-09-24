-- ==============================================================================
-- Migrasi 19: Database Optimization - Add Missing Composite Transaction Indexes
-- Ref: PRD Pembersihan Proyek (TASK-012 / Fase 3)
-- ==============================================================================

-- 1. Composite index riwayat_stok (item_id + urutan waktu DESC)
-- Mengoptimasi kartu stok per item barang dan ledger audit trail
CREATE INDEX IF NOT EXISTS idx_riwayat_stok_item_waktu 
    ON public.riwayat_stok (item_id, dibuat_pada DESC);

-- 2. Index riwayat_stok urutan waktu DESC global
-- Mengoptimasi dashboard monitoring mutasi stok terkini (InventoryController::index)
CREATE INDEX IF NOT EXISTS idx_riwayat_stok_dibuat_pada 
    ON public.riwayat_stok (dibuat_pada DESC);

-- 3. Composite index pesanan per pelanggan dan status pembayaran
-- Mengoptimasi filter riwayat piutang & pesanan per toko (CustomerController / CustomerOrderController)
CREATE INDEX IF NOT EXISTS idx_pesanan_pelanggan_status 
    ON public.pesanan (pelanggan_id, status_pembayaran);

-- 4. Composite index pesanan status pemrosesan dan tanggal pesanan DESC
-- Mengoptimasi filter pipeline PO, pesanan siap kirim, dan antrean logistik
CREATE INDEX IF NOT EXISTS idx_pesanan_status_proses_tanggal 
    ON public.pesanan (status_pemrosesan, tanggal_pesanan DESC);

-- 5. Composite index item pesanan (item_id + pesanan_id)
-- Mengoptimasi coverage scan lookup produk pada transaksi pesanan
CREATE INDEX IF NOT EXISTS idx_item_pesanan_item_pesanan 
    ON public.item_pesanan (item_id, pesanan_id);

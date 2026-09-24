-- ============================================================================
-- Migrasi Database 60: Penambahan Kolom Catatan Bonus pada public.item_pesanan
-- Modul: Pesanan Pelanggan (Customer Orders) & Logistik Gudang
-- ============================================================================

BEGIN;

-- 1. Tambahkan kolom catatan_bonus pada tabel public.item_pesanan jika belum ada
ALTER TABLE public.item_pesanan 
ADD COLUMN IF NOT EXISTS catatan_bonus VARCHAR(255) NULL;

-- 2. Berikan komentar deskriptif untuk dokumentasi skema
COMMENT ON COLUMN public.item_pesanan.catatan_bonus IS 'Keterangan/alasan pemberian barang bonus oleh gudang/sales (cth: Bonus Promo Gudang, Tester Produk Baru, Bonus Toko, Pengganti / Kompensasi, atau catatan khusus).';

COMMIT;

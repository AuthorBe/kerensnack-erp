-- ==============================================================================
-- Migrasi 39: Pembersihan Kolom Varian Rasa & Barcode pada Barang Jadi
-- ==============================================================================
-- Keterangan:
-- 1. Barcode barang jadi secara arsitektur mengikuti grup_produk.barcode_universal 
--    karena barcode universal pabrik tercetak pada kemasan luar bersama.
-- 2. Varian rasa telah melebur langsung ke dalam nama_item lengkap.
-- 3. Data lama pada tipe_item = 'barang_jadi' dibersihkan menjadi NULL agar tidak redundan.
-- ==============================================================================

BEGIN;

UPDATE public.item
SET barcode = NULL,
    varian_rasa = NULL
WHERE tipe_item = 'barang_jadi';

COMMIT;

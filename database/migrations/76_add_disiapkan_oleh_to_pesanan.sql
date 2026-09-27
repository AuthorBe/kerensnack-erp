-- ==============================================================================
-- Migrasi: 76_add_disiapkan_oleh_to_pesanan.sql
-- Keterangan: Menambahkan kolom disiapkan_oleh pada tabel pesanan untuk mencatat
--             staf gudang yang memverifikasi fisik barang dan menekan "Siap Dikirim"
-- ==============================================================================

BEGIN;

ALTER TABLE public.pesanan
    ADD COLUMN IF NOT EXISTS disiapkan_oleh UUID REFERENCES public.pengguna(id) ON DELETE SET NULL;

CREATE INDEX IF NOT EXISTS idx_pesanan_disiapkan_oleh 
    ON public.pesanan(disiapkan_oleh);

COMMENT ON COLUMN public.pesanan.disiapkan_oleh IS 'ID Pengguna / Staf Gudang yang memverifikasi dan menyiapkan fisik pesanan PO (Siap Dikirim)';

COMMIT;

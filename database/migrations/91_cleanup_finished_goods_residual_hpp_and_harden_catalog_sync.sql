-- ==============================================================================
-- MIGRASI 91: PEMBERSIHAN HPP RESIDUAL BARANG JADI & SINKRONISASI HARGA KATALOG
-- Repositori: KEREN ONE ERP
-- Tanggal: 2026-10-05
-- ==============================================================================
-- Deskripsi:
-- 1. Membersihkan nilai residu HPP Rp 10.000 pada barang jadi yang diproduksi mandiri
--    (HPP barang jadi murni bersumber dari Resep BOM + Upah Borongan).
-- 2. Menambahkan trigger sinkronisasi otomatis dari pemasok_item ke item.harga_pokok_pembelian
--    apabila vendor yang diupdate adalah pemasok_utama_id dari item terkait.
-- 3. Menambahkan trigger sinkronisasi otomatis dari grup_produk_barcode ke grup_produk.barcode_universal
--    apabila barcode default ditambahkan/diperbarui.
-- ==============================================================================

BEGIN;

-- 1. Pembersihan Nilai Semu HPP Rp 10.000 pada Barang Jadi Mandiri
-- Produk maklon titip jual (PROD-0149, PROD-0150, PROD-0151 CIOMY) tetap dipreservasi
UPDATE public.item
SET harga_pokok_pembelian = 0.00,
    diubah_pada = NOW()
WHERE tipe_item = 'barang_jadi' 
  AND (pemasok_utama_id IS NULL OR kode_sku NOT IN ('PROD-0149', 'PROD-0150', 'PROD-0151'))
  AND harga_pokok_pembelian = 10000.00;

-- 2. Fungsi & Trigger Sinkronisasi Otomatis Harga Katalog Vendor Utama ke item.harga_pokok_pembelian
CREATE OR REPLACE FUNCTION public.trg_sync_pemasok_item_to_item_hpp()
RETURNS TRIGGER LANGUAGE plpgsql SECURITY DEFINER AS $$
BEGIN
    -- Jika item memiliki pemasok_utama_id yang cocok dengan vendor yang di-update di katalog
    UPDATE public.item
    SET harga_pokok_pembelian = NEW.harga_beli,
        diubah_pada = NOW()
    WHERE id = NEW.item_id 
      AND pemasok_utama_id = NEW.pemasok_id
      AND harga_pokok_pembelian != NEW.harga_beli;

    RETURN NEW;
END;
$$;

REVOKE EXECUTE ON FUNCTION public.trg_sync_pemasok_item_to_item_hpp() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.trg_sync_pemasok_item_to_item_hpp() TO postgres, service_role;

DROP TRIGGER IF EXISTS trg_sync_pemasok_item_to_item_hpp ON public.pemasok_item;
CREATE TRIGGER trg_sync_pemasok_item_to_item_hpp
AFTER INSERT OR UPDATE OF harga_beli ON public.pemasok_item
FOR EACH ROW
EXECUTE FUNCTION public.trg_sync_pemasok_item_to_item_hpp();

-- 3. Fungsi & Trigger Sinkronisasi Otomatis Barcode Default ke grup_produk.barcode_universal
CREATE OR REPLACE FUNCTION public.trg_sync_grup_barcode_default_to_grup_produk()
RETURNS TRIGGER LANGUAGE plpgsql SECURITY DEFINER AS $$
BEGIN
    IF NEW.is_default = TRUE AND NEW.status_aktif = TRUE THEN
        -- Pastikan barcode default lain pada grup ini dinonaktifkan status default-nya
        UPDATE public.grup_produk_barcode
        SET is_default = FALSE,
            diubah_pada = NOW()
        WHERE grup_produk_id = NEW.grup_produk_id 
          AND id != NEW.id 
          AND is_default = TRUE;

        -- Sinkronkan nilai barcode ke tabel grup_produk
        UPDATE public.grup_produk
        SET barcode_universal = NEW.barcode,
            diubah_pada = NOW()
        WHERE id = NEW.grup_produk_id 
          AND (barcode_universal IS NULL OR barcode_universal != NEW.barcode);
    END IF;

    RETURN NEW;
END;
$$;

REVOKE EXECUTE ON FUNCTION public.trg_sync_grup_barcode_default_to_grup_produk() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.trg_sync_grup_barcode_default_to_grup_produk() TO postgres, service_role;

DROP TRIGGER IF EXISTS trg_sync_grup_barcode_default_to_grup_produk ON public.grup_produk_barcode;
CREATE TRIGGER trg_sync_grup_barcode_default_to_grup_produk
AFTER INSERT OR UPDATE OF barcode, is_default, status_aktif ON public.grup_produk_barcode
FOR EACH ROW
EXECUTE FUNCTION public.trg_sync_grup_barcode_default_to_grup_produk();

COMMIT;

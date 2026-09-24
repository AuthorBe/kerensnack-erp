-- database/40_clean_dead_columns_and_harden_relations.sql
-- Pembersihan kolom mati, normalisasi relasi master, dan hardening skema PostgreSQL

BEGIN;

-- 1. Penegasan Integritas: Barang Jadi Wajib Terhubung ke Grup Kemasan
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_barang_jadi_wajib_grup'
    ) THEN
        ALTER TABLE public.item 
        ADD CONSTRAINT check_barang_jadi_wajib_grup 
        CHECK (tipe_item != 'barang_jadi' OR grup_id IS NOT NULL);
    END IF;
END $$;

-- 2. Drop Kolom Mati & Redundan Murni
ALTER TABLE public.item DROP COLUMN IF EXISTS id_legacy_produk;
ALTER TABLE public.item DROP COLUMN IF EXISTS konversi_distribusi_ke_dasar;
ALTER TABLE public.kelompok_upah_borongan DROP COLUMN IF EXISTS id_legacy;
ALTER TABLE public.grup_produk DROP COLUMN IF EXISTS merek;
ALTER TABLE public.pemasok DROP COLUMN IF EXISTS detail_bank;
ALTER TABLE public.grup_produk_harga_level DROP COLUMN IF EXISTS nama_level;

-- 3. Drop Kolom Barcode & Varian Rasa pada Item
DROP INDEX IF EXISTS public.idx_item_barcode;
ALTER TABLE public.item DROP COLUMN IF EXISTS barcode;
ALTER TABLE public.item DROP COLUMN IF EXISTS varian_rasa;

-- 4. Perbarui Stored Procedure fn_cari_item_by_barcode (Murni Universal Barcode Kemasan)
CREATE OR REPLACE FUNCTION public.fn_cari_item_by_barcode(p_barcode character varying)
RETURNS jsonb LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
    v_items JSONB;
BEGIN
    SELECT jsonb_agg(
        jsonb_build_object(
            'item_id', i.id,
            'kode_sku', i.kode_sku,
            'nama_item', i.nama_item,
            'grup_nama', gp.nama_grup,
            'stok_fisik', i.stok_fisik_saat_ini,
            'satuan_dasar', i.satuan_dasar
        )
    ) INTO v_items
    FROM public.item i
    JOIN public.grup_produk gp ON i.grup_id = gp.id
    WHERE gp.barcode_universal = p_barcode
      AND i.status_aktif = TRUE;

    IF v_items IS NULL THEN
        RETURN jsonb_build_object('ditemukan', false, 'pesan', 'Barcode produk tidak ditemukan');
    END IF;

    RETURN jsonb_build_object('ditemukan', true, 'total_varian', jsonb_array_length(v_items), 'data', v_items);
END;
$$;

-- 5. Bersihkan data percobaan grup pelanggan yang mengarah ke Level 5, 8, 12 (Reset ke Level 1 Ritel Standar)
UPDATE public.grup_pelanggan
SET default_level_harga = 1
WHERE default_level_harga IN (5, 8, 12);

COMMIT;

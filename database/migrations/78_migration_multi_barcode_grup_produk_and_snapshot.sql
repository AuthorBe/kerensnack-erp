-- ==============================================================================
-- MIGRASI 78: MULTI-BARCODE GRUP PRODUK, MAPPING TOKO, DAN SNAPSHOT TRANSAKSI
-- Tanggal: 2026-09-29
-- Deskripsi:
-- 1. Membuat tabel public.grup_produk_barcode untuk mendukung multi-barcode per grup kemasan.
-- 2. Memindahkan barcode eksisting dari grup_produk.barcode_universal ke grup_produk_barcode.
-- 3. Membuat tabel public.pelanggan_grup_barcode untuk preferensi barcode khusus toko per grup.
-- 4. Menambahkan kolom barcode_universal pada public.item_pesanan untuk snapshot transaksi immutable.
-- 5. Memperbarui RPC scanner fn_cari_item_by_barcode agar mendukung multi-barcode.
-- ==============================================================================

BEGIN;

-- 1. TABEL MULTI-BARCODE GRUP PRODUK
CREATE TABLE IF NOT EXISTS public.grup_produk_barcode (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    grup_produk_id UUID NOT NULL REFERENCES public.grup_produk(id) ON DELETE CASCADE,
    barcode VARCHAR(100) NOT NULL,
    label_barcode VARCHAR(100) NOT NULL DEFAULT 'Standar / Pabrik',
    is_default BOOLEAN NOT NULL DEFAULT FALSE,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_grup_barcode UNIQUE (grup_produk_id, barcode)
);

CREATE INDEX IF NOT EXISTS idx_grup_produk_barcode_grup ON public.grup_produk_barcode(grup_produk_id);
CREATE INDEX IF NOT EXISTS idx_grup_produk_barcode_code ON public.grup_produk_barcode(barcode);

-- Migrasi data barcode yang sudah ada ke tabel baru
INSERT INTO public.grup_produk_barcode (grup_produk_id, barcode, label_barcode, is_default, status_aktif)
SELECT id, TRIM(barcode_universal), 'Standar / Pabrik', TRUE, TRUE
FROM public.grup_produk
WHERE barcode_universal IS NOT NULL AND TRIM(barcode_universal) != ''
ON CONFLICT (grup_produk_id, barcode) DO UPDATE SET is_default = TRUE;

-- 2. TABEL MAPPING PREFERENSI BARCODE PER PELANGGAN (TOKO)
CREATE TABLE IF NOT EXISTS public.pelanggan_grup_barcode (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id) ON DELETE CASCADE,
    grup_produk_id UUID NOT NULL REFERENCES public.grup_produk(id) ON DELETE CASCADE,
    barcode VARCHAR(100) NOT NULL,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_pelanggan_grup_barcode UNIQUE (pelanggan_id, grup_produk_id)
);

CREATE INDEX IF NOT EXISTS idx_pelanggan_grup_barcode_pel ON public.pelanggan_grup_barcode(pelanggan_id);
CREATE INDEX IF NOT EXISTS idx_pelanggan_grup_barcode_grp ON public.pelanggan_grup_barcode(grup_produk_id);

-- 3. KOLOM SNAPSHOT TRANSAKSI PADA ITEM PESANAN
ALTER TABLE public.item_pesanan ADD COLUMN IF NOT EXISTS barcode_universal VARCHAR(100);

-- 4. UPDATE RPC SCANNER UNTUK PENCARIAN VARIAN RASA PER KEMASAN
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
            'satuan_dasar', gp.satuan_dasar,
            'stok_fisik_saat_ini', i.stok_fisik_saat_ini
        ) ORDER BY i.nama_item ASC
    ) INTO v_items
    FROM public.item i
    JOIN public.grup_produk gp ON i.grup_id = gp.id
    WHERE (
        gp.barcode_universal = p_barcode 
        OR i.kode_sku = p_barcode 
        OR EXISTS (
            SELECT 1 FROM public.grup_produk_barcode gpb 
            WHERE gpb.grup_produk_id = gp.id 
              AND gpb.barcode = p_barcode 
              AND gpb.status_aktif = TRUE
        )
    )
      AND i.status_aktif = TRUE;

    IF v_items IS NULL THEN
        RETURN jsonb_build_object('ditemukan', false, 'pesan', 'Barcode produk tidak ditemukan');
    END IF;

    RETURN jsonb_build_object('ditemukan', true, 'total_varian', jsonb_array_length(v_items), 'data', v_items);
END;
$$;

-- 5. SUPABASE POSTGREST & SECURITY DEFINER GRANTS (SESUAI PROTOKOL AGENTS.md)
REVOKE EXECUTE ON FUNCTION public.fn_cari_item_by_barcode(character varying) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_cari_item_by_barcode(character varying) TO postgres, service_role;

GRANT SELECT, INSERT, UPDATE, DELETE ON public.grup_produk_barcode TO authenticated;
GRANT ALL ON public.grup_produk_barcode TO service_role;
ALTER TABLE public.grup_produk_barcode ENABLE ROW LEVEL SECURITY;

GRANT SELECT, INSERT, UPDATE, DELETE ON public.pelanggan_grup_barcode TO authenticated;
GRANT ALL ON public.pelanggan_grup_barcode TO service_role;
ALTER TABLE public.pelanggan_grup_barcode ENABLE ROW LEVEL SECURITY;

COMMIT;

-- ==============================================================================
-- KEREN SNACK ERP - DATABASE MIGRATION 63: LEVEL HARGA & DISKON DINAMIS PER MEREK
-- ==============================================================================

BEGIN;

-- 1. Buat Tabel Relasi: grup_pelanggan_level_merek
CREATE TABLE IF NOT EXISTS public.grup_pelanggan_level_merek (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    grup_pelanggan_id UUID NOT NULL REFERENCES public.grup_pelanggan(id) ON DELETE CASCADE,
    merek_id UUID NOT NULL REFERENCES public.merek(id) ON DELETE CASCADE,
    level_harga INT NULL REFERENCES public.master_level_harga(level_nomor) ON UPDATE CASCADE ON DELETE RESTRICT,
    diskon_persen NUMERIC(5, 2) NOT NULL DEFAULT 0.00 CHECK (diskon_persen BETWEEN 0.00 AND 100.00),
    diskon_nominal NUMERIC(15, 2) NOT NULL DEFAULT 0.00 CHECK (diskon_nominal >= 0.00),
    is_dijual BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_grup_merek UNIQUE (grup_pelanggan_id, merek_id)
);

-- Indeks Relasi untuk Performa Pencarian Cepat
CREATE INDEX IF NOT EXISTS idx_gplm_grup_pelanggan ON public.grup_pelanggan_level_merek(grup_pelanggan_id);
CREATE INDEX IF NOT EXISTS idx_gplm_merek ON public.grup_pelanggan_level_merek(merek_id);
CREATE INDEX IF NOT EXISTS idx_gplm_status_jual ON public.grup_pelanggan_level_merek(is_dijual);

-- 2. Aktifkan Row Level Security (RLS) & Kebijakan Akses
ALTER TABLE public.grup_pelanggan_level_merek ENABLE ROW LEVEL SECURITY;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies WHERE tablename = 'grup_pelanggan_level_merek' AND policyname = 'service_role_all_gplm'
    ) THEN
        CREATE POLICY service_role_all_gplm ON public.grup_pelanggan_level_merek FOR ALL TO service_role USING (true) WITH CHECK (true);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_policies WHERE tablename = 'grup_pelanggan_level_merek' AND policyname = 'public_all_gplm'
    ) THEN
        CREATE POLICY public_all_gplm ON public.grup_pelanggan_level_merek FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- 3. Inisialisasi Data Transisi untuk Seluruh Grup Pelanggan Eksisting
INSERT INTO public.grup_pelanggan_level_merek (
    grup_pelanggan_id,
    merek_id,
    level_harga,
    diskon_persen,
    diskon_nominal,
    is_dijual,
    dibuat_pada,
    diubah_pada
)
SELECT 
    gp.id AS grup_pelanggan_id,
    m.id AS merek_id,
    COALESCE(gp.default_level_harga, 1) AS level_harga,
    COALESCE(gp.diskon_persen_default, 0.00) AS diskon_persen,
    COALESCE(gp.diskon_nominal_default, 0.00) AS diskon_nominal,
    TRUE AS is_dijual,
    NOW(),
    NOW()
FROM public.grup_pelanggan gp
CROSS JOIN public.merek m
WHERE m.status_aktif = TRUE
ON CONFLICT (grup_pelanggan_id, merek_id) DO NOTHING;

-- 4. Trigger Otomatis Pembuatan Baris Merek saat Grup Pelanggan Baru Dibuat
CREATE OR REPLACE FUNCTION public.fn_trg_grup_pelanggan_after_insert()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $$
BEGIN
    INSERT INTO public.grup_pelanggan_level_merek (
        grup_pelanggan_id,
        merek_id,
        level_harga,
        diskon_persen,
        diskon_nominal,
        is_dijual,
        dibuat_pada,
        diubah_pada
    )
    SELECT 
        NEW.id,
        m.id,
        COALESCE(NEW.default_level_harga, 1),
        COALESCE(NEW.diskon_persen_default, 0.00),
        COALESCE(NEW.diskon_nominal_default, 0.00),
        TRUE,
        NOW(),
        NOW()
    FROM public.merek m
    WHERE m.status_aktif = TRUE
    ON CONFLICT (grup_pelanggan_id, merek_id) DO NOTHING;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_grup_pelanggan_after_insert ON public.grup_pelanggan;
CREATE TRIGGER trg_grup_pelanggan_after_insert
AFTER INSERT ON public.grup_pelanggan
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_grup_pelanggan_after_insert();

-- 5. Trigger Otomatis Inisialisasi Grup Eksisting saat Merek Baru Dibuat
CREATE OR REPLACE FUNCTION public.fn_trg_merek_after_insert()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $$
BEGIN
    IF NEW.status_aktif = TRUE THEN
        INSERT INTO public.grup_pelanggan_level_merek (
            grup_pelanggan_id,
            merek_id,
            level_harga,
            diskon_persen,
            diskon_nominal,
            is_dijual,
            dibuat_pada,
            diubah_pada
        )
        SELECT 
            gp.id,
            NEW.id,
            COALESCE(gp.default_level_harga, 1),
            COALESCE(gp.diskon_persen_default, 0.00),
            COALESCE(gp.diskon_nominal_default, 0.00),
            TRUE,
            NOW(),
            NOW()
        FROM public.grup_pelanggan gp
        ON CONFLICT (grup_pelanggan_id, merek_id) DO NOTHING;
    END IF;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_merek_after_insert ON public.merek;
CREATE TRIGGER trg_merek_after_insert
AFTER INSERT ON public.merek
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_merek_after_insert();

-- 6. Perbarui Stored Procedure fn_hitung_harga_jual_item
CREATE OR REPLACE FUNCTION public.fn_hitung_harga_jual_item(
    p_item_id UUID,
    p_pelanggan_id UUID
)
RETURNS JSONB
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $$
DECLARE
    v_grup_produk_id UUID;
    v_nama_item VARCHAR;
    v_merek_id UUID;
    v_nama_merek VARCHAR;
    v_grup_pelanggan_id UUID;
    v_nama_grup_pelanggan VARCHAR;
    v_level_harga INT;
    v_diskon_persen NUMERIC(5, 2);
    v_diskon_nominal NUMERIC(15, 2);
    v_is_dijual BOOLEAN;
    v_harga_pcs_dasar NUMERIC(15, 2);
    v_harga_pcs_netto NUMERIC(15, 2);
    v_grup_umum_id UUID;
    v_found_merek_rule BOOLEAN := FALSE;
BEGIN
    -- 1. Ambil info item, grup produk, dan merek
    SELECT 
        i.grup_id, 
        i.nama_item, 
        gp.merek_id, 
        COALESCE(m.nama_merek, 'KEREN SNACK')
    INTO 
        v_grup_produk_id, 
        v_nama_item, 
        v_merek_id, 
        v_nama_merek
    FROM public.item i
    LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
    LEFT JOIN public.merek m ON gp.merek_id = m.id
    WHERE i.id = p_item_id;

    IF v_grup_produk_id IS NULL THEN
        RETURN jsonb_build_object(
            'error', true,
            'code', 'ITEM_NOT_FOUND',
            'message', 'Item produk tidak ditemukan.'
        );
    END IF;

    -- 2. Ambil aturan harga dari grup_pelanggan yang terhubung
    IF p_pelanggan_id IS NOT NULL THEN
        SELECT 
            p.grup_pelanggan_id,
            gp.nama_grup
        INTO 
            v_grup_pelanggan_id,
            v_nama_grup_pelanggan
        FROM public.pelanggan p
        JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
        WHERE p.id = p_pelanggan_id;

        IF v_grup_pelanggan_id IS NOT NULL AND v_merek_id IS NOT NULL THEN
            SELECT 
                gplm.level_harga,
                COALESCE(gplm.diskon_persen, 0.00),
                COALESCE(gplm.diskon_nominal, 0.00),
                gplm.is_dijual,
                TRUE
            INTO 
                v_level_harga,
                v_diskon_persen,
                v_diskon_nominal,
                v_is_dijual,
                v_found_merek_rule
            FROM public.grup_pelanggan_level_merek gplm
            WHERE gplm.grup_pelanggan_id = v_grup_pelanggan_id AND gplm.merek_id = v_merek_id;
        END IF;

        -- Fallback jika grup produk tidak memiliki merek_id atau aturan merek belum diset
        IF v_found_merek_rule IS NOT TRUE AND v_grup_pelanggan_id IS NOT NULL THEN
            SELECT 
                COALESCE(gp.default_level_harga, 1),
                COALESCE(gp.diskon_persen_default, 0.00),
                COALESCE(gp.diskon_nominal_default, 0.00),
                TRUE
            INTO 
                v_level_harga,
                v_diskon_persen,
                v_diskon_nominal,
                v_is_dijual
            FROM public.grup_pelanggan gp
            WHERE gp.id = v_grup_pelanggan_id;
        END IF;
    END IF;

    -- 3. Logika untuk Pelanggan Umum / Walk-in Cash (p_pelanggan_id IS NULL)
    IF p_pelanggan_id IS NULL THEN
        -- Cari grup umum jika ada
        SELECT id, nama_grup INTO v_grup_umum_id, v_nama_grup_pelanggan 
        FROM public.grup_pelanggan 
        WHERE kode_grup = 'GRP-UMUM' OR nama_grup ILIKE '%umum%' 
        LIMIT 1;

        IF v_grup_umum_id IS NOT NULL AND v_merek_id IS NOT NULL THEN
            SELECT 
                gplm.level_harga,
                COALESCE(gplm.diskon_persen, 0.00),
                COALESCE(gplm.diskon_nominal, 0.00),
                gplm.is_dijual,
                TRUE
            INTO 
                v_level_harga,
                v_diskon_persen,
                v_diskon_nominal,
                v_is_dijual,
                v_found_merek_rule
            FROM public.grup_pelanggan_level_merek gplm
            WHERE gplm.grup_pelanggan_id = v_grup_umum_id AND gplm.merek_id = v_merek_id;
        END IF;

        IF v_found_merek_rule IS NOT TRUE AND v_grup_umum_id IS NOT NULL THEN
            SELECT 
                COALESCE(gp.default_level_harga, 1),
                COALESCE(gp.diskon_persen_default, 0.00),
                COALESCE(gp.diskon_nominal_default, 0.00),
                TRUE
            INTO 
                v_level_harga,
                v_diskon_persen,
                v_diskon_nominal,
                v_is_dijual
            FROM public.grup_pelanggan gp
            WHERE gp.id = v_grup_umum_id;
        END IF;

        -- Fallback default untuk umum jika belum disetel
        IF v_level_harga IS NULL AND v_is_dijual IS NULL THEN
            v_level_harga := 1;
            v_diskon_persen := 0.00;
            v_diskon_nominal := 0.00;
            v_is_dijual := TRUE;
            v_nama_grup_pelanggan := 'Umum';
        END IF;
    END IF;

    -- 4. Validasi Status Penjualan Merek (BRAND_NOT_ALLOWED)
    IF v_is_dijual IS FALSE THEN
        RETURN jsonb_build_object(
            'error', true,
            'code', 'BRAND_NOT_ALLOWED',
            'message', format('Produk "%s" (Merek: %s) tidak dijual untuk grup pelanggan "%s".', COALESCE(v_nama_item, 'Produk'), COALESCE(v_nama_merek, 'Merek'), COALESCE(v_nama_grup_pelanggan, 'Pelanggan')),
            'merek_id', v_merek_id,
            'nama_merek', v_nama_merek,
            'grup_pelanggan', v_nama_grup_pelanggan
        );
    END IF;

    -- Jika level harga belum terisi, fallback ke 1
    v_level_harga := COALESCE(v_level_harga, 1);
    v_diskon_persen := COALESCE(v_diskon_persen, 0.00);
    v_diskon_nominal := COALESCE(v_diskon_nominal, 0.00);

    -- 5. Cari harga base di grup_produk_harga_level
    SELECT harga_jual_pcs
    INTO v_harga_pcs_dasar
    FROM public.grup_produk_harga_level
    WHERE grup_produk_id = v_grup_produk_id AND level_harga = v_level_harga;

    -- Pilihan B (Strict Rejection): Jika harga level belum diatur di /pricing
    IF v_harga_pcs_dasar IS NULL THEN
        RETURN jsonb_build_object(
            'error', true,
            'code', 'PRICE_LEVEL_NOT_CONFIGURED',
            'message', format('Harga Level %s belum diatur untuk produk "%s" di /pricing.', v_level_harga, COALESCE(v_nama_item, 'Produk')),
            'level_harga', v_level_harga,
            'merek_id', v_merek_id,
            'nama_merek', v_nama_merek,
            'grup_pelanggan', v_nama_grup_pelanggan
        );
    END IF;

    -- 6. Hitung Diskon (% dan Nominal) spesifik merek
    v_harga_pcs_netto := v_harga_pcs_dasar - (v_harga_pcs_dasar * (v_diskon_persen / 100.0)) - v_diskon_nominal;
    v_harga_pcs_netto := ROUND(GREATEST(0, v_harga_pcs_netto));

    RETURN jsonb_build_object(
        'error', false,
        'level_harga', v_level_harga,
        'merek_id', v_merek_id,
        'nama_merek', v_nama_merek,
        'grup_pelanggan', v_nama_grup_pelanggan,
        'diskon_persen', v_diskon_persen,
        'diskon_nominal', v_diskon_nominal,
        'harga_pcs_bruto', v_harga_pcs_dasar,
        'harga_pcs_netto', v_harga_pcs_netto
    );
END;
$$;

-- 7. Kunci Hak Akses Eksekusi RPC
REVOKE EXECUTE ON FUNCTION public.fn_hitung_harga_jual_item(UUID, UUID) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_hitung_harga_jual_item(UUID, UUID) TO postgres, service_role;

COMMIT;

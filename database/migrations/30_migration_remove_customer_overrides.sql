-- database/30_migration_remove_customer_overrides.sql
-- ==============================================================================
-- MIGRASI 30: HAPUS OVERRIDE TIER HARGA & DISKON KHUSUS DARI TABEL PELANGGAN
-- ==============================================================================

-- 1. Update stored procedure fn_hitung_harga_jual_item
CREATE OR REPLACE FUNCTION public.fn_hitung_harga_jual_item(
    p_item_id UUID,
    p_pelanggan_id UUID
)
RETURNS JSONB AS $$
DECLARE
    v_grup_produk_id UUID;
    v_level_harga INT;
    v_diskon_persen NUMERIC(5, 2);
    v_diskon_nominal NUMERIC(15, 2);
    v_harga_pcs_dasar NUMERIC(15, 2);
    v_harga_bal_dasar NUMERIC(15, 2);
    v_harga_pcs_netto NUMERIC(15, 2);
    v_harga_bal_netto NUMERIC(15, 2);
    v_nama_grup_pelanggan VARCHAR;
BEGIN
    -- 1. Ambil grup_produk dari item
    SELECT grup_id INTO v_grup_produk_id FROM public.item WHERE id = p_item_id;

    -- 2. Ambil aturan harga dari grup_pelanggan yang terhubung
    IF p_pelanggan_id IS NOT NULL THEN
        SELECT 
            COALESCE(gp.default_level_harga, 1),
            COALESCE(gp.diskon_persen_default, 0.00),
            COALESCE(gp.diskon_nominal_default, 0.00),
            gp.nama_grup
        INTO v_level_harga, v_diskon_persen, v_diskon_nominal, v_nama_grup_pelanggan
        FROM public.pelanggan p
        JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
        WHERE p.id = p_pelanggan_id;
    END IF;

    -- Jika tidak ditemukan pelanggan / walk-in cash, default level 1 tanpa diskon
    IF v_level_harga IS NULL THEN
        v_level_harga := 1;
        v_diskon_persen := 0.00;
        v_diskon_nominal := 0.00;
        v_nama_grup_pelanggan := 'Umum';
    END IF;

    -- 3. Cari harga base di grup_produk_harga_level
    SELECT harga_jual_pcs, harga_jual_bal 
    INTO v_harga_pcs_dasar, v_harga_bal_dasar
    FROM public.grup_produk_harga_level
    WHERE grup_produk_id = v_grup_produk_id AND level_harga = v_level_harga;

    -- Fallback jika level harga belum diset di grup, kalikan HPP dengan konversi bal grup produk
    IF v_harga_pcs_dasar IS NULL THEN
        SELECT 
            COALESCE(NULLIF(i.harga_pokok_pembelian, 0), 10000),
            COALESCE(NULLIF(i.harga_pokok_pembelian, 0), 10000) * COALESCE(NULLIF(gp.konversi_bal_ke_pcs, 0), NULLIF(i.konversi_distribusi_ke_dasar, 0), 20)
        INTO v_harga_pcs_dasar, v_harga_bal_dasar
        FROM public.item i
        LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
        WHERE i.id = p_item_id;

        v_harga_pcs_dasar := COALESCE(v_harga_pcs_dasar, 10000);
        v_harga_bal_dasar := COALESCE(v_harga_bal_dasar, v_harga_pcs_dasar * 20);
    END IF;

    -- 4. Hitung Diskon (% dan Nominal) dari grup pelanggan
    v_harga_pcs_netto := v_harga_pcs_dasar - (v_harga_pcs_dasar * (v_diskon_persen / 100.0)) - v_diskon_nominal;
    v_harga_bal_netto := v_harga_bal_dasar - (v_harga_bal_dasar * (v_diskon_persen / 100.0)) - (v_diskon_nominal * 20);

    RETURN jsonb_build_object(
        'level_harga', v_level_harga,
        'grup_pelanggan', v_nama_grup_pelanggan,
        'diskon_persen', v_diskon_persen,
        'diskon_nominal', v_diskon_nominal,
        'harga_pcs_bruto', v_harga_pcs_dasar,
        'harga_bal_bruto', v_harga_bal_dasar,
        'harga_pcs_netto', GREATEST(0, v_harga_pcs_netto),
        'harga_bal_netto', GREATEST(0, v_harga_bal_netto)
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- 2. Hapus kolom-kolom override dari tabel public.pelanggan
ALTER TABLE public.pelanggan DROP COLUMN IF EXISTS override_level_harga;
ALTER TABLE public.pelanggan DROP COLUMN IF EXISTS override_diskon_persen;
ALTER TABLE public.pelanggan DROP COLUMN IF EXISTS override_diskon_nominal;
-- ==============================================================================
-- MIGRATION 26: FASE 1 - MASTER DATA & FINANCIAL INTEGRITY CRITICAL FIXES (P0)
-- 1000% Selaras dengan PRD & Arsitektur Keren Snack ERP
-- ==============================================================================

BEGIN;

-- ------------------------------------------------------------------------------
-- 1. NORMALISASI BARCODE MASTER PRODUK DARI NOTASI ILMIAH (SCIENTIFIC NOTATION)
-- ------------------------------------------------------------------------------
-- Ubah notasi ilmiah seperti '8.8026176E7' menjadi digit murni '88026176'
UPDATE public.grup_produk
SET barcode_universal = (barcode_universal::numeric)::bigint::text,
    diubah_pada = NOW()
WHERE barcode_universal IS NOT NULL 
  AND barcode_universal != '' 
  AND (barcode_universal ~* 'e' OR barcode_universal ~* '\.0');

UPDATE public.item
SET barcode = (barcode::numeric)::bigint::text,
    diubah_pada = NOW()
WHERE barcode IS NOT NULL 
  AND barcode != '' 
  AND (barcode ~* 'e' OR barcode ~* '\.0');

-- ------------------------------------------------------------------------------
-- 2. PERBAIKAN CHECK CONSTRAINT ARUS_KAS (BUKU BESAR & TRANSFER KAS)
-- ------------------------------------------------------------------------------
-- Lepas CHECK constraint kategori yang kaku agar mendukung modal_awal, koreksi, dan kategori dinamis
ALTER TABLE public.arus_kas DROP CONSTRAINT IF EXISTS arus_kas_kategori_check;

-- Perbarui CHECK constraint jenis_kas agar mendukung transfer_masuk dan transfer_keluar
ALTER TABLE public.arus_kas DROP CONSTRAINT IF EXISTS arus_kas_jenis_kas_check;
ALTER TABLE public.arus_kas ADD CONSTRAINT arus_kas_jenis_kas_check 
    CHECK (jenis_kas IN ('masuk', 'keluar', 'transfer_masuk', 'transfer_keluar'));

-- ------------------------------------------------------------------------------
-- 3. KOREKSI FALLBACK HARGA BAL PADA STORED PROCEDURE fn_hitung_harga_jual_item
-- ------------------------------------------------------------------------------
-- Fallback bal WAJIB membaca konversi_bal_ke_pcs dari grup_produk (default 20),
-- bukan membaca konversi_distribusi_ke_dasar dari item (default 1) yang memicu rugi 95%.
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

    -- 2. Ambil aturan harga dari pelanggan & grup_pelanggan
    SELECT 
        COALESCE(p.override_level_harga, gp.default_level_harga, 1),
        COALESCE(p.override_diskon_persen, gp.diskon_persen_default, 0.00),
        COALESCE(p.override_diskon_nominal, gp.diskon_nominal_default, 0.00),
        gp.nama_grup
    INTO v_level_harga, v_diskon_persen, v_diskon_nominal, v_nama_grup_pelanggan
    FROM public.pelanggan p
    JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
    WHERE p.id = p_pelanggan_id;

    -- Jika tidak ditemukan pelanggan, default level 1 tanpa diskon
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

    -- 4. Hitung Diskon (% dan Nominal)
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

-- ------------------------------------------------------------------------------
-- 4. PRESISI KUANTITAS DESIMAL PEMBELIAN BAHAN BAKU CURAH
-- ------------------------------------------------------------------------------
ALTER TABLE public.rincian_pembelian 
    ALTER COLUMN kuantitas TYPE NUMERIC(15, 2);

COMMIT;

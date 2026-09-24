-- ==============================================================================
-- 40_migration_tiered_sales_commission.sql
-- Migrasi Skema Komisi Sales Bertingkat (Tiered Commission System)
-- Menggantikan komisi statis 5% dengan skema tier dinamis terpusat
-- ==============================================================================

-- 1. Buat Tabel Skema Komisi Sales Bertingkat
CREATE TABLE IF NOT EXISTS public.skema_komisi_sales (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    urutan INT NOT NULL,
    nama_tier VARCHAR(100) NOT NULL,
    omzet_min NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    omzet_maks NUMERIC(15, 2) DEFAULT NULL, -- NULL berarti tanpa batas atas (tak terhingga)
    persentase NUMERIC(5, 2) NOT NULL DEFAULT 0.00,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_skema_komisi_omzet_min_non_neg CHECK (omzet_min >= 0),
    CONSTRAINT chk_skema_komisi_persentase_range CHECK (persentase >= 0 AND persentase <= 100),
    CONSTRAINT chk_skema_komisi_range_valid CHECK (omzet_maks IS NULL OR omzet_maks >= omzet_min)
);

CREATE INDEX IF NOT EXISTS idx_skema_komisi_urutan ON public.skema_komisi_sales (urutan ASC);
CREATE INDEX IF NOT EXISTS idx_skema_komisi_aktif ON public.skema_komisi_sales (status_aktif);

COMMENT ON TABLE public.skema_komisi_sales IS 'Master skema tier komisi sales bertingkat berdasarkan akumulasi omzet bulanan';

-- 2. Fungsi Helper PostgreSQL untuk Perhitungan Tier Komisi Otomatis
CREATE OR REPLACE FUNCTION public.fn_hitung_tier_komisi_sales(p_omzet NUMERIC)
RETURNS JSONB
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
    v_tier RECORD;
    v_next_tier RECORD;
    v_nominal_komisi NUMERIC(15, 2) := 0.00;
    v_gap_omzet NUMERIC(15, 2) := 0.00;
    v_omzet_bersih NUMERIC(15, 2) := GREATEST(0.00, COALESCE(p_omzet, 0.00));
BEGIN
    -- Cari tier yang sesuai dengan rentang omzet
    SELECT * INTO v_tier
    FROM public.skema_komisi_sales
    WHERE status_aktif = TRUE
      AND v_omzet_bersih >= omzet_min
      AND (omzet_maks IS NULL OR v_omzet_bersih <= omzet_maks)
    ORDER BY urutan DESC
    LIMIT 1;

    -- Jika omzet di bawah tier 1 minimum, gunakan tier dengan urutan pertama tetapi komisi 0 jika di bawah min
    IF NOT FOUND OR v_tier.id IS NULL THEN
        SELECT * INTO v_tier
        FROM public.skema_komisi_sales
        WHERE status_aktif = TRUE
        ORDER BY urutan ASC
        LIMIT 1;
        
        IF FOUND AND v_tier.id IS NOT NULL AND v_omzet_bersih < v_tier.omzet_min THEN
            -- Omzet belum memenuhi tier dasar
            v_nominal_komisi := 0.00;
            v_gap_omzet := v_tier.omzet_min - v_omzet_bersih;
            RETURN jsonb_build_object(
                'matched', false,
                'tier_id', NULL,
                'nama_tier', 'Di Bawah Tier Minimum',
                'urutan', 0,
                'omzet_min', 0.00,
                'omzet_maks', v_tier.omzet_min,
                'persentase', 0.00,
                'total_omzet', v_omzet_bersih,
                'nominal_komisi', 0.00,
                'has_next_tier', true,
                'next_tier_nama', v_tier.nama_tier,
                'next_tier_persentase', v_tier.persentase,
                'gap_omzet_ke_next_tier', v_gap_omzet
            );
        END IF;
    END IF;

    -- Jika tidak ada data tier sama sekali di tabel
    IF v_tier.id IS NULL THEN
        RETURN jsonb_build_object(
            'matched', false,
            'tier_id', NULL,
            'nama_tier', 'Skema Belum Dikonfigurasi',
            'urutan', 0,
            'omzet_min', 0.00,
            'omzet_maks', NULL,
            'persentase', 0.00,
            'total_omzet', v_omzet_bersih,
            'nominal_komisi', 0.00,
            'has_next_tier', false,
            'next_tier_nama', NULL,
            'next_tier_persentase', 0.00,
            'gap_omzet_ke_next_tier', 0.00
        );
    END IF;

    -- Hitung komisi flat retroaktif terhadap total omzet
    v_nominal_komisi := ROUND((v_omzet_bersih * v_tier.persentase / 100.0), 2);

    -- Cari tier berikutnya untuk motivasi sales
    SELECT * INTO v_next_tier
    FROM public.skema_komisi_sales
    WHERE status_aktif = TRUE
      AND urutan > v_tier.urutan
    ORDER BY urutan ASC
    LIMIT 1;

    IF FOUND AND v_next_tier.id IS NOT NULL THEN
        v_gap_omzet := GREATEST(0.00, v_next_tier.omzet_min - v_omzet_bersih);
    ELSE
        v_gap_omzet := 0.00;
    END IF;

    RETURN jsonb_build_object(
        'matched', true,
        'tier_id', v_tier.id,
        'nama_tier', v_tier.nama_tier,
        'urutan', v_tier.urutan,
        'omzet_min', v_tier.omzet_min,
        'omzet_maks', v_tier.omzet_maks,
        'persentase', v_tier.persentase,
        'total_omzet', v_omzet_bersih,
        'nominal_komisi', v_nominal_komisi,
        'has_next_tier', (FOUND AND v_next_tier.id IS NOT NULL),
        'next_tier_nama', CASE WHEN FOUND AND v_next_tier.id IS NOT NULL THEN v_next_tier.nama_tier ELSE NULL END,
        'next_tier_persentase', CASE WHEN FOUND AND v_next_tier.id IS NOT NULL THEN v_next_tier.persentase ELSE 0.00 END,
        'gap_omzet_ke_next_tier', v_gap_omzet
    );
END;
$$;

-- 3. Default Seeding Skema Tier Awal (Jika tabel masih kosong)
INSERT INTO public.skema_komisi_sales (urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif)
SELECT 1, 'Tier 1 (Dasar)', 0.00, 20000000.00, 1.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM public.skema_komisi_sales);

INSERT INTO public.skema_komisi_sales (urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif)
SELECT 2, 'Tier 2 (Reguler)', 20000000.01, 35000000.00, 2.50, TRUE
WHERE (SELECT COUNT(*) FROM public.skema_komisi_sales) = 1;

INSERT INTO public.skema_komisi_sales (urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif)
SELECT 3, 'Tier 3 (Gold)', 35000000.01, 50000000.00, 4.00, TRUE
WHERE (SELECT COUNT(*) FROM public.skema_komisi_sales) = 2;

INSERT INTO public.skema_komisi_sales (urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif)
SELECT 4, 'Tier 4 (Platinum)', 50000000.01, NULL, 5.00, TRUE
WHERE (SELECT COUNT(*) FROM public.skema_komisi_sales) = 3;

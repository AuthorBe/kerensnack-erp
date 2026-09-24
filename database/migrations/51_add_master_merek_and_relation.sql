-- ==============================================================================
-- KEREN SNACK ERP - DATABASE MIGRATION 51: MASTER DATA MEREK & RELASI GRUP
-- ==============================================================================

BEGIN;

-- 1. Buat Tabel Master Merek
CREATE TABLE IF NOT EXISTS public.merek (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_merek VARCHAR(50) NOT NULL UNIQUE,
    nama_merek VARCHAR(150) NOT NULL,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Indeks Pencarian Nama Merek & Status
CREATE INDEX IF NOT EXISTS idx_merek_nama ON public.merek(nama_merek);
CREATE INDEX IF NOT EXISTS idx_merek_status ON public.merek(status_aktif);

-- 2. Aktifkan Row Level Security (RLS) & Kebijakan Akses
ALTER TABLE public.merek ENABLE ROW LEVEL SECURITY;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies WHERE tablename = 'merek' AND policyname = 'service_role_all_merek'
    ) THEN
        CREATE POLICY service_role_all_merek ON public.merek FOR ALL TO service_role USING (true) WITH CHECK (true);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_policies WHERE tablename = 'merek' AND policyname = 'public_all_merek'
    ) THEN
        CREATE POLICY public_all_merek ON public.merek FOR ALL USING (true) WITH CHECK (true);
    END IF;
END $$;

-- 3. Seeding Merek Default Pertama ('KEREN SNACK')
INSERT INTO public.merek (kode_merek, nama_merek, status_aktif, dibuat_pada, diubah_pada)
VALUES ('KRN', 'KEREN SNACK', TRUE, NOW(), NOW())
ON CONFLICT (kode_merek) DO UPDATE 
SET nama_merek = EXCLUDED.nama_merek, status_aktif = TRUE;

-- 4. Tambahkan Kolom Foreign Key 'merek_id' ke Tabel 'grup_produk'
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'grup_produk' AND column_name = 'merek_id'
    ) THEN
        ALTER TABLE public.grup_produk 
        ADD COLUMN merek_id UUID REFERENCES public.merek(id) ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;
END $$;

CREATE INDEX IF NOT EXISTS idx_grup_produk_merek_id ON public.grup_produk(merek_id);

-- 5. Backfill Seluruh Grup Produk Eksisting ke Merek Default ('KEREN SNACK')
UPDATE public.grup_produk
SET merek_id = (SELECT id FROM public.merek WHERE kode_merek = 'KRN' LIMIT 1)
WHERE merek_id IS NULL;

COMMIT;

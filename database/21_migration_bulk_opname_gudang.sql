-- ============================================================================
-- MIGRATION 21: BULK OPNAME GUDANG & SESI AUDIT STOK
-- Database: PostgreSQL Supabase (KEREN SNACK ERP)
-- ============================================================================

-- 1. Tabel Header Dokumen Sesi Opname Gudang
CREATE TABLE IF NOT EXISTS public.opname_gudang (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_dokumen VARCHAR(100) NOT NULL UNIQUE,
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    total_item_dihitung INT NOT NULL DEFAULT 0,
    total_item_selisih INT NOT NULL DEFAULT 0,
    total_qty_masuk NUMERIC(15, 4) NOT NULL DEFAULT 0.0000,
    total_qty_keluar NUMERIC(15, 4) NOT NULL DEFAULT 0.0000,
    catatan TEXT,
    dibuat_oleh UUID REFERENCES public.pengguna(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 2. Tabel Detail Item dalam Sesi Opname Gudang
CREATE TABLE IF NOT EXISTS public.opname_gudang_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    opname_id UUID NOT NULL REFERENCES public.opname_gudang(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id),
    stok_sistem NUMERIC(15, 4) NOT NULL,
    stok_fisik NUMERIC(15, 4) NOT NULL,
    selisih NUMERIC(15, 4) NOT NULL,
    tipe_mutasi VARCHAR(30) NOT NULL CHECK (tipe_mutasi IN ('masuk', 'keluar', 'tetap')),
    catatan_item TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 3. Index Performa untuk Pencarian, Filter & Audit
CREATE INDEX IF NOT EXISTS idx_opname_gudang_tgl ON public.opname_gudang(tanggal DESC);
CREATE INDEX IF NOT EXISTS idx_opname_gudang_nomor ON public.opname_gudang(nomor_dokumen);
CREATE INDEX IF NOT EXISTS idx_opname_gudang_item_parent ON public.opname_gudang_item(opname_id);
CREATE INDEX IF NOT EXISTS idx_opname_gudang_item_item ON public.opname_gudang_item(item_id);

-- 4. Enable Row Level Security (RLS)
ALTER TABLE public.opname_gudang ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.opname_gudang_item ENABLE ROW LEVEL SECURITY;

DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE tablename = 'opname_gudang' AND policyname = 'service_role_all_opname_gudang'
    ) THEN
        CREATE POLICY service_role_all_opname_gudang ON public.opname_gudang 
        FOR ALL TO public USING (true) WITH CHECK (true);
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_policies 
        WHERE tablename = 'opname_gudang_item' AND policyname = 'service_role_all_opname_gudang_item'
    ) THEN
        CREATE POLICY service_role_all_opname_gudang_item ON public.opname_gudang_item 
        FOR ALL TO public USING (true) WITH CHECK (true);
    END IF;
END $$;

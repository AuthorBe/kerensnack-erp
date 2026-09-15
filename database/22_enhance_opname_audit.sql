-- ============================================================================
-- MIGRATION 22: ENHANCE OPNAME GUDANG AUDIT & FINANCIAL VALUATION
-- Database: PostgreSQL Supabase (KEREN SNACK ERP)
-- ============================================================================

-- 1. Tambah Kolom Rekap Finansial & Total Katalog pada Header opname_gudang
ALTER TABLE public.opname_gudang
    ADD COLUMN IF NOT EXISTS total_nilai_selisih_rp NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS total_item_katalog INT NOT NULL DEFAULT 0;

-- 2. Tambah Kolom Snapshot HPP & Subtotal Nilai Selisih pada opname_gudang_item
ALTER TABLE public.opname_gudang_item
    ADD COLUMN IF NOT EXISTS harga_pokok_saat_opname NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    ADD COLUMN IF NOT EXISTS subtotal_nilai_selisih NUMERIC(15, 2) NOT NULL DEFAULT 0.00;

-- 3. Constraint Unique (opname_id, item_id) untuk Mencegah SKU Ganda dalam Satu Sesi
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint 
        WHERE conname = 'uq_opname_gudang_item_item'
    ) THEN
        ALTER TABLE public.opname_gudang_item
            ADD CONSTRAINT uq_opname_gudang_item_item UNIQUE (opname_id, item_id);
    END IF;
END $$;

-- 4. Index Tambahan untuk Pencarian Cepat Rentang Tanggal
CREATE INDEX IF NOT EXISTS idx_opname_gudang_tgl_desc ON public.opname_gudang(tanggal DESC, dibuat_pada DESC);

-- ==============================================================================
-- MIGRATION 27: FASE 2 - CONSOLIDATE MISSING MASTER SCHEMA & TRANSACTION INTEGRITY
-- 1000% Selaras dengan PRD & Arsitektur Keren Snack ERP
-- ==============================================================================

BEGIN;

-- ------------------------------------------------------------------------------
-- 1. TABEL PELANGGAN_ITEM (WHITELIST PRODUK SPESIFIK TOKO)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.pelanggan_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id) ON DELETE CASCADE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_pelanggan_item UNIQUE (pelanggan_id, item_id)
);

CREATE INDEX IF NOT EXISTS idx_pelanggan_item_pelanggan ON public.pelanggan_item(pelanggan_id);
CREATE INDEX IF NOT EXISTS idx_pelanggan_item_item ON public.pelanggan_item(item_id);

-- ------------------------------------------------------------------------------
-- 2. TABEL KATEGORI_BIAYA (MASTER KATEGORI BEBAN OPERASIONAL KAS TOKO)
-- ------------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.kategori_biaya (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_kategori VARCHAR(50) NOT NULL UNIQUE,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    tipe_beban VARCHAR(50) DEFAULT 'operasional',
    status_aktif BOOLEAN DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ------------------------------------------------------------------------------
-- 3. KOLOM METADATA AKUN KAS (TIPE AKUN & DEFAULT KASIR POS)
-- ------------------------------------------------------------------------------
ALTER TABLE public.akun_kas ADD COLUMN IF NOT EXISTS tipe_akun VARCHAR(30) DEFAULT 'kas_fisik';
ALTER TABLE public.akun_kas ADD COLUMN IF NOT EXISTS is_default_pos BOOLEAN DEFAULT FALSE;

-- ------------------------------------------------------------------------------
-- 4. KOLOM REKENING BANK VENDOR / PEMASOK
-- ------------------------------------------------------------------------------
ALTER TABLE public.pemasok ADD COLUMN IF NOT EXISTS nama_bank VARCHAR(50);
ALTER TABLE public.pemasok ADD COLUMN IF NOT EXISTS nomor_rekening VARCHAR(50);
ALTER TABLE public.pemasok ADD COLUMN IF NOT EXISTS atas_nama_rekening VARCHAR(100);

-- ------------------------------------------------------------------------------
-- 5. SNAPSHOT HPP BERJALAN PADA BARIS DETAIL PESANAN (HISTORICAL GROSS MARGIN)
-- ------------------------------------------------------------------------------
ALTER TABLE public.item_pesanan ADD COLUMN IF NOT EXISTS harga_pokok_satuan NUMERIC(15, 2) NOT NULL DEFAULT 0.00;

COMMIT;

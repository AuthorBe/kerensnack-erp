-- ==============================================================================
-- MIGRASI 82: KATALOG MULTI-VENDOR (pemasok_item) & KONSOLIDASI BAHAN MENTAH
-- Repositori: KEREN ONE ERP
-- Tanggal: 2026-10-02
-- ==============================================================================

BEGIN;

-- 1. Buat Tabel Katalog Multi-Vendor (pemasok_item)
CREATE TABLE IF NOT EXISTS public.pemasok_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pemasok_id UUID NOT NULL REFERENCES public.pemasok(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id) ON DELETE CASCADE,
    harga_beli NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    kode_sku_vendor VARCHAR(100) DEFAULT NULL,
    catatan TEXT DEFAULT NULL,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_pemasok_item UNIQUE (pemasok_id, item_id),
    CONSTRAINT chk_pemasok_item_harga_non_neg CHECK (harga_beli >= 0)
);

-- Indeks untuk pencarian cepat katalog vendor
CREATE INDEX IF NOT EXISTS idx_pemasok_item_pemasok ON public.pemasok_item(pemasok_id);
CREATE INDEX IF NOT EXISTS idx_pemasok_item_item ON public.pemasok_item(item_id);
CREATE INDEX IF NOT EXISTS idx_pemasok_item_pemasok_aktif ON public.pemasok_item(pemasok_id, status_aktif);

-- 2. Migrasi Data Eksisting Pemasok ke Tabel pemasok_item
-- Buat temporary mapping untuk menentukan Canonical Item ID (Item Utama per Nama Unik)
CREATE TEMP TABLE tmp_canonical_items AS
SELECT DISTINCT ON (LOWER(TRIM(nama_item)), tipe_item)
    id AS canonical_id,
    LOWER(TRIM(nama_item)) AS clean_name,
    tipe_item,
    kode_sku,
    nama_item,
    harga_pokok_pembelian,
    satuan_dasar
FROM public.item
ORDER BY LOWER(TRIM(nama_item)), tipe_item, dibuat_pada ASC, id ASC;

-- Masukkan seluruh relasi vendor dari master item (termasuk yang duplikat) ke tabel pemasok_item
-- Menggunakan canonical_id agar semua harga vendor terhubung ke 1 Master Item Utama
INSERT INTO public.pemasok_item (pemasok_id, item_id, harga_beli, catatan, status_aktif, dibuat_pada, diubah_pada)
SELECT 
    i.pemasok_utama_id AS pemasok_id,
    c.canonical_id AS item_id,
    COALESCE(i.harga_pokok_pembelian, 0.00) AS harga_beli,
    'Migrasi otomatis dari data master item lama' AS catatan,
    TRUE AS status_aktif,
    NOW(),
    NOW()
FROM public.item i
JOIN tmp_canonical_items c ON LOWER(TRIM(i.nama_item)) = c.clean_name AND i.tipe_item = c.tipe_item
WHERE i.pemasok_utama_id IS NOT NULL
ON CONFLICT (pemasok_id, item_id) 
DO UPDATE SET 
    harga_beli = EXCLUDED.harga_beli,
    diubah_pada = NOW();

-- 3. Alihkan referensi Foreign Key dari Item Duplikat ke Canonical Item
-- Update rincian_pembelian
UPDATE public.rincian_pembelian rp
SET item_id = c.canonical_id
FROM public.item i
JOIN tmp_canonical_items c ON LOWER(TRIM(i.nama_item)) = c.clean_name AND i.tipe_item = c.tipe_item
WHERE rp.item_id = i.id AND i.id != c.canonical_id;

-- Update riwayat_stok
UPDATE public.riwayat_stok rs
SET item_id = c.canonical_id
FROM public.item i
JOIN tmp_canonical_items c ON LOWER(TRIM(i.nama_item)) = c.clean_name AND i.tipe_item = c.tipe_item
WHERE rs.item_id = i.id AND i.id != c.canonical_id;

-- Update komposisi_item (BOM Resep)
-- Pastikan tidak duplicate sebelum update
DELETE FROM public.komposisi_item ki
WHERE EXISTS (
    SELECT 1 FROM public.item i
    JOIN tmp_canonical_items c ON LOWER(TRIM(i.nama_item)) = c.clean_name AND i.tipe_item = c.tipe_item
    WHERE ki.item_bahan_id = i.id AND i.id != c.canonical_id
    AND EXISTS (
        SELECT 1 FROM public.komposisi_item ki2 
        WHERE ki2.item_jadi_id = ki.item_jadi_id AND ki2.item_bahan_id = c.canonical_id
    )
);

UPDATE public.komposisi_item ki
SET item_bahan_id = c.canonical_id
FROM public.item i
JOIN tmp_canonical_items c ON LOWER(TRIM(i.nama_item)) = c.clean_name AND i.tipe_item = c.tipe_item
WHERE ki.item_bahan_id = i.id AND i.id != c.canonical_id;

-- Update opname_gudang_item
UPDATE public.opname_gudang_item ogi
SET item_id = c.canonical_id
FROM public.item i
JOIN tmp_canonical_items c ON LOWER(TRIM(i.nama_item)) = c.clean_name AND i.tipe_item = c.tipe_item
WHERE ogi.item_id = i.id AND i.id != c.canonical_id;

-- 4. Hapus Item Duplikat yang Sudah Bersih dari Referensi
DELETE FROM public.item i
USING tmp_canonical_items c
WHERE LOWER(TRIM(i.nama_item)) = c.clean_name 
  AND i.tipe_item = c.tipe_item 
  AND i.id != c.canonical_id;

-- Bersihkan tabel temporary
DROP TABLE IF EXISTS tmp_canonical_items;

-- 5. Standardisasi Master Item Bahan Mentah
-- Pastikan nama item bersih (Title Case / Uppercase rapi) dan HPP terisi nilai rata-rata/terakhir
UPDATE public.item
SET nama_item = UPPER(TRIM(nama_item)),
    diubah_pada = NOW()
WHERE tipe_item IN ('bahan_mentah', 'bahan_kemas');

-- 6. Hak Akses & Kebijakan Supabase PostgREST (RLS)
ALTER TABLE public.pemasok_item ENABLE ROW LEVEL SECURITY;

GRANT SELECT, INSERT, UPDATE, DELETE ON public.pemasok_item TO authenticated;
GRANT ALL ON public.pemasok_item TO service_role;

DROP POLICY IF EXISTS service_role_all_pemasok_item ON public.pemasok_item;
CREATE POLICY service_role_all_pemasok_item ON public.pemasok_item 
FOR ALL TO service_role 
USING (true) 
WITH CHECK (true);

COMMIT;

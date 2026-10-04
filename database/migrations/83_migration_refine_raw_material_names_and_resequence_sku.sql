-- ==============================================================================
-- DATABASE MIGRATION 83: REFINE RAW MATERIAL NAMES & RESEQUENCE SKU CODES
-- Keren One ERP Database
--
-- 1. Merge PISANG SALE into SALE PISANG (with multi-vendor catalog consolidation)
-- 2. Rename EMPING MARNING to EMPING JAGUNG
-- 3. Merge PANG PANG KROMA into PANG PANG
-- 4. Merge MARNING JAGUNG into MARNING BULAT
-- 5. Resequence all bahan mentah SKU codes (BAHAN-0001 .. BAHAN-0053) alphabetically
-- ==============================================================================

BEGIN;

-- 1. MERGE PISANG SALE -> SALE PISANG
-- Target: ae779cc8-4511-4957-b65c-a8b48a8375a7 (SALE PISANG)
-- Source: d59a9ba6-6ced-4f78-88c2-780f3efbc83e (PISANG SALE)
INSERT INTO public.pemasok_item (pemasok_id, item_id, harga_beli, catatan, status_aktif)
SELECT pemasok_id, 'ae779cc8-4511-4957-b65c-a8b48a8375a7'::uuid, harga_beli, catatan, status_aktif
FROM public.pemasok_item
WHERE item_id = 'd59a9ba6-6ced-4f78-88c2-780f3efbc83e'::uuid
ON CONFLICT (pemasok_id, item_id) DO UPDATE SET
    harga_beli = EXCLUDED.harga_beli,
    catatan = COALESCE(EXCLUDED.catatan, public.pemasok_item.catatan),
    diubah_pada = NOW();

DELETE FROM public.pemasok_item WHERE item_id = 'd59a9ba6-6ced-4f78-88c2-780f3efbc83e'::uuid;
UPDATE public.komposisi_item SET item_bahan_id = 'ae779cc8-4511-4957-b65c-a8b48a8375a7'::uuid WHERE item_bahan_id = 'd59a9ba6-6ced-4f78-88c2-780f3efbc83e'::uuid;
UPDATE public.rincian_pembelian SET item_id = 'ae779cc8-4511-4957-b65c-a8b48a8375a7'::uuid WHERE item_id = 'd59a9ba6-6ced-4f78-88c2-780f3efbc83e'::uuid;
DELETE FROM public.item WHERE id = 'd59a9ba6-6ced-4f78-88c2-780f3efbc83e'::uuid;

-- 2. RENAME EMPING MARNING -> EMPING JAGUNG
UPDATE public.item SET nama_item = 'EMPING JAGUNG' WHERE id = '23fbca9f-323e-4b87-9735-26f51235d480'::uuid;
UPDATE public.item SET nama_item = 'Emping Jagung' WHERE id = 'cf1d5216-0393-46d2-a3d4-fd09fff69c91'::uuid;

-- 3. MERGE PANG PANG KROMA -> PANG PANG
-- Target: bf7f6f1a-2f9e-425a-b6c5-fdf691888b2c (PANG-PANG)
-- Source: a58bf962-0674-4f4f-afa6-644598b6406e (PANG PANG KROMA)
UPDATE public.item SET nama_item = 'PANG PANG' WHERE id = 'bf7f6f1a-2f9e-425a-b6c5-fdf691888b2c'::uuid;

INSERT INTO public.pemasok_item (pemasok_id, item_id, harga_beli, catatan, status_aktif)
SELECT pemasok_id, 'bf7f6f1a-2f9e-425a-b6c5-fdf691888b2c'::uuid, harga_beli, catatan, status_aktif
FROM public.pemasok_item
WHERE item_id = 'a58bf962-0674-4f4f-afa6-644598b6406e'::uuid
ON CONFLICT (pemasok_id, item_id) DO UPDATE SET
    harga_beli = EXCLUDED.harga_beli,
    catatan = COALESCE(EXCLUDED.catatan, public.pemasok_item.catatan),
    diubah_pada = NOW();

DELETE FROM public.pemasok_item WHERE item_id = 'a58bf962-0674-4f4f-afa6-644598b6406e'::uuid;
UPDATE public.komposisi_item SET item_bahan_id = 'bf7f6f1a-2f9e-425a-b6c5-fdf691888b2c'::uuid WHERE item_bahan_id = 'a58bf962-0674-4f4f-afa6-644598b6406e'::uuid;
UPDATE public.rincian_pembelian SET item_id = 'bf7f6f1a-2f9e-425a-b6c5-fdf691888b2c'::uuid WHERE item_id = 'a58bf962-0674-4f4f-afa6-644598b6406e'::uuid;
DELETE FROM public.item WHERE id = 'a58bf962-0674-4f4f-afa6-644598b6406e'::uuid;

-- 4. MERGE MARNING JAGUNG -> MARNING BULAT
-- Target: 1a57a59c-bb70-472c-a0f1-3c1e03551481 (MARNING BULAT)
-- Source: dd86e875-5199-4e98-b614-cf2755f52868 (MARNING JAGUNG)
INSERT INTO public.pemasok_item (pemasok_id, item_id, harga_beli, catatan, status_aktif)
SELECT pemasok_id, '1a57a59c-bb70-472c-a0f1-3c1e03551481'::uuid, harga_beli, catatan, status_aktif
FROM public.pemasok_item
WHERE item_id = 'dd86e875-5199-4e98-b614-cf2755f52868'::uuid
ON CONFLICT (pemasok_id, item_id) DO UPDATE SET
    harga_beli = EXCLUDED.harga_beli,
    catatan = COALESCE(EXCLUDED.catatan, public.pemasok_item.catatan),
    diubah_pada = NOW();

DELETE FROM public.pemasok_item WHERE item_id = 'dd86e875-5199-4e98-b614-cf2755f52868'::uuid;
UPDATE public.komposisi_item SET item_bahan_id = '1a57a59c-bb70-472c-a0f1-3c1e03551481'::uuid WHERE item_bahan_id = 'dd86e875-5199-4e98-b614-cf2755f52868'::uuid;
UPDATE public.rincian_pembelian SET item_id = '1a57a59c-bb70-472c-a0f1-3c1e03551481'::uuid WHERE item_id = 'dd86e875-5199-4e98-b614-cf2755f52868'::uuid;
DELETE FROM public.item WHERE id = 'dd86e875-5199-4e98-b614-cf2755f52868'::uuid;

-- 5. RESEQUENCE ALL BAHAN MENTAH SKUs ALPHABETICALLY (BAHAN-0001 .. BAHAN-0053)
-- Step 5a: Set temporary SKU with prefix
WITH ranked AS (
    SELECT id, 'TMP-BAHAN-' || LPAD(ROW_NUMBER() OVER (ORDER BY nama_item ASC)::text, 4, '0') as new_sku
    FROM public.item
    WHERE tipe_item = 'bahan_mentah'
)
UPDATE public.item i
SET kode_sku = ranked.new_sku
FROM ranked
WHERE i.id = ranked.id;

-- Step 5b: Remove TMP- prefix to finalize official SKU sequence
UPDATE public.item
SET kode_sku = REPLACE(kode_sku, 'TMP-', '')
WHERE tipe_item = 'bahan_mentah' AND kode_sku LIKE 'TMP-BAHAN-%';

COMMIT;

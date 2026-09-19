-- ==============================================================================
-- KEREN SNACK ERP - MIGRATION 48: STANDARDIZE R2 STORAGE PATHS
-- Mengubah nama kolom penyimpanan bukti transaksi agar murni menyimpan string path objek R2
-- ==============================================================================

BEGIN;

-- 1. Standardisasi Tabel Pembelian (Purchases)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'url_foto_nota'
    ) THEN
        ALTER TABLE public.pembelian RENAME COLUMN url_foto_nota TO path_foto_nota;
    END IF;

    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'foto_bukti_kendala'
    ) THEN
        ALTER TABLE public.pembelian RENAME COLUMN foto_bukti_kendala TO path_bukti_kendala;
    END IF;
END $$;

-- 2. Standardisasi Tabel Draf Pengeluaran (Expenses)
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'draf_pengeluaran' AND column_name = 'url_foto_nota'
    ) THEN
        ALTER TABLE public.draf_pengeluaran RENAME COLUMN url_foto_nota TO path_foto_nota;
    END IF;
END $$;

-- 3. Bersihkan prefix '/uploads/' jika ada pada data lama yang tersimpan
UPDATE public.pembelian 
SET path_foto_nota = REGEXP_REPLACE(path_foto_nota, '^/?(public/)?uploads/', '')
WHERE path_foto_nota LIKE '%uploads/%';

UPDATE public.pembelian 
SET path_bukti_kendala = REGEXP_REPLACE(path_bukti_kendala, '^/?(public/)?uploads/', '')
WHERE path_bukti_kendala LIKE '%uploads/%';

UPDATE public.surat_jalan 
SET bukti_terima_foto = REGEXP_REPLACE(bukti_terima_foto, '^/?(public/)?uploads/', '')
WHERE bukti_terima_foto LIKE '%uploads/%';

UPDATE public.surat_jalan 
SET foto_bukti_gagal = REGEXP_REPLACE(foto_bukti_gagal, '^/?(public/)?uploads/', '')
WHERE foto_bukti_gagal LIKE '%uploads/%';

UPDATE public.kunjungan_konsinyasi 
SET foto_kunjungan = REGEXP_REPLACE(foto_kunjungan, '^/?(public/)?uploads/', '')
WHERE foto_kunjungan LIKE '%uploads/%';

COMMIT;

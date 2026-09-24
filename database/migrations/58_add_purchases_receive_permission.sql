-- ============================================================================
-- MIGRATION 58: PENDAFTARAN IZIN KONFIRMASI PENERIMAAN BARANG GUDANG (RBAC)
-- Database: PostgreSQL Supabase (KEREN SNACK ERP)
-- ============================================================================

BEGIN;

-- 1. Pendaftaran Izin Baru di Master Izin
INSERT INTO public.izin (kode_izin, nama_izin, grup_izin, deskripsi)
VALUES (
    'purchases.receive',
    'Konfirmasi Penerimaan Barang (Gudang)',
    'Pembelian & Vendor',
    'Memverifikasi fisik barang masuk, mencocokkan nota belanja/faktur fisik, dan memasukkan stok ke gudang'
)
ON CONFLICT (kode_izin) DO UPDATE SET
    nama_izin = EXCLUDED.nama_izin,
    grup_izin = EXCLUDED.grup_izin,
    deskripsi = EXCLUDED.deskripsi;

-- 2. Berikan Izin ke Role Owner dan Admin (Developer memiliki universal bypass)
DO $$
DECLARE
    v_izin_id UUID;
    v_owner_id UUID;
    v_admin_id UUID;
BEGIN
    SELECT id INTO v_izin_id FROM public.izin WHERE kode_izin = 'purchases.receive';
    SELECT id INTO v_owner_id FROM public.peran WHERE nama_peran = 'owner';
    SELECT id INTO v_admin_id FROM public.peran WHERE nama_peran = 'admin';

    IF v_izin_id IS NOT NULL THEN
        IF v_owner_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_owner_id, v_izin_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;

        IF v_admin_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_admin_id, v_izin_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
    END IF;
END $$;

COMMIT;

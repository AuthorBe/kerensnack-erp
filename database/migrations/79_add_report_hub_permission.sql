-- ============================================================================
-- MIGRATION 79: ADD REPORT HUB PERMISSION (RBAC)
-- Database: PostgreSQL Supabase (KEREN SNACK ERP)
-- ============================================================================

BEGIN;

-- 1. Daftarkan Izin Baru: reports.download_hub
INSERT INTO public.izin (kode_izin, nama_izin, grup_izin, deskripsi)
VALUES (
    'reports.download_hub',
    'Pusat Unduh Laporan & Ekspor Terpusat',
    'Eksekutif & Laporan',
    'Mengakses portal pusat unduhan laporan dan mengekspor rekapitulasi data keuangan, penjualan, konsinyasi, stok gudang, dan log sistem'
)
ON CONFLICT (kode_izin) DO UPDATE SET
    nama_izin = EXCLUDED.nama_izin,
    grup_izin = EXCLUDED.grup_izin,
    deskripsi = EXCLUDED.deskripsi;

-- 2. Berikan Izin ke Role Owner dan Admin secara default
DO $$
DECLARE
    v_perm_id UUID;
    v_owner_id UUID;
    v_admin_id UUID;
BEGIN
    SELECT id INTO v_perm_id FROM public.izin WHERE kode_izin = 'reports.download_hub';
    SELECT id INTO v_owner_id FROM public.peran WHERE nama_peran = 'owner';
    SELECT id INTO v_admin_id FROM public.peran WHERE nama_peran = 'admin';

    IF v_perm_id IS NOT NULL THEN
        -- Assign to Owner
        IF v_owner_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_owner_id, v_perm_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;

        -- Assign to Admin
        IF v_admin_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_admin_id, v_perm_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
    END IF;
END $$;

COMMIT;

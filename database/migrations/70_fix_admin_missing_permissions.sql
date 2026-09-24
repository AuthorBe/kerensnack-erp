-- ==============================================================================
-- MIGRATION 70: SYNC ADMIN PERMISSIONS FOR IMPORT DATA & PURCHASES RECEIVE
-- Memberikan izin system.import_data dan purchases.receive ke peran Admin
-- Sesuai dengan spesifikasi Migration 47 & Migration 58
-- ==============================================================================

BEGIN;

DO $$
DECLARE
    v_admin_id UUID;
    v_import_data_id UUID;
    v_purchases_receive_id UUID;
BEGIN
    SELECT id INTO v_admin_id FROM public.peran WHERE nama_peran = 'admin';
    SELECT id INTO v_import_data_id FROM public.izin WHERE kode_izin = 'system.import_data';
    SELECT id INTO v_purchases_receive_id FROM public.izin WHERE kode_izin = 'purchases.receive';

    IF v_admin_id IS NOT NULL THEN
        IF v_import_data_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_admin_id, v_import_data_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;

        IF v_purchases_receive_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_admin_id, v_purchases_receive_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
    END IF;
END $$;

COMMIT;

-- ============================================================================
-- MIGRATION 59: REFACTORING IZIN MANDIRI & CLEANUP IZIN MATI (RBAC)
-- Database: PostgreSQL Supabase (KEREN SNACK ERP)
-- ============================================================================

BEGIN;

-- 1. Hapus Izin Mati: pos.void_item (Kasir bebas hapus item keranjang tanpa izin khusus)
DELETE FROM public.izin_peran 
WHERE izin_id IN (SELECT id FROM public.izin WHERE kode_izin = 'pos.void_item');

DELETE FROM public.izin 
WHERE kode_izin = 'pos.void_item';

-- 2. Daftarkan Izin Mandiri Baru
INSERT INTO public.izin (kode_izin, nama_izin, grup_izin, deskripsi)
VALUES 
    (
        'production.bom_manage',
        'Kelola Resep BOM & Upah Borongan',
        'Produksi & Manufaktur',
        'Mengatur komposisi bahan resep (BOM) repacking, menyalin resep produk, dan menetapkan tarif upah borongan kemas'
    ),
    (
        'master.territories_manage',
        'Kelola Wilayah & Rute Distribusi',
        'Master Data',
        'Menambah, mengedit, dan menghapus master wilayah pemasaran, kode rute, dan ongkir armada'
    ),
    (
        'system.cache_manage',
        'Pembersihan Cache & Storage Media',
        'Hak Akses & Sistem',
        'Mengosongkan cache file presigned URL dan sinkronisasi Cloudflare R2 storage'
    )
ON CONFLICT (kode_izin) DO UPDATE SET
    nama_izin = EXCLUDED.nama_izin,
    grup_izin = EXCLUDED.grup_izin,
    deskripsi = EXCLUDED.deskripsi;

-- 3. Berikan Izin Baru ke Role Terkait (Owner, Admin, Mandor)
DO $$
DECLARE
    v_bom_id UUID;
    v_territory_id UUID;
    v_cache_id UUID;
    v_owner_id UUID;
    v_admin_id UUID;
    v_mandor_id UUID;
BEGIN
    SELECT id INTO v_bom_id FROM public.izin WHERE kode_izin = 'production.bom_manage';
    SELECT id INTO v_territory_id FROM public.izin WHERE kode_izin = 'master.territories_manage';
    SELECT id INTO v_cache_id FROM public.izin WHERE kode_izin = 'system.cache_manage';

    SELECT id INTO v_owner_id FROM public.peran WHERE nama_peran = 'owner';
    SELECT id INTO v_admin_id FROM public.peran WHERE nama_peran = 'admin';
    SELECT id INTO v_mandor_id FROM public.peran WHERE nama_peran = 'mandor';

    -- OWNER & ADMIN: Dapatkan seluruh izin baru
    IF v_owner_id IS NOT NULL THEN
        IF v_bom_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan) VALUES (v_owner_id, v_bom_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
        IF v_territory_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan) VALUES (v_owner_id, v_territory_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
        IF v_cache_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan) VALUES (v_owner_id, v_cache_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
    END IF;

    IF v_admin_id IS NOT NULL THEN
        IF v_bom_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan) VALUES (v_admin_id, v_bom_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
        IF v_territory_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan) VALUES (v_admin_id, v_territory_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
        IF v_cache_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan) VALUES (v_admin_id, v_cache_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
    END IF;

    -- MANDOR: Dapatkan izin production.bom_manage
    IF v_mandor_id IS NOT NULL AND v_bom_id IS NOT NULL THEN
        INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan) VALUES (v_mandor_id, v_bom_id, TRUE)
        ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
    END IF;
END $$;

COMMIT;

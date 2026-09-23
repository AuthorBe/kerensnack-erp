-- ==============================================================================
-- MIGRATION 66: PENAMBAHAN POSISI KARYAWAN DAN ROLE SISTEM PERMANEN "GUDANG"
-- ==============================================================================
-- 1. Memperbarui CHECK constraint chk_pengguna_posisi_valid pada public.pengguna
--    agar mengizinkan posisi 'gudang' bersama posisi resmi lainnya.
-- 2. Mendaftarkan 'gudang' sebagai Peran Sistem Bawaan resmi (Protected Role)
--    beserta pemetaan izin default (izin_peran) untuk pergudangan, persediaan,
--    penerimaan pembelian vendor, dan pemrosesan pesanan PO.
-- ==============================================================================

BEGIN;

-- 1. Perbarui CHECK constraint daftar posisi sah pada public.pengguna
ALTER TABLE public.pengguna
DROP CONSTRAINT IF EXISTS chk_pengguna_posisi_valid;

ALTER TABLE public.pengguna
ADD CONSTRAINT chk_pengguna_posisi_valid
CHECK (posisi IN ('developer', 'owner', 'admin', 'mandor', 'pengemasan', 'sales', 'driver', 'gudang'));

-- 2. Injeksi / Pastikan Role 'gudang' ada di public.peran
DO $$
DECLARE
    v_gudang_role_id UUID := '11111111-1111-1111-1111-111111111104';
BEGIN
    INSERT INTO public.peran (id, nama_peran, deskripsi, dibuat_pada, diubah_pada)
    VALUES (
        v_gudang_role_id,
        'gudang',
        'Staf Gudang & Logistik Persediaan',
        NOW(),
        NOW()
    )
    ON CONFLICT (nama_peran) DO UPDATE SET
        deskripsi = EXCLUDED.deskripsi,
        diubah_pada = NOW()
    RETURNING id INTO v_gudang_role_id;

    -- 3. Injeksi Izin Bawaan Role Gudang (Stok, Opname, Waste, Terima Barang, PO Masuk, Lihat Produk/Bahan)
    DELETE FROM public.izin_peran WHERE peran_id = v_gudang_role_id;

    INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
    SELECT v_gudang_role_id, id, TRUE 
    FROM public.izin
    WHERE kode_izin IN (
        'inventory.view_all',
        'inventory.opname',
        'inventory.waste',
        'purchases.view',
        'purchases.receive',
        'orders.po_view_all',
        'orders.po_process',
        'orders.po_print',
        'master.products_view',
        'master.materials_manage'
    );
END $$;

COMMIT;

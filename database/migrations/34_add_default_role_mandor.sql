-- ==============================================================================
-- MIGRATION 34: PENAMBAHAN ROLE SISTEM DEFAULT "MANDOR"
-- ==============================================================================
-- Menjadikan 'mandor' sebagai salah satu Peran Sistem Default resmi (Protected Role)
-- beserta pemetaan izin bawaan (izin_peran) untuk persediaan, gudang & produksi.

DO $$
DECLARE
    v_mandor_id UUID := '11111111-1111-1111-1111-111111111103';
BEGIN
    -- 1. Pastikan Peran 'mandor' ada di tabel public.peran
    INSERT INTO public.peran (id, nama_peran, deskripsi, dibuat_pada, diubah_pada)
    VALUES (
        v_mandor_id,
        'mandor',
        'Mandor Produksi & Pengawas Pengemasan',
        NOW(),
        NOW()
    )
    ON CONFLICT (nama_peran) DO UPDATE SET
        deskripsi = EXCLUDED.deskripsi,
        diubah_pada = NOW()
    RETURNING id INTO v_mandor_id;

    -- 2. Bersihkan dan Injeksi Izin Bawaan Mandor (Stok, Persediaan, Packing & PO)
    DELETE FROM public.izin_peran WHERE peran_id = v_mandor_id;

    INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
    SELECT v_mandor_id, id, TRUE 
    FROM public.izin
    WHERE kode_izin IN (
        'inventory.view_all',
        'inventory.opname',
        'inventory.waste',
        'master.products_view',
        'master.materials_manage',
        'orders.po_view_all',
        'orders.po_process',
        'orders.po_print'
    );

    -- 3. Sinkronisasi Akun Pengguna Bawaan 'mandor'
    UPDATE public.pengguna 
    SET peran_id = v_mandor_id,
        posisi = 'mandor',
        diubah_pada = NOW()
    WHERE nama_pengguna = 'mandor' 
       OR id = '00000000-0000-0000-0000-000000000003';

END $$;

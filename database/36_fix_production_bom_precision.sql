-- database/36_fix_production_bom_precision.sql
-- ==============================================================================
-- MIGRASI 36: PRESISI DESIMAL BAHAN BAKU & KEMASAN BOM PRODUKSI REPACKING
-- ==============================================================================

CREATE OR REPLACE FUNCTION public.fn_trg_produksi_harian_after_insert()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
DECLARE
    v_stok_lama NUMERIC(15, 2);
    v_stok_baru NUMERIC(15, 2);
    v_total_pcs NUMERIC(15, 2);
    r_bom RECORD;
    v_bahan_stok_lama NUMERIC(15, 2);
    v_bahan_stok_baru NUMERIC(15, 2);
    v_pemakaian_bahan NUMERIC(15, 4);
BEGIN
    v_total_pcs := (NEW.kuantitas_pcs + NEW.lembur_pcs)::NUMERIC;

    -- 1. Tambah Stok Fisik Barang Jadi
    SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_stok_lama 
    FROM public.item 
    WHERE id = NEW.item_id;

    v_stok_baru := v_stok_lama + v_total_pcs;

    UPDATE public.item 
    SET stok_fisik_saat_ini = v_stok_baru, diubah_pada = NOW()
    WHERE id = NEW.item_id;

    INSERT INTO public.riwayat_stok (
        item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
        referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
    ) VALUES (
        NEW.item_id, 'produksi_masuk', v_total_pcs, v_stok_lama, v_stok_baru,
        'produksi_harian', NEW.id, 'Hasil produksi borongan karyawan', NEW.dicatat_oleh, NOW()
    );

    -- 2. Kurangi Bahan Baku Curah & Kemasan Sesuai Formula BOM (Presisi Desimal)
    FOR r_bom IN 
        SELECT item_bahan_id, jumlah_kebutuhan 
        FROM public.komposisi_item 
        WHERE item_jadi_id = NEW.item_id
    LOOP
        v_pemakaian_bahan := ROUND((v_total_pcs * r_bom.jumlah_kebutuhan)::NUMERIC, 4);

        SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_bahan_stok_lama 
        FROM public.item 
        WHERE id = r_bom.item_bahan_id;

        v_bahan_stok_baru := v_bahan_stok_lama - v_pemakaian_bahan;

        UPDATE public.item 
        SET stok_fisik_saat_ini = v_bahan_stok_baru, diubah_pada = NOW()
        WHERE id = r_bom.item_bahan_id;

        INSERT INTO public.riwayat_stok (
            item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
            referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
        ) VALUES (
            r_bom.item_bahan_id, 'bahan_terpakai_produksi', -v_pemakaian_bahan, 
            v_bahan_stok_lama, v_bahan_stok_baru,
            'produksi_harian', NEW.id, 'Pemakaian bahan baku repacking', NEW.dicatat_oleh, NOW()
        );
    END LOOP;

    RETURN NEW;
END;
$function$;

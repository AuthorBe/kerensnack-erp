-- database/migrations/94_add_potong_sesuai_bal_to_komposisi_item_and_harden_production.sql
-- ==============================================================================
-- MIGRASI 94: PENGURANGAN BAHAN HYBRID (BAL vs PCS) & PROTEKSI STOK PRODUKSI
-- ==============================================================================

BEGIN;

-- 1. Tambah kolom potong_sesuai_bal pada tabel komposisi_item jika belum ada
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'komposisi_item' 
          AND column_name = 'potong_sesuai_bal'
    ) THEN
        ALTER TABLE public.komposisi_item ADD COLUMN potong_sesuai_bal BOOLEAN NOT NULL DEFAULT FALSE;
    END IF;
END $$;

-- 2. Perbarui Trigger AFTER INSERT pada produksi_harian
CREATE OR REPLACE FUNCTION public.fn_trg_produksi_harian_after_insert()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $function$
DECLARE
    v_stok_lama NUMERIC(15, 2);
    v_stok_baru NUMERIC(15, 2);
    v_total_pcs NUMERIC(15, 2);
    v_total_bal NUMERIC(15, 2);
    r_bom RECORD;
    v_bahan_stok_lama NUMERIC(15, 2);
    v_bahan_stok_baru NUMERIC(15, 2);
    v_pemakaian_bahan NUMERIC(15, 4);
BEGIN
    v_total_pcs := (NEW.kuantitas_pcs + NEW.lembur_pcs)::NUMERIC;
    v_total_bal := (COALESCE(NEW.kuantitas_bal, 0) + COALESCE(NEW.lembur_bal, 0))::NUMERIC;

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

    -- 2. Kurangi Bahan Baku Curah & Kemasan Sesuai Formula BOM (Hybrid Bal / Pcs)
    FOR r_bom IN 
        SELECT ki.item_bahan_id, ki.jumlah_kebutuhan, COALESCE(ki.potong_sesuai_bal, FALSE) as potong_sesuai_bal, ib.nama_item, ib.satuan_dasar
        FROM public.komposisi_item ki
        JOIN public.item ib ON ki.item_bahan_id = ib.id
        WHERE ki.item_jadi_id = NEW.item_id
    LOOP
        IF r_bom.potong_sesuai_bal IS TRUE THEN
            v_pemakaian_bahan := ROUND(v_total_bal, 4);
        ELSE
            v_pemakaian_bahan := ROUND((v_total_pcs * r_bom.jumlah_kebutuhan)::NUMERIC, 4);
        END IF;

        IF v_pemakaian_bahan <= 0 THEN
            CONTINUE;
        END IF;

        SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_bahan_stok_lama 
        FROM public.item 
        WHERE id = r_bom.item_bahan_id;

        IF v_bahan_stok_lama < v_pemakaian_bahan THEN
            RAISE EXCEPTION 'Stok bahan "%" tidak mencukupi untuk repacking. Tersedia: % %, dibutuhkan: % %.',
                r_bom.nama_item, v_bahan_stok_lama, r_bom.satuan_dasar, v_pemakaian_bahan, r_bom.satuan_dasar;
        END IF;

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

REVOKE EXECUTE ON FUNCTION public.fn_trg_produksi_harian_after_insert() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_trg_produksi_harian_after_insert() TO postgres, service_role;

DROP TRIGGER IF EXISTS trg_produksi_harian_after_insert ON public.produksi_harian;
CREATE TRIGGER trg_produksi_harian_after_insert
AFTER INSERT ON public.produksi_harian
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_produksi_harian_after_insert();

-- 3. Perbarui Trigger AFTER UPDATE pada produksi_harian
CREATE OR REPLACE FUNCTION public.fn_trg_produksi_harian_after_update()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $function$
DECLARE
    v_total_pcs_lama NUMERIC(15, 2);
    v_total_pcs_baru NUMERIC(15, 2);
    v_delta_pcs NUMERIC(15, 2);
    v_total_bal_lama NUMERIC(15, 2);
    v_total_bal_baru NUMERIC(15, 2);
    v_delta_bal NUMERIC(15, 2);
    v_stok_lama NUMERIC(15, 2);
    v_stok_baru NUMERIC(15, 2);
    r_bom RECORD;
    v_bahan_stok_lama NUMERIC(15, 2);
    v_bahan_stok_baru NUMERIC(15, 2);
    v_delta_bahan NUMERIC(15, 4);
BEGIN
    v_total_pcs_lama := (OLD.kuantitas_pcs + OLD.lembur_pcs)::NUMERIC;
    v_total_pcs_baru := (NEW.kuantitas_pcs + NEW.lembur_pcs)::NUMERIC;
    v_delta_pcs := v_total_pcs_baru - v_total_pcs_lama;

    v_total_bal_lama := (COALESCE(OLD.kuantitas_bal, 0) + COALESCE(OLD.lembur_bal, 0))::NUMERIC;
    v_total_bal_baru := (COALESCE(NEW.kuantitas_bal, 0) + COALESCE(NEW.lembur_bal, 0))::NUMERIC;
    v_delta_bal := v_total_bal_baru - v_total_bal_lama;

    IF v_delta_pcs = 0 AND v_delta_bal = 0 AND OLD.item_id = NEW.item_id THEN
        RETURN NEW;
    END IF;

    -- 1. Sesuaikan Barang Jadi
    SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_stok_lama FROM public.item WHERE id = NEW.item_id;
    v_stok_baru := v_stok_lama + v_delta_pcs;

    -- Proteksi Anti-Stok Minus Barang Jadi
    IF v_stok_baru < 0 THEN
        RAISE EXCEPTION 'Koreksi produksi gagal: Stok barang jadi "%" saat ini tersisa % pcs, tidak mencukupi untuk pengurangan koreksi sebesar % pcs karena barang sudah laku terjual.',
            (SELECT nama_item FROM public.item WHERE id = NEW.item_id), v_stok_lama, ABS(v_delta_pcs);
    END IF;

    IF v_delta_pcs <> 0 THEN
        UPDATE public.item 
        SET stok_fisik_saat_ini = v_stok_baru, diubah_pada = NOW() 
        WHERE id = NEW.item_id;

        INSERT INTO public.riwayat_stok (
            item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
            referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
        ) VALUES (
            NEW.item_id, 'penyesuaian_opname_koreksi', v_delta_pcs, v_stok_lama, v_stok_baru,
            'produksi_harian', NEW.id, 'Koreksi kuantitas hasil produksi harian', NEW.dicatat_oleh, NOW()
        );
    END IF;

    -- 2. Sesuaikan Bahan Baku & Kemasan BOM
    FOR r_bom IN 
        SELECT ki.item_bahan_id, ki.jumlah_kebutuhan, COALESCE(ki.potong_sesuai_bal, FALSE) as potong_sesuai_bal, ib.nama_item, ib.satuan_dasar
        FROM public.komposisi_item ki
        JOIN public.item ib ON ki.item_bahan_id = ib.id
        WHERE ki.item_jadi_id = NEW.item_id
    LOOP
        IF r_bom.potong_sesuai_bal IS TRUE THEN
            v_delta_bahan := ROUND(v_delta_bal, 4);
        ELSE
            v_delta_bahan := ROUND((v_delta_pcs * r_bom.jumlah_kebutuhan)::NUMERIC, 4);
        END IF;

        IF v_delta_bahan = 0 THEN
            CONTINUE;
        END IF;

        SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_bahan_stok_lama 
        FROM public.item 
        WHERE id = r_bom.item_bahan_id;

        IF v_delta_bahan > 0 AND v_bahan_stok_lama < v_delta_bahan THEN
            RAISE EXCEPTION 'Koreksi produksi gagal: Stok bahan "%" tidak mencukupi penambahan. Tersedia: % %, dibutuhkan tambahan: % %.',
                r_bom.nama_item, v_bahan_stok_lama, r_bom.satuan_dasar, v_delta_bahan, r_bom.satuan_dasar;
        END IF;

        v_bahan_stok_baru := v_bahan_stok_lama - v_delta_bahan;

        UPDATE public.item 
        SET stok_fisik_saat_ini = v_bahan_stok_baru, diubah_pada = NOW()
        WHERE id = r_bom.item_bahan_id;

        INSERT INTO public.riwayat_stok (
            item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
            referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
        ) VALUES (
            r_bom.item_bahan_id, 'penyesuaian_opname_koreksi', -v_delta_bahan, 
            v_bahan_stok_lama, v_bahan_stok_baru,
            'produksi_harian', NEW.id, 'Koreksi pemakaian bahan produksi', NEW.dicatat_oleh, NOW()
        );
    END LOOP;

    RETURN NEW;
END;
$function$;

REVOKE EXECUTE ON FUNCTION public.fn_trg_produksi_harian_after_update() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_trg_produksi_harian_after_update() TO postgres, service_role;

DROP TRIGGER IF EXISTS trg_produksi_harian_after_update ON public.produksi_harian;
CREATE TRIGGER trg_produksi_harian_after_update
AFTER UPDATE ON public.produksi_harian
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_produksi_harian_after_update();

-- 4. Perbarui Trigger AFTER DELETE pada produksi_harian
CREATE OR REPLACE FUNCTION public.fn_trg_produksi_harian_after_delete()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $function$
DECLARE
    v_total_pcs NUMERIC(15, 2);
    v_total_bal NUMERIC(15, 2);
    v_stok_lama NUMERIC(15, 2);
    v_stok_baru NUMERIC(15, 2);
    r_bom RECORD;
    v_bahan_stok_lama NUMERIC(15, 2);
    v_bahan_stok_baru NUMERIC(15, 2);
    v_pemakaian_bahan NUMERIC(15, 4);
BEGIN
    v_total_pcs := (OLD.kuantitas_pcs + OLD.lembur_pcs)::NUMERIC;
    v_total_bal := (COALESCE(OLD.kuantitas_bal, 0) + COALESCE(OLD.lembur_bal, 0))::NUMERIC;

    -- 1. Revert Barang Jadi (Kurangi kembali hasil produksi)
    SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_stok_lama FROM public.item WHERE id = OLD.item_id;
    v_stok_baru := v_stok_lama - v_total_pcs;

    -- Proteksi Anti-Stok Minus Barang Jadi
    IF v_stok_baru < 0 THEN
        RAISE EXCEPTION 'Pembatalan produksi gagal: Stok barang jadi "%" saat ini tersisa % pcs, tidak mencukupi untuk pembatalan produksi sebesar % pcs karena sebagian barang sudah laku terjual.',
            (SELECT nama_item FROM public.item WHERE id = OLD.item_id), v_stok_lama, v_total_pcs;
    END IF;

    UPDATE public.item 
    SET stok_fisik_saat_ini = v_stok_baru, diubah_pada = NOW() 
    WHERE id = OLD.item_id;

    INSERT INTO public.riwayat_stok (
        item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
        referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
    ) VALUES (
        OLD.item_id, 'produksi_batal', -v_total_pcs, v_stok_lama, v_stok_baru,
        'produksi_harian', OLD.id, 'Pembatalan / hapus catatan produksi', OLD.dicatat_oleh, NOW()
    );

    -- 2. Revert Bahan Baku & Kemasan (Kembalikan bahan ke stok gudang)
    FOR r_bom IN 
        SELECT ki.item_bahan_id, ki.jumlah_kebutuhan, COALESCE(ki.potong_sesuai_bal, FALSE) as potong_sesuai_bal
        FROM public.komposisi_item ki
        WHERE ki.item_jadi_id = OLD.item_id
    LOOP
        IF r_bom.potong_sesuai_bal IS TRUE THEN
            v_pemakaian_bahan := ROUND(v_total_bal, 4);
        ELSE
            v_pemakaian_bahan := ROUND((v_total_pcs * r_bom.jumlah_kebutuhan)::NUMERIC, 4);
        END IF;

        IF v_pemakaian_bahan <= 0 THEN
            CONTINUE;
        END IF;

        SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_bahan_stok_lama 
        FROM public.item 
        WHERE id = r_bom.item_bahan_id;

        v_bahan_stok_baru := v_bahan_stok_lama + v_pemakaian_bahan;

        UPDATE public.item 
        SET stok_fisik_saat_ini = v_bahan_stok_baru, diubah_pada = NOW()
        WHERE id = r_bom.item_bahan_id;

        INSERT INTO public.riwayat_stok (
            item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
            referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
        ) VALUES (
            r_bom.item_bahan_id, 'produksi_batal', v_pemakaian_bahan, 
            v_bahan_stok_lama, v_bahan_stok_baru,
            'produksi_harian', OLD.id, 'Pengembalian bahan akibat pembatalan produksi', OLD.dicatat_oleh, NOW()
        );
    END LOOP;

    RETURN OLD;
END;
$function$;

REVOKE EXECUTE ON FUNCTION public.fn_trg_produksi_harian_after_delete() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_trg_produksi_harian_after_delete() TO postgres, service_role;

DROP TRIGGER IF EXISTS trg_produksi_harian_after_delete ON public.produksi_harian;
CREATE TRIGGER trg_produksi_harian_after_delete
AFTER DELETE ON public.produksi_harian
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_produksi_harian_after_delete();

COMMIT;

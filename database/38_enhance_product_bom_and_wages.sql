-- database/38_enhance_product_bom_and_wages.sql
-- ==============================================================================
-- MIGRASI 38: UPAH BUNGKUS PER ITEM, INTEGRITAS RESEP BOM & TRIGGER REPACKING
-- ==============================================================================

-- 1. Tambahkan kolom upah_per_bungkus langsung pada tabel item (Barang Jadi)
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'item' 
          AND column_name = 'upah_per_bungkus'
    ) THEN
        ALTER TABLE public.item ADD COLUMN upah_per_bungkus NUMERIC(15, 2) DEFAULT NULL;
    END IF;
END $$;

-- 2. Tambahkan Check Constraint jumlah_kebutuhan > 0 pada komposisi_item
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'check_komposisi_item_jumlah_positif'
    ) THEN
        ALTER TABLE public.komposisi_item 
        ADD CONSTRAINT check_komposisi_item_jumlah_positif 
        CHECK (jumlah_kebutuhan > 0);
    END IF;
END $$;

-- 2B. Update riwayat_stok_tipe_mutasi_check agar mengizinkan produksi_batal dan penyesuaian_opname_koreksi
ALTER TABLE public.riwayat_stok DROP CONSTRAINT IF EXISTS riwayat_stok_tipe_mutasi_check;
ALTER TABLE public.riwayat_stok ADD CONSTRAINT riwayat_stok_tipe_mutasi_check
CHECK (((tipe_mutasi)::text = ANY ((ARRAY[
    'produksi_masuk'::character varying,
    'bahan_terpakai_produksi'::character varying,
    'produksi_batal'::character varying,
    'penyesuaian_opname_koreksi'::character varying,
    'penjualan_keluar'::character varying,
    'pembelian_masuk'::character varying,
    'penyesuaian_opname_tambah'::character varying,
    'penyesuaian_opname_kurang'::character varying,
    'retur_pelanggan_masuk'::character varying,
    'konsinyasi_keluar'::character varying,
    'konsinyasi_retur_masuk'::character varying,
    'konsinyasi_retur_rusak'::character varying,
    'item_keluar_waste'::character varying
])::text[])));

-- 3. Perbarui Trigger AFTER INSERT pada produksi_harian dengan pengecekan ketersediaan stok bahan yang informatif
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
    v_nama_bahan VARCHAR;
    v_satuan_bahan VARCHAR;
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
        SELECT ki.item_bahan_id, ki.jumlah_kebutuhan, ib.nama_item, ib.satuan_dasar
        FROM public.komposisi_item ki
        JOIN public.item ib ON ki.item_bahan_id = ib.id
        WHERE ki.item_jadi_id = NEW.item_id
    LOOP
        v_pemakaian_bahan := ROUND((v_total_pcs * r_bom.jumlah_kebutuhan)::NUMERIC, 4);

        SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_bahan_stok_lama 
        FROM public.item 
        WHERE id = r_bom.item_bahan_id;

        -- Validasi ketersediaan stok bahan di gudang
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

-- 4. Trigger AFTER UPDATE pada produksi_harian (Koreksi Kuantitas & Penyesuaian Bahan)
CREATE OR REPLACE FUNCTION public.fn_trg_produksi_harian_after_update()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
AS $function$
DECLARE
    v_total_pcs_lama NUMERIC(15, 2);
    v_total_pcs_baru NUMERIC(15, 2);
    v_delta_pcs NUMERIC(15, 2);
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

    IF v_delta_pcs = 0 AND OLD.item_id = NEW.item_id THEN
        RETURN NEW;
    END IF;

    -- Sesuaikan Barang Jadi
    SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_stok_lama FROM public.item WHERE id = NEW.item_id;
    v_stok_baru := v_stok_lama + v_delta_pcs;

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

    -- Sesuaikan Bahan Baku & Kemasan BOM
    FOR r_bom IN 
        SELECT ki.item_bahan_id, ki.jumlah_kebutuhan, ib.nama_item, ib.satuan_dasar
        FROM public.komposisi_item ki
        JOIN public.item ib ON ki.item_bahan_id = ib.id
        WHERE ki.item_jadi_id = NEW.item_id
    LOOP
        v_delta_bahan := ROUND((v_delta_pcs * r_bom.jumlah_kebutuhan)::NUMERIC, 4);

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

DROP TRIGGER IF EXISTS trg_produksi_harian_after_update ON public.produksi_harian;
CREATE TRIGGER trg_produksi_harian_after_update
AFTER UPDATE ON public.produksi_harian
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_produksi_harian_after_update();

-- 5. Trigger AFTER DELETE pada produksi_harian (Pembatalan Produksi & Revert Stok)
CREATE OR REPLACE FUNCTION public.fn_trg_produksi_harian_after_delete()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
AS $function$
DECLARE
    v_total_pcs NUMERIC(15, 2);
    v_stok_lama NUMERIC(15, 2);
    v_stok_baru NUMERIC(15, 2);
    r_bom RECORD;
    v_bahan_stok_lama NUMERIC(15, 2);
    v_bahan_stok_baru NUMERIC(15, 2);
    v_pemakaian_bahan NUMERIC(15, 4);
BEGIN
    v_total_pcs := (OLD.kuantitas_pcs + OLD.lembur_pcs)::NUMERIC;

    -- Revert Barang Jadi (Kurangi kembali hasil produksi)
    SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_stok_lama FROM public.item WHERE id = OLD.item_id;
    v_stok_baru := v_stok_lama - v_total_pcs;

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

    -- Revert Bahan Baku & Kemasan (Kembalikan bahan ke stok gudang)
    FOR r_bom IN 
        SELECT ki.item_bahan_id, ki.jumlah_kebutuhan
        FROM public.komposisi_item ki
        WHERE ki.item_jadi_id = OLD.item_id
    LOOP
        v_pemakaian_bahan := ROUND((v_total_pcs * r_bom.jumlah_kebutuhan)::NUMERIC, 4);

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

DROP TRIGGER IF EXISTS trg_produksi_harian_after_delete ON public.produksi_harian;
CREATE TRIGGER trg_produksi_harian_after_delete
AFTER DELETE ON public.produksi_harian
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_produksi_harian_after_delete();

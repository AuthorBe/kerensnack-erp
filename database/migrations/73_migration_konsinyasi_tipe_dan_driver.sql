-- ============================================================================
-- Migrasi 73: Konsinyasi Tipe Toko & Driver Pengirim Fisik
-- 1. Tambah kolom tipe_konsinyasi pada public.pelanggan ('rolling_nota' vs 'kolektif_tagihan')
-- 2. Tambah kolom driver_pengirim_id pada public.kunjungan_konsinyasi
-- 3. Update public.fn_proses_kunjungan_konsinyasi dengan parameter p_driver_pengirim_id
-- 4. Kepatuhan mutlak AGENTS.md: Atomik BEGIN; ... COMMIT;
-- ============================================================================

BEGIN;

-- 1. Tambah kolom tipe_konsinyasi pada tabel pelanggan
ALTER TABLE public.pelanggan 
ADD COLUMN IF NOT EXISTS tipe_konsinyasi VARCHAR(30) NOT NULL DEFAULT 'rolling_nota' 
CHECK (tipe_konsinyasi IN ('rolling_nota', 'kolektif_tagihan'));

COMMENT ON COLUMN public.pelanggan.tipe_konsinyasi IS 'Model operasional konsinyasi: rolling_nota (Per Kunjungan Lapangan) vs kolektif_tagihan (Tagihan Terpusat / Bulanan)';

-- 2. Tambah kolom driver_pengirim_id pada tabel kunjungan_konsinyasi
ALTER TABLE public.kunjungan_konsinyasi 
ADD COLUMN IF NOT EXISTS driver_pengirim_id UUID REFERENCES public.karyawan(id) ON DELETE SET NULL;

COMMENT ON COLUMN public.kunjungan_konsinyasi.driver_pengirim_id IS 'Driver / kurir yang secara fisik mengantarkan muatan barang ke toko';

-- 3. Hapus versi lama function jika ada untuk mencegah collision overload
DROP FUNCTION IF EXISTS public.fn_proses_kunjungan_konsinyasi(uuid, uuid, jsonb, text, text, uuid);

-- 4. Buat function fn_proses_kunjungan_konsinyasi dengan parameter p_driver_pengirim_id
CREATE OR REPLACE FUNCTION public.fn_proses_kunjungan_konsinyasi(
    p_pelanggan_id uuid, 
    p_sales_driver_id uuid, 
    p_rincian jsonb, 
    p_keterangan text DEFAULT NULL::text, 
    p_foto_kunjungan text DEFAULT NULL::text, 
    p_pengguna_id uuid DEFAULT NULL::uuid,
    p_driver_pengirim_id uuid DEFAULT NULL::uuid
)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
AS $function$
DECLARE
    v_kunjungan_id UUID;
    v_nomor_kunjungan VARCHAR(50);
    v_pengguna_id UUID := p_pengguna_id;
    v_sales_id UUID := p_sales_driver_id;
    v_driver_id UUID := p_driver_pengirim_id;
    v_stok_titip_lama INT;
    v_sisa_rak_hitung INT;
    v_stok_rak_baru INT;
    v_stok_gudang_lama INT;
    v_stok_gudang_baru INT;
    v_laku INT;
    v_total_laku_netto NUMERIC(15,2) := 0.00;
    v_harga_info JSONB;
    v_harga_deal NUMERIC(15,2) := 0.00;
    v_subtotal NUMERIC(15,2) := 0.00;
    r_item RECORD;
    v_selisih INT := 0;
    v_harga_pokok NUMERIC(15, 2) := 0.00;
    v_nilai_kerugian NUMERIC(15, 2) := 0.00;
    v_pending_hilang_lama INT := 0;
    v_pending_hilang_baru INT := 0;
BEGIN
    v_nomor_kunjungan := 'KONSIN-' || TO_CHAR(CLOCK_TIMESTAMP(), 'YYYYMMDD-HH24MISS') || '-' || LPAD(FLOOR(RANDOM() * 10000)::TEXT, 4, '0');

    -- Pastikan user_id valid untuk audit trail
    IF v_pengguna_id IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM public.pengguna WHERE id = v_pengguna_id) THEN
            SELECT pengguna_id INTO v_pengguna_id FROM public.karyawan WHERE id = p_sales_driver_id LIMIT 1;
        END IF;
    END IF;
    IF v_pengguna_id IS NULL THEN
        SELECT id INTO v_pengguna_id FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
    END IF;

    -- Validasi sales pembina: jika tidak disediakan, otomatis ambil dari toko
    IF v_sales_id IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM public.karyawan WHERE id = v_sales_id) THEN
            SELECT sales_driver_id INTO v_sales_id FROM public.pelanggan WHERE id = p_pelanggan_id;
        END IF;
    ELSE
        SELECT sales_driver_id INTO v_sales_id FROM public.pelanggan WHERE id = p_pelanggan_id;
    END IF;

    -- Validasi driver pengirim fisik (jika ada)
    IF v_driver_id IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM public.karyawan WHERE id = v_driver_id) THEN
            v_driver_id := NULL;
        END IF;
    END IF;

    -- 1. Insert Header Kunjungan (Menyimpan Sales Pembina dan Driver Pengirim)
    INSERT INTO public.kunjungan_konsinyasi (
        nomor_kunjungan, pelanggan_id, sales_driver_id, driver_pengirim_id, tanggal_kunjungan,
        catatan, foto_kunjungan, dibuat_oleh, dibuat_pada
    ) VALUES (
        v_nomor_kunjungan, p_pelanggan_id, v_sales_id, v_driver_id, CURRENT_DATE,
        p_keterangan, p_foto_kunjungan, v_pengguna_id, NOW()
    ) RETURNING id INTO v_kunjungan_id;

    -- Update tanggal diubah toko (mengunci baris pelanggan secara eksklusif)
    UPDATE public.pelanggan 
    SET diubah_pada = NOW() 
    WHERE id = p_pelanggan_id;

    -- 2. Loop Rincian Produk
    FOR r_item IN 
        SELECT 
            (elem->>'item_id')::UUID AS item_id,
            CASE 
                WHEN (elem->>'sisa_fisik_di_rak')::INT IS NOT NULL 
                THEN GREATEST(0, (elem->>'sisa_fisik_di_rak')::INT) 
                WHEN (elem->>'sisa_fisik')::INT IS NOT NULL 
                THEN GREATEST(0, (elem->>'sisa_fisik')::INT) 
                ELSE NULL 
            END AS sisa_fisik_param,
            GREATEST(0, COALESCE((elem->>'tambah_titip_baru')::INT, (elem->>'kiriman_hari_ini')::INT, 0)) AS tambah_titip_baru,
            GREATEST(0, COALESCE((elem->>'retur_bagus')::INT, 0)) AS retur_bagus,
            GREATEST(0, COALESCE((elem->>'retur_rusak')::INT, 0)) AS retur_rusak,
            CASE 
                WHEN (elem->>'jumlah_laku')::INT IS NOT NULL 
                THEN GREATEST(0, (elem->>'jumlah_laku')::INT) 
                ELSE NULL 
            END AS jumlah_laku_param,
            COALESCE((elem->>'selisih_qty')::INT, 0) AS selisih_qty
        FROM jsonb_array_elements(p_rincian) AS elem
    LOOP
        -- Ambil stok titip lama dan pending barang hilang di toko dengan ROW LOCK (FOR UPDATE)
        SELECT COALESCE(stok_titip_saat_ini, 0), COALESCE(stok_hilang_pending, 0)
        INTO v_stok_titip_lama, v_pending_hilang_lama
        FROM public.stok_konsinyasi_toko
        WHERE pelanggan_id = p_pelanggan_id AND item_id = r_item.item_id
        FOR UPDATE;

        IF NOT FOUND THEN
            v_stok_titip_lama := 0;
            v_pending_hilang_lama := 0;
            
            INSERT INTO public.stok_konsinyasi_toko (
                pelanggan_id, item_id, stok_titip_saat_ini, stok_hilang_pending, terakhir_opname_pada
            ) VALUES (
                p_pelanggan_id, r_item.item_id, 0, 0, NOW()
            )
            ON CONFLICT (pelanggan_id, item_id) DO NOTHING;
        END IF;

        -- Ambil HPP untuk perhitungan nilai kerugian retur rusak
        SELECT COALESCE(harga_pokok_pembelian, 0) INTO v_harga_pokok
        FROM public.item WHERE id = r_item.item_id;

        -- A. Proses Retur Bagus: kembalikan ke gudang siap jual
        IF r_item.retur_bagus > 0 THEN
            SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_stok_gudang_lama 
            FROM public.item 
            WHERE id = r_item.item_id 
            FOR UPDATE;

            v_stok_gudang_baru := v_stok_gudang_lama + r_item.retur_bagus;

            UPDATE public.item 
            SET stok_fisik_saat_ini = v_stok_gudang_baru, diubah_pada = NOW()
            WHERE id = r_item.item_id;

            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
            ) VALUES (
                r_item.item_id, 'konsinyasi_retur_masuk', r_item.retur_bagus,
                v_stok_gudang_lama, v_stok_gudang_baru, 'kunjungan_konsinyasi', v_kunjungan_id,
                'Retur barang bagus dari rak konsinyasi kembali ke stok siap jual', v_pengguna_id, NOW()
            );
        END IF;

        -- B. Proses Retur Rusak: hitung kerugian HPP resmi
        IF r_item.retur_rusak > 0 THEN
            v_nilai_kerugian := r_item.retur_rusak * v_harga_pokok;

            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
            ) VALUES (
                r_item.item_id, 'konsinyasi_retur_rusak', r_item.retur_rusak,
                v_stok_titip_lama, GREATEST(0, v_stok_titip_lama - r_item.retur_rusak), 'kunjungan_konsinyasi', v_kunjungan_id,
                'Retur barang rusak/BS ditarik dari rak konsinyasi toko', v_pengguna_id, NOW()
            );
        ELSE
            v_nilai_kerugian := 0.00;
        END IF;

        -- C. Hitung Saldo Sisa Fisik & Laku Terjual
        v_selisih := r_item.selisih_qty;

        IF r_item.sisa_fisik_param IS NOT NULL THEN
            v_sisa_rak_hitung := GREATEST(0, r_item.sisa_fisik_param);
        ELSE
            v_sisa_rak_hitung := v_stok_titip_lama;
        END IF;

        -- Jika jumlah laku diinput eksplisit oleh sales/admin, gunakan itu secara murni
        IF r_item.jumlah_laku_param IS NOT NULL THEN
            v_laku := r_item.jumlah_laku_param;
        ELSE
            v_laku := GREATEST(0, v_stok_titip_lama - (v_sisa_rak_hitung + r_item.retur_bagus + r_item.retur_rusak) + v_selisih);
        END IF;

        -- D. Kelola Akumulasi Pending Barang Hilang di Toko
        IF v_selisih < 0 THEN
            v_pending_hilang_baru := v_pending_hilang_lama + ABS(v_selisih);
        ELSIF v_selisih > 0 THEN
            v_pending_hilang_baru := GREATEST(0, v_pending_hilang_lama - v_selisih);
        ELSE
            v_pending_hilang_baru := v_pending_hilang_lama;
        END IF;

        -- E. Hitung Stok Rak Baru Pasca Kunjungan: Sisa Fisik di Rak + Tambah Titip Baru
        v_stok_rak_baru := v_sisa_rak_hitung + r_item.tambah_titip_baru;

        -- F. Jika ada Tambah Titip Baru (Drop Baru Hari Ini > 0): Potong stok gudang / muatan
        IF r_item.tambah_titip_baru > 0 THEN
            SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_stok_gudang_lama 
            FROM public.item 
            WHERE id = r_item.item_id 
            FOR UPDATE;

            v_stok_gudang_baru := GREATEST(0, v_stok_gudang_lama - r_item.tambah_titip_baru);

            UPDATE public.item 
            SET stok_fisik_saat_ini = v_stok_gudang_baru, diubah_pada = NOW()
            WHERE id = r_item.item_id;

            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
            ) VALUES (
                r_item.item_id, 'konsinyasi_keluar', r_item.tambah_titip_baru,
                v_stok_gudang_lama, v_stok_gudang_baru, 'kunjungan_konsinyasi', v_kunjungan_id,
                'Barang baru dititipkan ke rak konsinyasi toko (+drop)', v_pengguna_id, NOW()
            );
        END IF;

        -- Update Saldo Rak Toko di DB
        UPDATE public.stok_konsinyasi_toko
        SET stok_titip_saat_ini = v_stok_rak_baru,
            stok_hilang_pending = v_pending_hilang_baru,
            terakhir_opname_pada = NOW(),
            diubah_pada = NOW()
        WHERE pelanggan_id = p_pelanggan_id AND item_id = r_item.item_id;

        -- Ambil harga deal konsinyasi untuk item ini
        SELECT public.fn_hitung_harga_jual_item(r_item.item_id, p_pelanggan_id) INTO v_harga_info;
        v_harga_deal := COALESCE((v_harga_info->>'harga_pcs_netto')::NUMERIC(15,2), 0.00);

        IF v_harga_deal = 0.00 THEN
            SELECT COALESCE(gphl.harga_jual_pcs, 15000.00) INTO v_harga_deal
            FROM public.item it
            LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = it.grup_id AND gphl.level_harga = 1
            WHERE it.id = r_item.item_id;
        END IF;

        v_subtotal   := v_laku * v_harga_deal;
        v_total_laku_netto := v_total_laku_netto + v_subtotal;

        -- Insert Rincian Kunjungan
        INSERT INTO public.rincian_kunjungan_konsinyasi (
            kunjungan_id, item_id, stok_titip_awal, tambah_titip_baru, sisa_fisik_di_rak,
            retur_bagus, retur_rusak, jumlah_laku_terjual, selisih_qty,
            harga_satuan_deal, subtotal_laku, harga_pokok_satuan, nilai_kerugian_rusak
        ) VALUES (
            v_kunjungan_id, r_item.item_id, v_stok_titip_lama, r_item.tambah_titip_baru, v_sisa_rak_hitung,
            r_item.retur_bagus, r_item.retur_rusak, v_laku, v_selisih,
            v_harga_deal, v_subtotal, v_harga_pokok, v_nilai_kerugian
        );
    END LOOP;

    -- Update Total Laku Nominal di Header Kunjungan
    UPDATE public.kunjungan_konsinyasi 
    SET total_laku_nominal = v_total_laku_netto 
    WHERE id = v_kunjungan_id;

    RETURN jsonb_build_object(
        'success', true,
        'kunjungan_id', v_kunjungan_id,
        'nomor_kunjungan', v_nomor_kunjungan,
        'total_laku_netto', v_total_laku_netto
    );
END;
$function$;

COMMIT;

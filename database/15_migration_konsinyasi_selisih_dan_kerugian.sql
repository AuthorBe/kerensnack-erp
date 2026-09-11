-- ==============================================================================
-- 15_migration_konsinyasi_selisih_dan_kerugian.sql
-- Migrasi Database: Perbaikan Modul Konsinyasi, Valuasi Kerugian & Rekonsiliasi Selisih
-- ==============================================================================

-- 1. Update HPP item yang masih 0 / NULL menjadi Rp 10.000 (sesuai instruksi user)
UPDATE public.item
SET harga_pokok_pembelian = 10000.00,
    diubah_pada = NOW()
WHERE (harga_pokok_pembelian = 0 OR harga_pokok_pembelian IS NULL)
  AND status_aktif = TRUE;

-- 2. Tambah kolom stok_hilang_pending di stok_konsinyasi_toko untuk memantau akumulasi barang hilang per toko
ALTER TABLE public.stok_konsinyasi_toko
    ADD COLUMN IF NOT EXISTS stok_hilang_pending INT NOT NULL DEFAULT 0;

-- 2b. Tambah kolom foto_kunjungan di kunjungan_konsinyasi (opsional)
ALTER TABLE public.kunjungan_konsinyasi
    ADD COLUMN IF NOT EXISTS foto_kunjungan TEXT;

-- 3. Update CHECK constraint riwayat_stok agar mengizinkan mutasi waste dan penyesuaian selisih konsinyasi
ALTER TABLE public.riwayat_stok DROP CONSTRAINT IF EXISTS riwayat_stok_tipe_mutasi_check;
ALTER TABLE public.riwayat_stok ADD CONSTRAINT riwayat_stok_tipe_mutasi_check
CHECK (((tipe_mutasi)::text = ANY ((ARRAY[
    'produksi_masuk'::character varying,
    'bahan_terpakai_produksi'::character varying,
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

-- 4. Hapus overload lama fungsi fn_proses_kunjungan_konsinyasi agar tidak ambigu
DROP FUNCTION IF EXISTS public.fn_proses_kunjungan_konsinyasi(uuid, uuid, jsonb, uuid);
DROP FUNCTION IF EXISTS public.fn_proses_kunjungan_konsinyasi(uuid, uuid, jsonb, text, text);
DROP FUNCTION IF EXISTS public.fn_proses_kunjungan_konsinyasi(uuid, uuid, jsonb, text, text, uuid);

-- 5. Buat fungsi tunggal terpadu fn_proses_kunjungan_konsinyasi
CREATE OR REPLACE FUNCTION public.fn_proses_kunjungan_konsinyasi(
    p_pelanggan_id uuid,
    p_sales_driver_id uuid,
    p_rincian jsonb,
    p_keterangan text DEFAULT NULL,
    p_foto_kunjungan text DEFAULT NULL,
    p_pengguna_id uuid DEFAULT NULL
) RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
    v_kunjungan_id UUID;
    v_nomor_kunjungan VARCHAR(50);
    v_pengguna_id UUID := p_pengguna_id;
    v_driver_id UUID := p_sales_driver_id;
    v_stok_titip_lama INT;
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

    -- Validasi sales_driver_id
    IF v_driver_id IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM public.karyawan WHERE id = v_driver_id) THEN
            SELECT id INTO v_driver_id FROM public.karyawan WHERE pengguna_id = v_pengguna_id LIMIT 1;
            IF v_driver_id IS NULL THEN
                SELECT id INTO v_driver_id FROM public.karyawan WHERE pengguna_id IS NOT NULL ORDER BY id ASC LIMIT 1;
            END IF;
        END IF;
    ELSE
        SELECT id INTO v_driver_id FROM public.karyawan WHERE pengguna_id = v_pengguna_id LIMIT 1;
        IF v_driver_id IS NULL THEN
            SELECT id INTO v_driver_id FROM public.karyawan WHERE pengguna_id IS NOT NULL ORDER BY id ASC LIMIT 1;
        END IF;
    END IF;

    -- 1. Insert Header Kunjungan
    INSERT INTO public.kunjungan_konsinyasi (
        nomor_kunjungan, pelanggan_id, sales_driver_id, tanggal_kunjungan,
        catatan, foto_kunjungan, dibuat_oleh, dibuat_pada
    ) VALUES (
        v_nomor_kunjungan, p_pelanggan_id, v_driver_id, CURRENT_DATE,
        p_keterangan, p_foto_kunjungan, v_pengguna_id, NOW()
    ) RETURNING id INTO v_kunjungan_id;

    -- Update tanggal diubah toko
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
        -- Ambil stok titip lama dan pending barang hilang di toko
        SELECT COALESCE(stok_titip_saat_ini, 0), COALESCE(stok_hilang_pending, 0)
        INTO v_stok_titip_lama, v_pending_hilang_lama
        FROM public.stok_konsinyasi_toko
        WHERE pelanggan_id = p_pelanggan_id AND item_id = r_item.item_id;

        IF v_stok_titip_lama IS NULL THEN
            v_stok_titip_lama := 0;
        END IF;
        IF v_pending_hilang_lama IS NULL THEN
            v_pending_hilang_lama := 0;
        END IF;

        -- Ambil snapshot HPP master produk untuk valuasi kerugian retur rusak
        SELECT COALESCE(harga_pokok_pembelian, 0.00) INTO v_harga_pokok
        FROM public.item WHERE id = r_item.item_id;

        v_nilai_kerugian := 0.00;

        -- A. Proses Retur Bagus: kembalikan ke stok fisik gudang pusat
        IF r_item.retur_bagus > 0 THEN
            SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_stok_gudang_lama FROM public.item WHERE id = r_item.item_id;
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
        END IF;

        -- C. Hitung Saldo Fisik Rak Baru & Laku Terjual
        v_selisih := r_item.selisih_qty;

        IF r_item.sisa_fisik_param IS NOT NULL THEN
            v_stok_rak_baru := GREATEST(0, r_item.sisa_fisik_param);
        ELSE
            v_stok_rak_baru := v_stok_titip_lama;
        END IF;

        -- Jika jumlah laku diinput eksplisit oleh sales, gunakan itu secara murni
        -- Tagihan toko HANYA dihitung dari laku riil, BUKAN dari barang hilang
        IF r_item.jumlah_laku_param IS NOT NULL THEN
            v_laku := r_item.jumlah_laku_param;
        ELSE
            v_laku := GREATEST(0, v_stok_titip_lama - (v_stok_rak_baru + r_item.retur_bagus + r_item.retur_rusak) + v_selisih);
        END IF;

        -- D. Kelola Akumulasi Pending Barang Hilang di Toko
        -- Jika selisih < 0 (ada barang hilang hari ini): tambahkan ke pending hilang
        -- Jika selisih > 0 (barang lama ketemu kembali): kurangi dari pending hilang
        IF v_selisih < 0 THEN
            v_pending_hilang_baru := v_pending_hilang_lama + ABS(v_selisih);
        ELSIF v_selisih > 0 THEN
            v_pending_hilang_baru := GREATEST(0, v_pending_hilang_lama - v_selisih);
        ELSE
            v_pending_hilang_baru := v_pending_hilang_lama;
        END IF;

        -- E. Hitung Harga Deal & Subtotal Laku
        SELECT public.fn_hitung_harga_jual_item(r_item.item_id, p_pelanggan_id) INTO v_harga_info;
        v_harga_deal := COALESCE((v_harga_info->>'harga_pcs_netto')::NUMERIC(15,2), 0.00);

        IF v_harga_deal = 0.00 THEN
            SELECT COALESCE(gphl.harga_jual_pcs, 15000.00) INTO v_harga_deal
            FROM public.item it
            LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = it.grup_id AND gphl.level_harga = 1
            WHERE it.id = r_item.item_id;
        END IF;

        v_subtotal := v_laku * v_harga_deal;
        v_total_laku_netto := v_total_laku_netto + v_subtotal;

        -- F. Update Saldo Rak Toko = Sisa Fisik Aktual & Pending Hilang
        INSERT INTO public.stok_konsinyasi_toko (
            pelanggan_id, item_id, stok_titip_saat_ini, stok_hilang_pending, terakhir_opname_pada, dibuat_pada, diubah_pada
        ) VALUES (
            p_pelanggan_id, r_item.item_id, v_stok_rak_baru, v_pending_hilang_baru, NOW(), NOW(), NOW()
        )
        ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
            stok_titip_saat_ini = EXCLUDED.stok_titip_saat_ini,
            stok_hilang_pending = EXCLUDED.stok_hilang_pending,
            terakhir_opname_pada = NOW(),
            diubah_pada = NOW();

        -- G. Catat Rincian Kunjungan Konsinyasi
        INSERT INTO public.rincian_kunjungan_konsinyasi (
            kunjungan_id, item_id, stok_titip_awal, tambah_titip_baru,
            sisa_fisik_di_rak, retur_bagus, retur_rusak, jumlah_laku_terjual,
            selisih_qty, harga_satuan_deal, subtotal_laku,
            harga_pokok_satuan, nilai_kerugian_rusak, dibuat_pada
        ) VALUES (
            v_kunjungan_id, r_item.item_id, v_stok_titip_lama, 0,
            v_stok_rak_baru, r_item.retur_bagus, r_item.retur_rusak, v_laku,
            v_selisih, v_harga_deal, v_subtotal,
            v_harga_pokok, v_nilai_kerugian, NOW()
        );
    END LOOP;

    -- 3. Update total laku di kunjungan konsinyasi
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
$$;

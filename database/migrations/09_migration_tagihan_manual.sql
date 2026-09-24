-- ==============================================================================
-- 09_migration_tagihan_manual.sql
-- Migrasi Database: Decoupling Tagihan dari Opname
-- Tambah junction table tagihan_kunjungan, update fn_proses_kunjungan_konsinyasi,
-- dan buat fn_buat_tagihan_konsinyasi untuk pembuatan tagihan manual.
-- ==============================================================================

-- 1. Buat Junction Table tagihan_kunjungan
-- Menghubungkan 1 tagihan (pesanan) ke banyak kunjungan (many-to-many via junction)
-- UNIQUE(kunjungan_id) memastikan 1 kunjungan hanya bisa masuk 1 tagihan
CREATE TABLE IF NOT EXISTS public.tagihan_kunjungan (
    id          UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    pesanan_id  UUID NOT NULL REFERENCES public.pesanan(id) ON DELETE CASCADE,
    kunjungan_id UUID NOT NULL REFERENCES public.kunjungan_konsinyasi(id) ON DELETE RESTRICT,
    dibuat_pada TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    CONSTRAINT tagihan_kunjungan_kunjungan_id_unique UNIQUE (kunjungan_id)
);

CREATE INDEX IF NOT EXISTS idx_tagihan_kunjungan_pesanan   ON public.tagihan_kunjungan(pesanan_id);
CREATE INDEX IF NOT EXISTS idx_tagihan_kunjungan_kunjungan ON public.tagihan_kunjungan(kunjungan_id);

COMMENT ON TABLE public.tagihan_kunjungan IS 
    'Junction table: menghubungkan 1 tagihan konsinyasi (pesanan) ke 1 atau lebih kunjungan. UNIQUE(kunjungan_id) menjamin 1 kunjungan tidak bisa masuk 2 tagihan berbeda.';

-- 2. Backfill: isi tagihan_kunjungan dari data historis (kunjungan yang sudah punya pesanan_id)
INSERT INTO public.tagihan_kunjungan (pesanan_id, kunjungan_id)
SELECT pesanan_id, id
FROM public.kunjungan_konsinyasi
WHERE pesanan_id IS NOT NULL
ON CONFLICT (kunjungan_id) DO NOTHING;


-- 3. Update fn_proses_kunjungan_konsinyasi
-- HAPUS blok auto-create tagihan/pesanan. Opname hanya mencatat kunjungan & stok,
-- tanpa menerbitkan faktur. Tagihan dibuat manual via fn_buat_tagihan_konsinyasi().
CREATE OR REPLACE FUNCTION public.fn_proses_kunjungan_konsinyasi(
    p_pelanggan_id UUID,
    p_sales_driver_id UUID,
    p_rincian JSONB,
    p_pengguna_id UUID DEFAULT NULL
)
RETURNS JSONB
LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
    v_kunjungan_id      UUID;
    v_nomor_kunjungan   VARCHAR(100);
    v_total_laku_netto  NUMERIC(15, 2) := 0.00;
    r_item              RECORD;
    v_stok_titip_lama   INT;
    v_laku              INT;
    v_subtotal          NUMERIC(15, 2);
    v_harga_info        JSONB;
    v_harga_deal        NUMERIC(15, 2);
    v_stok_rak_baru     INT;
    v_stok_gudang_lama  INT;
    v_stok_gudang_baru  INT;
    v_pengguna_id       UUID := p_pengguna_id;
    v_driver_id         UUID := p_sales_driver_id;
    v_selisih           INT := 0;
    v_harga_pokok       NUMERIC(15, 2) := 0.00;
    v_nilai_kerugian    NUMERIC(15, 2) := 0.00;
BEGIN
    v_nomor_kunjungan := 'KONSIN-' || TO_CHAR(NOW(), 'YYYYMMDD-HH24MISS');

    -- Pastikan user_id valid untuk audit trail
    IF v_pengguna_id IS NULL THEN
        SELECT id INTO v_pengguna_id FROM public.pengguna WHERE karyawan_id = p_sales_driver_id LIMIT 1;
        IF v_pengguna_id IS NULL THEN
            SELECT id INTO v_pengguna_id FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
        END IF;
    END IF;

    -- Validasi sales_driver_id
    IF v_driver_id IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM public.karyawan WHERE id = v_driver_id) THEN
            SELECT karyawan_id INTO v_driver_id FROM public.pengguna WHERE id = v_pengguna_id;
            IF v_driver_id IS NULL THEN
                SELECT id INTO v_driver_id FROM public.karyawan WHERE status_aktif = TRUE ORDER BY nama_karyawan ASC LIMIT 1;
            END IF;
        END IF;
    ELSE
        SELECT karyawan_id INTO v_driver_id FROM public.pengguna WHERE id = v_pengguna_id;
        IF v_driver_id IS NULL THEN
            SELECT id INTO v_driver_id FROM public.karyawan WHERE status_aktif = TRUE ORDER BY nama_karyawan ASC LIMIT 1;
        END IF;
    END IF;

    -- 1. Buat Header Kunjungan (pesanan_id = NULL, akan diisi saat tagihan dibuat manual)
    INSERT INTO public.kunjungan_konsinyasi (
        nomor_kunjungan, pelanggan_id, sales_driver_id, tanggal_kunjungan, total_laku_nominal, dibuat_oleh
    ) VALUES (
        v_nomor_kunjungan, p_pelanggan_id, v_driver_id, CURRENT_DATE, 0.00, v_pengguna_id
    ) RETURNING id INTO v_kunjungan_id;

    -- 2. Loop Rincian Barang
    FOR r_item IN 
        SELECT 
            (elem->>'item_id')::UUID AS item_id,
            COALESCE((elem->>'sisa_fisik_di_rak')::INT, 0) AS sisa_fisik_di_rak,
            COALESCE((elem->>'retur_bagus')::INT, 0) AS retur_bagus,
            COALESCE((elem->>'retur_rusak')::INT, 0) AS retur_rusak,
            COALESCE((elem->>'jumlah_laku')::INT, NULL) AS jumlah_laku_param,
            COALESCE((elem->>'selisih_qty')::INT, 0) AS selisih_qty
        FROM jsonb_array_elements(p_rincian) AS elem
    LOOP
        -- Ambil stok titip lama di toko ini
        SELECT COALESCE(stok_titip_saat_ini, 0) INTO v_stok_titip_lama
        FROM public.stok_konsinyasi_toko
        WHERE pelanggan_id = p_pelanggan_id AND item_id = r_item.item_id;

        IF v_stok_titip_lama IS NULL THEN
            v_stok_titip_lama := 0;
        END IF;

        -- Ambil snapshot HPP terkini untuk valuasi kerugian barang rusak
        SELECT COALESCE(harga_pokok_pembelian, 0.00) INTO v_harga_pokok
        FROM public.item WHERE id = r_item.item_id;

        -- Reset nilai kerugian rusak per item
        v_nilai_kerugian := 0.00;

        -- A. Jika ada retur bagus (ditarik balik ke gudang), kembalikan ke stok fisik gudang pusat
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

        -- B. Jika ada retur rusak, catat ke riwayat stok dan hitung kerugian HPP
        IF r_item.retur_rusak > 0 THEN
            v_nilai_kerugian := r_item.retur_rusak * v_harga_pokok;

            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
            ) VALUES (
                r_item.item_id, 'konsinyasi_retur_rusak', r_item.retur_rusak,
                0, 0, 'kunjungan_konsinyasi', v_kunjungan_id,
                'Retur barang rusak/BS ditarik dari rak konsinyasi toko', v_pengguna_id, NOW()
            );
        END IF;

        -- C. Hitung Qty Laku Terjual
        IF r_item.jumlah_laku_param IS NOT NULL THEN
            v_laku := GREATEST(0, r_item.jumlah_laku_param);
        ELSE
            v_laku := GREATEST(0, v_stok_titip_lama - (r_item.sisa_fisik_di_rak + r_item.retur_bagus + r_item.retur_rusak));
        END IF;

        v_stok_rak_baru := GREATEST(0, r_item.sisa_fisik_di_rak);
        v_selisih := r_item.selisih_qty;

        -- D. Hitung harga satuan deal toko
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

        -- E. Perbarui saldo stok di rak konsinyasi toko = sisa fisik aktual
        INSERT INTO public.stok_konsinyasi_toko (
            pelanggan_id, item_id, stok_titip_saat_ini, terakhir_opname_pada, dibuat_pada, diubah_pada
        ) VALUES (
            p_pelanggan_id, r_item.item_id, v_stok_rak_baru, NOW(), NOW(), NOW()
        )
        ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
            stok_titip_saat_ini = EXCLUDED.stok_titip_saat_ini,
            terakhir_opname_pada = NOW(),
            diubah_pada = NOW();

        -- F. Catat Rincian Kunjungan beserta valuasi kerugian rusak
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

    -- NOTE: Tidak ada lagi auto-create pesanan/tagihan di sini.
    -- Tagihan dibuat secara manual via fn_buat_tagihan_konsinyasi() dari halaman /consignment/tagihan

    RETURN jsonb_build_object(
        'success', true,
        'kunjungan_id', v_kunjungan_id,
        'nomor_kunjungan', v_nomor_kunjungan,
        'total_laku_netto', v_total_laku_netto,
        'pesanan_id', NULL,
        'nomor_nota', NULL
    );
END;
$$;


-- 4. Buat fn_buat_tagihan_konsinyasi — pembuatan tagihan manual dari 1 atau banyak kunjungan
CREATE OR REPLACE FUNCTION public.fn_buat_tagihan_konsinyasi(
    p_kunjungan_ids UUID[],
    p_pengguna_id   UUID DEFAULT NULL
)
RETURNS JSONB
LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
    v_pesanan_id    UUID;
    v_nomor_nota    VARCHAR(100);
    v_pelanggan_id  UUID;
    v_driver_id     UUID;
    v_total_netto   NUMERIC(15, 2) := 0.00;
    v_kid           UUID;
    v_count_toko    INT;
BEGIN
    -- Validasi: array kunjungan tidak boleh kosong
    IF array_length(p_kunjungan_ids, 1) IS NULL OR array_length(p_kunjungan_ids, 1) = 0 THEN
        RAISE EXCEPTION 'Tidak ada kunjungan yang dipilih untuk ditagih.';
    END IF;

    -- Validasi: semua kunjungan harus dari 1 toko yang sama
    SELECT COUNT(DISTINCT kk.pelanggan_id) INTO v_count_toko
    FROM public.kunjungan_konsinyasi kk
    WHERE kk.id = ANY(p_kunjungan_ids);

    IF v_count_toko > 1 THEN
        RAISE EXCEPTION 'Semua kunjungan yang ditagih harus berasal dari 1 toko yang sama.';
    END IF;

    IF v_count_toko = 0 THEN
        RAISE EXCEPTION 'Kunjungan yang dipilih tidak ditemukan di database.';
    END IF;

    -- Validasi: tidak ada kunjungan yang sudah masuk tagihan lain
    IF EXISTS (
        SELECT 1 FROM public.tagihan_kunjungan
        WHERE kunjungan_id = ANY(p_kunjungan_ids)
    ) THEN
        RAISE EXCEPTION 'Satu atau lebih kunjungan yang dipilih sudah termasuk dalam tagihan yang ada.';
    END IF;

    -- Validasi: semua kunjungan harus punya total_laku_nominal > 0
    IF EXISTS (
        SELECT 1 FROM public.kunjungan_konsinyasi
        WHERE id = ANY(p_kunjungan_ids) AND total_laku_nominal <= 0
    ) THEN
        RAISE EXCEPTION 'Kunjungan dengan total penjualan nihil tidak dapat ditagihkan.';
    END IF;

    -- Ambil pelanggan_id & sales_driver_id (semua kunjungan berasal dari 1 toko yang sama)
    SELECT kk.pelanggan_id, COALESCE(kk.sales_driver_id, p.sales_driver_id)
    INTO v_pelanggan_id, v_driver_id
    FROM public.kunjungan_konsinyasi kk
    LEFT JOIN public.pelanggan p ON kk.pelanggan_id = p.id
    WHERE kk.id = ANY(p_kunjungan_ids)
    ORDER BY (kk.sales_driver_id IS NOT NULL) DESC, kk.dibuat_pada DESC
    LIMIT 1;

    -- Ambil total nominal dari semua kunjungan terpilih
    SELECT COALESCE(SUM(kk.total_laku_nominal), 0.00)
    INTO v_total_netto
    FROM public.kunjungan_konsinyasi kk
    WHERE kk.id = ANY(p_kunjungan_ids);

    -- Resolusi pengguna_id jika null
    IF p_pengguna_id IS NULL THEN
        SELECT id INTO p_pengguna_id 
        FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
    END IF;

    -- Buat nomor nota unik
    v_nomor_nota := 'INV-KONSIN-' || TO_CHAR(NOW(), 'YYYYMMDD-HH24MISS');
    IF EXISTS (SELECT 1 FROM public.pesanan WHERE nomor_nota = v_nomor_nota) THEN
        v_nomor_nota := v_nomor_nota || '-' || LPAD(FLOOR(RANDOM() * 900 + 100)::TEXT, 3, '0');
    END IF;

    -- Insert pesanan (tagihan konsinyasi baru)
    INSERT INTO public.pesanan (
        nomor_nota, pelanggan_id, sales_driver_id,
        tanggal_pesanan, total_bruto, total_diskon, total_netto,
        tipe_pembayaran, status_pembayaran, status_pemrosesan,
        total_dibayar, sisa_tagihan, adalah_tagihan, catatan,
        dibuat_oleh, dibuat_pada, diubah_pada
    ) VALUES (
        v_nomor_nota, v_pelanggan_id, v_driver_id,
        CURRENT_DATE, v_total_netto, 0.00, v_total_netto,
        'konsinyasi', 'belum_lunas', 'selesai',
        0.00, v_total_netto, TRUE,
        'Tagihan Manual Konsinyasi: ' || array_length(p_kunjungan_ids, 1) || ' kunjungan',
        p_pengguna_id, NOW(), NOW()
    ) RETURNING id INTO v_pesanan_id;

    -- Insert item_pesanan: agregasi dari semua rincian kunjungan terpilih (group by item)
    INSERT INTO public.item_pesanan (
        pesanan_id, item_id,
        kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
        harga_satuan_deal, diskon_item_persen, diskon_item_nominal,
        is_bonus, subtotal, dibuat_pada
    )
    SELECT 
        v_pesanan_id,
        rkk.item_id,
        SUM(rkk.jumlah_laku_terjual),
        0,
        MAX(rkk.harga_satuan_deal),
        0.00, 0.00,
        FALSE,
        SUM(rkk.subtotal_laku),
        NOW()
    FROM public.rincian_kunjungan_konsinyasi rkk
    WHERE rkk.kunjungan_id = ANY(p_kunjungan_ids)
      AND rkk.jumlah_laku_terjual > 0
    GROUP BY rkk.item_id;

    -- Isi junction table tagihan_kunjungan dan update link di kunjungan
    FOREACH v_kid IN ARRAY p_kunjungan_ids LOOP
        INSERT INTO public.tagihan_kunjungan (pesanan_id, kunjungan_id)
        VALUES (v_pesanan_id, v_kid);

        -- Update pesanan_id di kunjungan untuk backward compat (riwayat kunjungan, dll)
        UPDATE public.kunjungan_konsinyasi 
        SET pesanan_id = v_pesanan_id
        WHERE id = v_kid;
    END LOOP;

    -- Update total piutang berjalan di master pelanggan
    UPDATE public.pelanggan 
    SET total_piutang_berjalan = COALESCE(total_piutang_berjalan, 0) + v_total_netto,
        diubah_pada = NOW()
    WHERE id = v_pelanggan_id;

    RETURN jsonb_build_object(
        'success', true,
        'pesanan_id', v_pesanan_id,
        'nomor_nota', v_nomor_nota,
        'total_tagihan', v_total_netto,
        'jumlah_kunjungan', array_length(p_kunjungan_ids, 1)
    );
END;
$$;

COMMENT ON FUNCTION public.fn_buat_tagihan_konsinyasi IS 
    'Membuat tagihan konsinyasi manual dari 1 atau lebih kunjungan (dari toko yang sama). 
     Tagihan dapat mencakup banyak kunjungan sekaligus dalam 1 faktur.
     Constraint UNIQUE pada tagihan_kunjungan.kunjungan_id mencegah double-billing.';

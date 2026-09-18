-- ============================================================
-- Migration 45: DROP kolom satuan_distribusi & konversi_distribusi_ke_dasar
--               dari public.item
--               DROP kolom kuantitas_satuan_distribusi dari public.item_pesanan
-- 
-- Alasan: Kolom tidak pernah dipakai secara bermakna.
--   - item.satuan_distribusi: 137 item, semua bernilai 'bal' (default), tidak pernah diubah
--   - item.konversi_distribusi_ke_dasar: semua bernilai 1 (default), tidak ada referensi di kode
--   - item_pesanan.kuantitas_satuan_distribusi: 104 baris transaksi, semua bernilai 0
-- 
-- CATATAN: satuan_distribusi & konversi_bal_ke_pcs di public.grup_produk
--          TIDAK disentuh karena masih aktif dipakai di POS dan pricing.
-- ============================================================

-- 1. Hapus kolom dari public.item
ALTER TABLE public.item DROP COLUMN IF EXISTS satuan_distribusi;
ALTER TABLE public.item DROP COLUMN IF EXISTS konversi_distribusi_ke_dasar;

-- 2. Hapus kolom dari public.item_pesanan
ALTER TABLE public.item_pesanan DROP COLUMN IF EXISTS kuantitas_satuan_distribusi;

-- 3. Perbarui stored procedure fn_cari_item_by_barcode
CREATE OR REPLACE FUNCTION public.fn_cari_item_by_barcode(p_barcode character varying)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_items JSONB;
BEGIN
    SELECT jsonb_agg(
        jsonb_build_object(
            'item_id', i.id,
            'kode_sku', i.kode_sku,
            'nama_item', i.nama_item,
            'grup_nama', gp.nama_grup,
            'stok_fisik', i.stok_fisik_saat_ini,
            'satuan_dasar', i.satuan_dasar
        )
    ) INTO v_items
    FROM public.item i
    JOIN public.grup_produk gp ON i.grup_id = gp.id
    WHERE gp.barcode_universal = p_barcode
      AND i.status_aktif = TRUE;

    IF v_items IS NULL THEN
        RETURN jsonb_build_object('ditemukan', false, 'pesan', 'Barcode produk tidak ditemukan');
    END IF;

    RETURN jsonb_build_object('ditemukan', true, 'total_varian', jsonb_array_length(v_items), 'data', v_items);
END;
$$;

-- 4. Perbarui stored procedure fn_buat_tagihan_konsinyasi
CREATE OR REPLACE FUNCTION public.fn_buat_tagihan_konsinyasi(p_kunjungan_ids uuid[], p_pengguna_id uuid DEFAULT NULL::uuid)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_pesanan_id    UUID;
    v_nomor_nota    VARCHAR(100);
    v_pelanggan_id  UUID;
    v_driver_id     UUID;
    v_total_netto   NUMERIC(15, 2) := 0.00;
    v_kid           UUID;
    v_count_toko    INT;
BEGIN
    IF array_length(p_kunjungan_ids, 1) IS NULL OR array_length(p_kunjungan_ids, 1) = 0 THEN
        RAISE EXCEPTION 'Tidak ada kunjungan yang dipilih untuk ditagih.';
    END IF;

    SELECT COUNT(DISTINCT kk.pelanggan_id) INTO v_count_toko
    FROM public.kunjungan_konsinyasi kk
    WHERE kk.id = ANY(p_kunjungan_ids);

    IF v_count_toko > 1 THEN
        RAISE EXCEPTION 'Semua kunjungan yang ditagih harus berasal dari 1 toko yang sama.';
    END IF;

    IF v_count_toko = 0 THEN
        RAISE EXCEPTION 'Kunjungan yang dipilih tidak ditemukan di database.';
    END IF;

    IF EXISTS (
        SELECT 1 FROM public.tagihan_kunjungan
        WHERE kunjungan_id = ANY(p_kunjungan_ids)
    ) THEN
        RAISE EXCEPTION 'Satu atau lebih kunjungan yang dipilih sudah termasuk dalam tagihan yang ada.';
    END IF;

    IF EXISTS (
        SELECT 1 FROM public.kunjungan_konsinyasi
        WHERE id = ANY(p_kunjungan_ids) AND total_laku_nominal <= 0
    ) THEN
        RAISE EXCEPTION 'Kunjungan dengan total penjualan nihil tidak dapat ditagihkan.';
    END IF;

    SELECT kk.pelanggan_id, COALESCE(kk.sales_driver_id, p.sales_driver_id)
    INTO v_pelanggan_id, v_driver_id
    FROM public.kunjungan_konsinyasi kk
    LEFT JOIN public.pelanggan p ON kk.pelanggan_id = p.id
    WHERE kk.id = ANY(p_kunjungan_ids)
    ORDER BY (kk.sales_driver_id IS NOT NULL) DESC, kk.dibuat_pada DESC
    LIMIT 1;

    SELECT COALESCE(SUM(kk.total_laku_nominal), 0.00)
    INTO v_total_netto
    FROM public.kunjungan_konsinyasi kk
    WHERE kk.id = ANY(p_kunjungan_ids);

    IF p_pengguna_id IS NULL THEN
        SELECT id INTO p_pengguna_id 
        FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
    END IF;

    v_nomor_nota := 'INV-KONSIN-' || TO_CHAR(NOW(), 'YYYYMMDD-HH24MISS');
    IF EXISTS (SELECT 1 FROM public.pesanan WHERE nomor_nota = v_nomor_nota) THEN
        v_nomor_nota := v_nomor_nota || '-' || LPAD(FLOOR(RANDOM() * 900 + 100)::TEXT, 3, '0');
    END IF;

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

    INSERT INTO public.item_pesanan (
        pesanan_id, item_id,
        kuantitas_satuan_dasar,
        harga_satuan_deal, diskon_item_persen, diskon_item_nominal,
        is_bonus, subtotal, dibuat_pada
    )
    SELECT 
        v_pesanan_id,
        rkk.item_id,
        SUM(rkk.jumlah_laku_terjual),
        MAX(rkk.harga_satuan_deal),
        0.00, 0.00,
        FALSE,
        SUM(rkk.subtotal_laku),
        NOW()
    FROM public.rincian_kunjungan_konsinyasi rkk
    WHERE rkk.kunjungan_id = ANY(p_kunjungan_ids)
      AND rkk.jumlah_laku_terjual > 0
    GROUP BY rkk.item_id;

    FOREACH v_kid IN ARRAY p_kunjungan_ids LOOP
        INSERT INTO public.tagihan_kunjungan (pesanan_id, kunjungan_id)
        VALUES (v_pesanan_id, v_kid);

        UPDATE public.kunjungan_konsinyasi 
        SET pesanan_id = v_pesanan_id
        WHERE id = v_kid;
    END LOOP;

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

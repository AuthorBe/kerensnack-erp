-- ==============================================================================
-- KEREN SNACK - STORED PROCEDURES (RPC), TRIGGERS & BUSINESS LOGIC ENGINE
-- Model: Custom AI ERP untuk Toko & Manufaktur Repacking KEREN Snack
-- Status: 100% Terverifikasi & Selaras Penuh dengan Live Database Supabase PostgreSQL
-- ==============================================================================

-- ==============================================================================
-- 1. MESIN HITUNG HARGA DINAMIS (DYNAMIC PRICING ENGINE)
-- ==============================================================================
-- Fungsi: fn_hitung_harga_jual_item
-- Menghitung harga jual per SKU berdasarkan grup pelanggan & matriks level harga
CREATE OR REPLACE FUNCTION public.fn_hitung_harga_jual_item(
    p_item_id UUID,
    p_pelanggan_id UUID
)
RETURNS JSONB AS $$
DECLARE
    v_grup_produk_id UUID;
    v_nama_item VARCHAR;
    v_level_harga INT;
    v_diskon_persen NUMERIC(5, 2);
    v_diskon_nominal NUMERIC(15, 2);
    v_harga_pcs_dasar NUMERIC(15, 2);
    v_harga_pcs_netto NUMERIC(15, 2);
    v_nama_grup_pelanggan VARCHAR;
BEGIN
    -- 1. Ambil grup_produk dan nama dari item
    SELECT grup_id, nama_item INTO v_grup_produk_id, v_nama_item FROM public.item WHERE id = p_item_id;

    -- 2. Ambil aturan harga dari grup_pelanggan yang terhubung
    IF p_pelanggan_id IS NOT NULL THEN
        SELECT 
            COALESCE(gp.default_level_harga, 1),
            COALESCE(gp.diskon_persen_default, 0.00),
            COALESCE(gp.diskon_nominal_default, 0.00),
            gp.nama_grup
        INTO v_level_harga, v_diskon_persen, v_diskon_nominal, v_nama_grup_pelanggan
        FROM public.pelanggan p
        JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
        WHERE p.id = p_pelanggan_id;
    END IF;

    -- Jika tidak ditemukan pelanggan / walk-in cash, default level 1 tanpa diskon
    IF v_level_harga IS NULL THEN
        v_level_harga := 1;
        v_diskon_persen := 0.00;
        v_diskon_nominal := 0.00;
        v_nama_grup_pelanggan := 'Umum';
    END IF;

    -- 3. Cari harga base di grup_produk_harga_level
    SELECT harga_jual_pcs
    INTO v_harga_pcs_dasar
    FROM public.grup_produk_harga_level
    WHERE grup_produk_id = v_grup_produk_id AND level_harga = v_level_harga;

    -- Pilihan B (Strict Rejection): Jika harga level belum diatur di /pricing, kembalikan status error eksplisit
    IF v_harga_pcs_dasar IS NULL THEN
        RETURN jsonb_build_object(
            'error', true,
            'code', 'PRICE_LEVEL_NOT_CONFIGURED',
            'message', format('Harga Level %s belum diatur untuk produk "%s" di /pricing.', v_level_harga, COALESCE(v_nama_item, 'Produk')),
            'level_harga', v_level_harga,
            'grup_pelanggan', v_nama_grup_pelanggan
        );
    END IF;

    -- 4. Hitung Diskon (% dan Nominal) dari grup pelanggan
    v_harga_pcs_netto := v_harga_pcs_dasar - (v_harga_pcs_dasar * (v_diskon_persen / 100.0)) - v_diskon_nominal;

    RETURN jsonb_build_object(
        'error', false,
        'level_harga', v_level_harga,
        'grup_pelanggan', v_nama_grup_pelanggan,
        'diskon_persen', v_diskon_persen,
        'diskon_nominal', v_diskon_nominal,
        'harga_pcs_bruto', v_harga_pcs_dasar,
        'harga_pcs_netto', GREATEST(0, v_harga_pcs_netto)
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ==============================================================================
-- 2. UNIVERSAL BARCODE DISAMBIGUATION (PENCARIAN VARIAN RASA PER KEMASAN)
-- ==============================================================================
-- Fungsi: fn_cari_item_by_barcode
-- Mencari semua SKU varian rasa yang menggunakan barcode kemasan bersama
CREATE OR REPLACE FUNCTION public.fn_cari_item_by_barcode(p_barcode character varying)
 RETURNS jsonb
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
DECLARE
    v_items JSONB;
BEGIN
    -- Cari item yang cocok dengan barcode spesifik atau barcode_universal dari grup produknya
    SELECT jsonb_agg(
        jsonb_build_object(
            'item_id', i.id,
            'kode_sku', i.kode_sku,
            'nama_item', i.nama_item,
            'varian_rasa', i.varian_rasa,
            'grup_nama', gp.nama_grup,
            'stok_fisik', i.stok_fisik_saat_ini,
            'satuan_dasar', i.satuan_dasar,
            'satuan_distribusi', i.satuan_distribusi
        )
    ) INTO v_items
    FROM public.item i
    LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
    WHERE (i.barcode = p_barcode OR gp.barcode_universal = p_barcode)
      AND i.status_aktif = TRUE;

    IF v_items IS NULL THEN
        RETURN jsonb_build_object('ditemukan', false, 'total_varian', 0, 'items', '[]'::jsonb);
    END IF;

    RETURN jsonb_build_object(
        'ditemukan', true,
        'total_varian', jsonb_array_length(v_items),
        'items', v_items
    );
END;
$function$;

-- ==============================================================================
-- 3. MESIN DISTRIBUSI KONSINYASI & AUDIT RAK TOKO
-- ==============================================================================
-- Fungsi: fn_proses_kunjungan_konsinyasi
-- Menangani opname fisik rak toko, menghitung barang laku, mutasi stok, dan valuasi kerugian
CREATE OR REPLACE FUNCTION public.fn_proses_kunjungan_konsinyasi(p_pelanggan_id uuid, p_sales_driver_id uuid, p_rincian jsonb, p_keterangan text DEFAULT NULL::text, p_foto_kunjungan text DEFAULT NULL::text, p_pengguna_id uuid DEFAULT NULL::uuid)
 RETURNS jsonb
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
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

        -- A. Proses Retur Bagus: kembalikan ke stok fisik gudang pusat dengan ROW LOCK (FOR UPDATE)
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
$function$;

-- Fungsi: fn_buat_tagihan_konsinyasi
-- Menerbitkan tagihan penjualan konsinyasi secara independen (decoupled dari opname)
CREATE OR REPLACE FUNCTION public.fn_buat_tagihan_konsinyasi(p_kunjungan_ids uuid[], p_pengguna_id uuid DEFAULT NULL::uuid)
 RETURNS jsonb
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
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
$function$;

-- Fungsi: fn_catat_pembayaran_konsinyasi
-- Mencatat pelunasan tagihan konsinyasi dan memperbarui buku kas penerimaan
CREATE OR REPLACE FUNCTION public.fn_catat_pembayaran_konsinyasi(p_pesanan_id uuid, p_akun_kas_id uuid, p_nominal_bayar numeric, p_dicatat_oleh uuid DEFAULT NULL::uuid, p_keterangan text DEFAULT NULL::text, p_tanggal_bayar date DEFAULT CURRENT_DATE)
 RETURNS jsonb
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
DECLARE
    v_pesanan RECORD;
    v_sisa_baru NUMERIC(15,2);
    v_status_baru VARCHAR(30);
    v_saldo_lama NUMERIC(15,2);
    v_saldo_baru NUMERIC(15,2);
    v_pengguna_id UUID := p_dicatat_oleh;
    v_tgl_transaksi DATE := COALESCE(p_tanggal_bayar, CURRENT_DATE);
    v_ket_kas TEXT;
BEGIN
    SELECT * INTO v_pesanan FROM public.pesanan WHERE id = p_pesanan_id FOR UPDATE;

    IF v_pesanan IS NULL THEN
        RAISE EXCEPTION 'Pesanan % tidak ditemukan', p_pesanan_id;
    END IF;

    IF v_pesanan.adalah_tagihan = FALSE THEN
        RAISE EXCEPTION 'Pesanan ini adalah dokumen pengiriman/titip, bukan tagihan. Tidak bisa dicatat pembayarannya.';
    END IF;

    IF v_pesanan.status_pembayaran = 'dibatalkan' THEN
        RAISE EXCEPTION 'Tagihan % telah dibatalkan. Pembayaran tidak dapat diproses.', v_pesanan.nomor_nota;
    END IF;

    IF v_pesanan.status_pembayaran = 'lunas' OR v_pesanan.sisa_tagihan <= 0 THEN
        RAISE EXCEPTION 'Tagihan % sudah berstatus LUNAS. Tidak ada sisa piutang.', v_pesanan.nomor_nota;
    END IF;

    IF p_nominal_bayar <= 0 THEN
        RAISE EXCEPTION 'Nominal bayar harus lebih dari 0';
    END IF;

    IF p_nominal_bayar > v_pesanan.sisa_tagihan THEN
        RAISE EXCEPTION 'Nominal bayar (%) melebihi sisa tagihan (%)', p_nominal_bayar, v_pesanan.sisa_tagihan;
    END IF;

    -- Validasi pengguna yang mencatat (hindari foreign key violation pada arus_kas)
    IF v_pengguna_id IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM public.pengguna WHERE id = v_pengguna_id) THEN
            SELECT pengguna_id INTO v_pengguna_id FROM public.karyawan WHERE id = p_dicatat_oleh LIMIT 1;
        END IF;
    END IF;
    IF v_pengguna_id IS NULL THEN
        SELECT id INTO v_pengguna_id FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
    END IF;

    v_sisa_baru := GREATEST(0, v_pesanan.sisa_tagihan - p_nominal_bayar);
    v_status_baru := CASE WHEN v_sisa_baru <= 0 THEN 'lunas' ELSE 'sebagian' END;

    UPDATE public.pesanan
    SET total_dibayar = COALESCE(total_dibayar, 0) + p_nominal_bayar,
        sisa_tagihan = v_sisa_baru,
        status_pembayaran = v_status_baru,
        akun_kas_id = COALESCE(akun_kas_id, p_akun_kas_id),
        diubah_pada = NOW()
    WHERE id = p_pesanan_id;

    UPDATE public.pelanggan
    SET total_piutang_berjalan = GREATEST(0, COALESCE(total_piutang_berjalan, 0) - p_nominal_bayar),
        diubah_pada = NOW()
    WHERE id = v_pesanan.pelanggan_id;

    SELECT saldo_saat_ini INTO v_saldo_lama FROM public.akun_kas WHERE id = p_akun_kas_id FOR UPDATE;
    IF v_saldo_lama IS NULL THEN
        RAISE EXCEPTION 'Akun kas % tidak ditemukan/tidak aktif', p_akun_kas_id;
    END IF;
    v_saldo_baru := v_saldo_lama + p_nominal_bayar;

    UPDATE public.akun_kas
    SET saldo_saat_ini = v_saldo_baru, diubah_pada = NOW()
    WHERE id = p_akun_kas_id;

    v_ket_kas := 'Pembayaran Nota ' || v_pesanan.nomor_nota;
    IF p_keterangan IS NOT NULL AND TRIM(p_keterangan) != '' THEN
        v_ket_kas := v_ket_kas || ' - ' || TRIM(p_keterangan);
    END IF;

    INSERT INTO public.arus_kas (
        akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
        referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
    ) VALUES (
        p_akun_kas_id, v_tgl_transaksi, 'masuk', 'penjualan', p_nominal_bayar,
        v_ket_kas, 'pesanan', p_pesanan_id, v_saldo_baru, v_pengguna_id, NOW()
    );

    RETURN jsonb_build_object(
        'success', true,
        'pesanan_id', p_pesanan_id,
        'nomor_nota', v_pesanan.nomor_nota,
        'total_dibayar', COALESCE(v_pesanan.total_dibayar, 0) + p_nominal_bayar,
        'sisa_tagihan', v_sisa_baru,
        'status_pembayaran', v_status_baru,
        'tanggal_transaksi', v_tgl_transaksi
    );
END;
$function$;

-- Fungsi: fn_rekonsiliasi_piutang_pelanggan
-- Merekonsiliasi dan menyelaraskan saldo piutang berjalan pelanggan
CREATE OR REPLACE FUNCTION public.fn_rekonsiliasi_piutang_pelanggan(p_pelanggan_id uuid DEFAULT NULL::uuid)
 RETURNS jsonb
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
DECLARE
    v_updated_count INT := 0;
    v_total_piutang_baru NUMERIC(15, 2) := 0;
BEGIN
    IF p_pelanggan_id IS NOT NULL THEN
        -- Rekonsiliasi untuk 1 Pelanggan Tertentu
        UPDATE public.pelanggan p
        SET total_piutang_berjalan = COALESCE((
            SELECT SUM(pes.sisa_tagihan)
            FROM public.pesanan pes
            WHERE pes.pelanggan_id = p.id
              AND pes.adalah_tagihan = TRUE
              AND pes.status_pemrosesan IN ('selesai_dikirim', 'selesai', 'selesai_diterima')
              AND pes.status_pemrosesan != 'dibatalkan'
              AND pes.status_pembayaran != 'lunas'
              AND pes.sisa_tagihan > 0
        ), 0),
        diubah_pada = NOW()
        WHERE p.id = p_pelanggan_id;

        GET DIAGNOSTICS v_updated_count = ROW_COUNT;

        SELECT total_piutang_berjalan INTO v_total_piutang_baru
        FROM public.pelanggan WHERE id = p_pelanggan_id;

        RETURN jsonb_build_object(
            'success', true,
            'mode', 'single',
            'pelanggan_id', p_pelanggan_id,
            'total_piutang_berjalan', v_total_piutang_baru
        );
    ELSE
        -- Rekonsiliasi Menyeluruh untuk Seluruh Pelanggan Aktif Maupun Nonaktif
        UPDATE public.pelanggan p
        SET total_piutang_berjalan = COALESCE((
            SELECT SUM(pes.sisa_tagihan)
            FROM public.pesanan pes
            WHERE pes.pelanggan_id = p.id
              AND pes.adalah_tagihan = TRUE
              AND pes.status_pemrosesan IN ('selesai_dikirim', 'selesai', 'selesai_diterima')
              AND pes.status_pemrosesan != 'dibatalkan'
              AND pes.status_pembayaran != 'lunas'
              AND pes.sisa_tagihan > 0
        ), 0),
        diubah_pada = NOW();

        GET DIAGNOSTICS v_updated_count = ROW_COUNT;

        SELECT COALESCE(SUM(total_piutang_berjalan), 0) INTO v_total_piutang_baru
        FROM public.pelanggan;

        RETURN jsonb_build_object(
            'success', true,
            'mode', 'all',
            'pelanggan_terupdate', v_updated_count,
            'total_piutang_nasional', v_total_piutang_baru
        );
    END IF;
END;
$function$;

-- ==============================================================================
-- 4. DATABASE TRIGGERS (AUTOMATION & INTEGRITY)
-- ==============================================================================

-- A. Trigger Pengiriman Konsinyasi (Surat Jalan Selesai Diterima -> Tambah Stok Titip Toko)
CREATE OR REPLACE FUNCTION public.fn_trg_proses_pengiriman_konsinyasi()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
        DECLARE
            v_pesanan RECORD;
            r_item RECORD;
            v_stok_gudang_lama INT;
            v_stok_gudang_baru INT;
            v_user_id UUID := NULL;
        BEGIN
            IF NEW.status_surat_jalan = 'selesai_diterima'
               AND (OLD.status_surat_jalan IS DISTINCT FROM 'selesai_diterima') THEN

                SELECT * INTO v_pesanan FROM public.pesanan WHERE id = NEW.pesanan_id;

                IF v_pesanan.tipe_pembayaran = 'konsinyasi' AND v_pesanan.adalah_tagihan = FALSE THEN
                    v_user_id := v_pesanan.dibuat_oleh;

                    FOR r_item IN
                        SELECT item_id, kuantitas_satuan_dasar
                        FROM public.item_pesanan
                        WHERE pesanan_id = NEW.pesanan_id
                    LOOP
                        SELECT stok_fisik_saat_ini INTO v_stok_gudang_lama
                        FROM public.item WHERE id = r_item.item_id;

                        -- Validasi stok cukup
                        IF v_stok_gudang_lama < r_item.kuantitas_satuan_dasar THEN
                            RAISE EXCEPTION 'Stok gudang item % tidak cukup: tersedia %, diminta %',
                                r_item.item_id, v_stok_gudang_lama, r_item.kuantitas_satuan_dasar;
                        END IF;

                        v_stok_gudang_baru := v_stok_gudang_lama - r_item.kuantitas_satuan_dasar;

                        UPDATE public.item
                        SET stok_fisik_saat_ini = v_stok_gudang_baru, diubah_pada = NOW()
                        WHERE id = r_item.item_id;

                        INSERT INTO public.riwayat_stok (
                            item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                            referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
                        ) VALUES (
                            r_item.item_id, 'konsinyasi_keluar', r_item.kuantitas_satuan_dasar,
                            v_stok_gudang_lama, v_stok_gudang_baru, 'surat_jalan', NEW.id,
                            'Titip barang konsinyasi ke toko: ' || NEW.nomor_surat_jalan, v_user_id, NOW()
                        );

                        INSERT INTO public.stok_konsinyasi_toko (
                            pelanggan_id, item_id, stok_titip_saat_ini, dibuat_pada, diubah_pada
                        ) VALUES (
                            v_pesanan.pelanggan_id, r_item.item_id, r_item.kuantitas_satuan_dasar, NOW(), NOW()
                        )
                        ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
                            stok_titip_saat_ini = stok_konsinyasi_toko.stok_titip_saat_ini + EXCLUDED.stok_titip_saat_ini,
                            diubah_pada = NOW();
                    END LOOP;

                    -- Sinkronkan status pemrosesan pesanan ke selesai
                    UPDATE public.pesanan
                    SET status_pemrosesan = 'selesai', diubah_pada = NOW()
                    WHERE id = NEW.pesanan_id;
                END IF;
            END IF;
            RETURN NEW;
        END;
        $function$;

DROP TRIGGER IF EXISTS trg_proses_pengiriman_konsinyasi ON public.surat_jalan;
CREATE TRIGGER trg_proses_pengiriman_konsinyasi
AFTER UPDATE ON public.surat_jalan
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_proses_pengiriman_konsinyasi();

-- B. Trigger Produksi Harian -> Tambah Stok Barang Jadi
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
        SELECT ki.item_bahan_id, ki.jumlah_kebutuhan, ib.nama_item, ib.satuan_dasar
        FROM public.komposisi_item ki
        JOIN public.item ib ON ki.item_bahan_id = ib.id
        WHERE ki.item_jadi_id = NEW.item_id
    LOOP
        v_pemakaian_bahan := ROUND((v_total_pcs * r_bom.jumlah_kebutuhan)::NUMERIC, 4);

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

DROP TRIGGER IF EXISTS trg_produksi_harian_after_insert ON public.produksi_harian;
CREATE TRIGGER trg_produksi_harian_after_insert
AFTER INSERT ON public.produksi_harian
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_produksi_harian_after_insert();

-- Trigger AFTER UPDATE pada produksi_harian (Koreksi Kuantitas & Penyesuaian Bahan)
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

-- Trigger AFTER DELETE pada produksi_harian (Pembatalan Produksi & Revert Stok)
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

-- C. Trigger Persetujuan Biaya Operasional (Draf Pengeluaran -> Catat Arus Kas Keluar)
CREATE OR REPLACE FUNCTION public.fn_trg_draf_pengeluaran_approval()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
DECLARE
    v_akun_id UUID;
    v_saldo_lama NUMERIC;
    v_saldo_baru NUMERIC;
BEGIN
    IF (OLD.status_approval IS DISTINCT FROM NEW.status_approval) AND (NEW.status_approval = 'disetujui') THEN
        v_akun_id := NEW.akun_kas_id;
        IF v_akun_id IS NULL THEN
            SELECT id INTO v_akun_id FROM public.akun_kas WHERE status_aktif = TRUE LIMIT 1;
        END IF;

        SELECT saldo_saat_ini INTO v_saldo_lama FROM public.akun_kas WHERE id = v_akun_id;
        v_saldo_baru := COALESCE(v_saldo_lama, 0) - NEW.nominal;

        UPDATE public.akun_kas 
        SET saldo_saat_ini = v_saldo_baru, diubah_pada = NOW()
        WHERE id = v_akun_id;

        INSERT INTO public.arus_kas (
            akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
            keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
        ) VALUES (
            v_akun_id, CURRENT_DATE, 'keluar', 'beban_operasional', NEW.nominal,
            'Pengeluaran disetujui: ' || NEW.keterangan_mentah, 'draf_pengeluaran', NEW.id,
            v_saldo_baru, NEW.diajukan_oleh_pengguna_id, NOW()
        );
    END IF;

    RETURN NEW;
END;
$function$;

DROP TRIGGER IF EXISTS trg_draf_pengeluaran_approval ON public.draf_pengeluaran;
CREATE TRIGGER trg_draf_pengeluaran_approval
AFTER UPDATE ON public.draf_pengeluaran
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_draf_pengeluaran_approval();

-- D. Trigger Potongan Kasbon -> Update Saldo Kasbon
CREATE OR REPLACE FUNCTION public.fn_trg_potongan_kasbon_update_saldo()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
DECLARE
    v_sisa_baru NUMERIC;
BEGIN
    SELECT (sisa_pinjaman - NEW.nominal) INTO v_sisa_baru 
    FROM public.kasbon 
    WHERE id = NEW.kasbon_id;

    UPDATE public.kasbon 
    SET sisa_pinjaman = GREATEST(0, v_sisa_baru),
        status_kasbon = CASE WHEN v_sisa_baru <= 0 THEN 'lunas' ELSE 'aktif' END,
        diubah_pada = NOW()
    WHERE id = NEW.kasbon_id;

    RETURN NEW;
END;
$function$;

DROP TRIGGER IF EXISTS trg_potongan_kasbon_update_saldo ON public.potongan_kasbon;
CREATE TRIGGER trg_potongan_kasbon_update_saldo
AFTER INSERT ON public.potongan_kasbon
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_potongan_kasbon_update_saldo();

-- E. Trigger Mutasi Tabungan -> Update Saldo Tabungan
CREATE OR REPLACE FUNCTION public.fn_trg_transaksi_tabungan_update_saldo()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
BEGIN
    IF NEW.tipe = 'deposit' THEN
        UPDATE public.tabungan 
        SET saldo = saldo + NEW.jumlah, diubah_pada = NOW()
        WHERE id = NEW.tabungan_id;
    ELSIF NEW.tipe = 'withdrawal' THEN
        UPDATE public.tabungan 
        SET saldo = GREATEST(0, saldo - NEW.jumlah), diubah_pada = NOW()
        WHERE id = NEW.tabungan_id;
    END IF;

    RETURN NEW;
END;
$function$;

DROP TRIGGER IF EXISTS trg_transaksi_tabungan_update_saldo ON public.transaksi_tabungan;
CREATE TRIGGER trg_transaksi_tabungan_update_saldo
AFTER INSERT ON public.transaksi_tabungan
FOR EACH ROW
EXECUTE FUNCTION public.fn_trg_transaksi_tabungan_update_saldo();

-- F. Trigger Proteksi Akun Developer Root Sistem
CREATE OR REPLACE FUNCTION public.fn_guard_developer_account()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
DECLARE
    v_dev_role_id UUID;
    v_dev_count INT;
BEGIN
    -- Ambil ID peran developer
    SELECT id INTO v_dev_role_id FROM public.peran WHERE nama_peran = 'developer' LIMIT 1;

    -- 1. PROTEKSI DELETE: Jangan pernah izinkan penghapusan akun developer
    IF TG_OP = 'DELETE' THEN
        IF OLD.peran_id = v_dev_role_id THEN
            RAISE EXCEPTION 'Akses Ditolak: Akun Developer dilindungi secara permanen dan tidak dapat dihapus dari database.';
        END IF;
        RETURN OLD;
    END IF;

    -- 2. PROTEKSI INSERT: Hanya izinkan maksimal 1 akun developer di seluruh sistem
    IF TG_OP = 'INSERT' THEN
        IF NEW.peran_id = v_dev_role_id THEN
            SELECT count(*) INTO v_dev_count FROM public.pengguna WHERE peran_id = v_dev_role_id;
            IF v_dev_count >= 1 THEN
                RAISE EXCEPTION 'Akses Ditolak: Sistem dibatasi hanya untuk tepat 1 akun Developer.';
            END IF;
            NEW.status_aktif := TRUE; -- Developer selalu aktif
        END IF;
        RETURN NEW;
    END IF;

    -- 3. PROTEKSI UPDATE:
    IF TG_OP = 'UPDATE' THEN
        -- Jika target adalah akun developer saat ini
        IF OLD.peran_id = v_dev_role_id THEN
            -- Role developer tidak bisa diubah
            IF NEW.peran_id != v_dev_role_id THEN
                RAISE EXCEPTION 'Akses Ditolak: Role Developer bersifat permanen dan tidak dapat diubah ke role lain.';
            END IF;
            -- Akun developer tidak bisa dinonaktifkan
            IF NEW.status_aktif IS NOT TRUE THEN
                RAISE EXCEPTION 'Akses Ditolak: Akun Developer adalah root sistem dan tidak dapat dinonaktifkan.';
            END IF;
        ELSE
            -- Jika user biasa ingin diubah menjadi developer
            IF NEW.peran_id = v_dev_role_id THEN
                RAISE EXCEPTION 'Akses Ditolak: Tidak diperbolehkan menetapkan peran Developer ke akun pengguna lain.';
            END IF;
        END IF;
        RETURN NEW;
    END IF;

    RETURN NEW;
END;
$function$;

DROP TRIGGER IF EXISTS trg_guard_developer_account ON public.pengguna;
CREATE TRIGGER trg_guard_developer_account
BEFORE INSERT OR UPDATE OR DELETE ON public.pengguna
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_developer_account();

-- G. Stored Procedure Master Audit Log
CREATE OR REPLACE FUNCTION public.fn_catat_log_aktivitas(p_pengguna_id uuid DEFAULT NULL::uuid, p_nama_aktor character varying DEFAULT 'Sistem Otomasi n8n'::character varying, p_peran_aktor character varying DEFAULT 'ai_n8n'::character varying, p_sumber_aksi character varying DEFAULT 'n8n_automation'::character varying, p_kategori_aktivitas character varying DEFAULT 'ai_interaction'::character varying, p_jenis_aksi character varying DEFAULT 'EXECUTE'::character varying, p_tabel_terdampak character varying DEFAULT NULL::character varying, p_id_referensi uuid DEFAULT NULL::uuid, p_deskripsi_aktivitas text DEFAULT ''::text, p_data_sebelum jsonb DEFAULT NULL::jsonb, p_data_sesudah jsonb DEFAULT NULL::jsonb, p_id_pesan_telegram bigint DEFAULT NULL::bigint)
 RETURNS uuid
 LANGUAGE plpgsql
 SECURITY DEFINER
AS $function$
DECLARE
    v_log_id UUID;
BEGIN
    INSERT INTO public.log_aktivitas (
        pengguna_id, nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas,
        jenis_aksi, tabel_terdampak, id_referensi, deskripsi_aktivitas,
        data_sebelum, data_sesudah, id_pesan_telegram, waktu_kejadian
    ) VALUES (
        p_pengguna_id, p_nama_aktor, p_peran_aktor, p_sumber_aksi, p_kategori_aktivitas,
        p_jenis_aksi, p_tabel_terdampak, p_id_referensi, p_deskripsi_aktivitas,
        p_data_sebelum, p_data_sesudah, p_id_pesan_telegram, NOW()
    ) RETURNING id INTO v_log_id;

    RETURN v_log_id;
END;
$function$;

-- H. Trigger Integritas Pemisahan Sales vs Driver
-- 1. Toko Binaan (pelanggan.sales_driver_id) HANYA boleh dipegang oleh posisi 'sales'
CREATE OR REPLACE FUNCTION public.fn_guard_pelanggan_sales_driver()
RETURNS TRIGGER AS $$
DECLARE
    v_posisi VARCHAR(50);
    v_nama   VARCHAR(150);
BEGIN
    IF NEW.sales_driver_id IS NOT NULL THEN
        SELECT p.posisi, p.nama_lengkap INTO v_posisi, v_nama
        FROM public.karyawan k
        JOIN public.pengguna p ON k.pengguna_id = p.id
        WHERE k.id = NEW.sales_driver_id;

        IF LOWER(COALESCE(v_posisi, '')) = 'driver' THEN
            RAISE EXCEPTION 'Driver (%) tidak dapat ditugaskan sebagai Penanggung Jawab Toko Binaan! Posisi karyawan harus Sales.', v_nama;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_guard_pelanggan_sales_driver ON public.pelanggan;
CREATE TRIGGER trg_guard_pelanggan_sales_driver
BEFORE INSERT OR UPDATE OF sales_driver_id ON public.pelanggan
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_pelanggan_sales_driver();

-- 2. Fungsi Helper PostgreSQL untuk Perhitungan Tier Komisi Sales Bertingkat
CREATE OR REPLACE FUNCTION public.fn_hitung_tier_komisi_sales(p_omzet NUMERIC)
RETURNS JSONB
LANGUAGE plpgsql
STABLE
AS $$
DECLARE
    v_tier RECORD;
    v_next_tier RECORD;
    v_nominal_komisi NUMERIC(15, 2) := 0.00;
    v_gap_omzet NUMERIC(15, 2) := 0.00;
    v_omzet_bersih NUMERIC(15, 2) := GREATEST(0.00, COALESCE(p_omzet, 0.00));
BEGIN
    -- Cari tier yang sesuai dengan rentang omzet
    SELECT * INTO v_tier
    FROM public.skema_komisi_sales
    WHERE status_aktif = TRUE
      AND v_omzet_bersih >= omzet_min
      AND (omzet_maks IS NULL OR v_omzet_bersih <= omzet_maks)
    ORDER BY urutan DESC
    LIMIT 1;

    -- Jika omzet di bawah tier 1 minimum, gunakan tier dengan urutan pertama tetapi komisi 0 jika di bawah min
    IF NOT FOUND OR v_tier.id IS NULL THEN
        SELECT * INTO v_tier
        FROM public.skema_komisi_sales
        WHERE status_aktif = TRUE
        ORDER BY urutan ASC
        LIMIT 1;
        
        IF FOUND AND v_tier.id IS NOT NULL AND v_omzet_bersih < v_tier.omzet_min THEN
            -- Omzet belum memenuhi tier dasar
            v_nominal_komisi := 0.00;
            v_gap_omzet := v_tier.omzet_min - v_omzet_bersih;
            RETURN jsonb_build_object(
                'matched', false,
                'tier_id', NULL,
                'nama_tier', 'Di Bawah Tier Minimum',
                'urutan', 0,
                'omzet_min', 0.00,
                'omzet_maks', v_tier.omzet_min,
                'persentase', 0.00,
                'total_omzet', v_omzet_bersih,
                'nominal_komisi', 0.00,
                'has_next_tier', true,
                'next_tier_nama', v_tier.nama_tier,
                'next_tier_persentase', v_tier.persentase,
                'gap_omzet_ke_next_tier', v_gap_omzet
            );
        END IF;
    END IF;

    -- Jika tidak ada data tier sama sekali di tabel
    IF v_tier.id IS NULL THEN
        RETURN jsonb_build_object(
            'matched', false,
            'tier_id', NULL,
            'nama_tier', 'Skema Belum Dikonfigurasi',
            'urutan', 0,
            'omzet_min', 0.00,
            'omzet_maks', NULL,
            'persentase', 0.00,
            'total_omzet', v_omzet_bersih,
            'nominal_komisi', 0.00,
            'has_next_tier', false,
            'next_tier_nama', NULL,
            'next_tier_persentase', 0.00,
            'gap_omzet_ke_next_tier', 0.00
        );
    END IF;

    -- Hitung komisi flat retroaktif terhadap total omzet
    v_nominal_komisi := ROUND((v_omzet_bersih * v_tier.persentase / 100.0), 2);

    -- Cari tier berikutnya untuk motivasi sales
    SELECT * INTO v_next_tier
    FROM public.skema_komisi_sales
    WHERE status_aktif = TRUE
      AND urutan > v_tier.urutan
    ORDER BY urutan ASC
    LIMIT 1;

    IF FOUND AND v_next_tier.id IS NOT NULL THEN
        v_gap_omzet := GREATEST(0.00, v_next_tier.omzet_min - v_omzet_bersih);
    ELSE
        v_gap_omzet := 0.00;
    END IF;

    RETURN jsonb_build_object(
        'matched', true,
        'tier_id', v_tier.id,
        'nama_tier', v_tier.nama_tier,
        'urutan', v_tier.urutan,
        'omzet_min', v_tier.omzet_min,
        'omzet_maks', v_tier.omzet_maks,
        'persentase', v_tier.persentase,
        'total_omzet', v_omzet_bersih,
        'nominal_komisi', v_nominal_komisi,
        'has_next_tier', (FOUND AND v_next_tier.id IS NOT NULL),
        'next_tier_nama', CASE WHEN FOUND AND v_next_tier.id IS NOT NULL THEN v_next_tier.nama_tier ELSE NULL END,
        'next_tier_persentase', CASE WHEN FOUND AND v_next_tier.id IS NOT NULL THEN v_next_tier.persentase ELSE 0.00 END,
        'gap_omzet_ke_next_tier', v_gap_omzet
    );
END;
$$;

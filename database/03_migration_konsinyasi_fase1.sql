-- ==============================================================================
-- 03_migration_konsinyasi_fase1.sql
-- Migrasi Database Fase 1: Penyempurnaan Fitur Konsinyasi Rak Toko (KEREN SNACK)
-- ==============================================================================

-- 1. Fix CHECK constraint riwayat_stok (izinkan 'konsinyasi_retur_rusak')
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
    'konsinyasi_retur_rusak'::character varying
])::text[])));

-- 2. Tambah kolom pelanggan.sales_driver_id (relasi sales pemegang toko tetap)
ALTER TABLE public.pelanggan
    ADD COLUMN IF NOT EXISTS sales_driver_id uuid REFERENCES public.karyawan(id);
CREATE INDEX IF NOT EXISTS idx_pelanggan_sales_driver ON public.pelanggan(sales_driver_id);

-- 3. Tambah kolom pesanan.adalah_tagihan (pembeda dokumen kirim vs tagihan asli)
ALTER TABLE public.pesanan
    ADD COLUMN IF NOT EXISTS adalah_tagihan boolean DEFAULT true NOT NULL;

-- 4. Update constraint pesanan_status_pembayaran_check (tambah 'sebagian')
ALTER TABLE public.pesanan DROP CONSTRAINT IF EXISTS pesanan_status_pembayaran_check;
ALTER TABLE public.pesanan ADD CONSTRAINT pesanan_status_pembayaran_check
CHECK (((status_pembayaran)::text = ANY ((ARRAY[
    'belum_lunas'::character varying,
    'sebagian'::character varying,
    'tempo'::character varying,
    'lunas'::character varying,
    'dibatalkan'::character varying
])::text[])));

-- 5. Update constraint surat_jalan_status_surat_jalan_check (tambah 'ditolak_owner')
ALTER TABLE public.surat_jalan DROP CONSTRAINT IF EXISTS surat_jalan_status_surat_jalan_check;
ALTER TABLE public.surat_jalan ADD CONSTRAINT surat_jalan_status_surat_jalan_check
CHECK (((status_surat_jalan)::text = ANY ((ARRAY[
    'draf_n8n'::character varying,
    'disetujui_owner'::character varying,
    'sedang_dikirim'::character varying,
    'selesai_diterima'::character varying,
    'gagal_kembali'::character varying,
    'ditolak_owner'::character varying
])::text[])));

-- 6. Tambah kolom kerugian barang rusak di rincian_kunjungan_konsinyasi
ALTER TABLE public.rincian_kunjungan_konsinyasi
    ADD COLUMN IF NOT EXISTS harga_pokok_satuan numeric(15,2) DEFAULT 0.00 NOT NULL,
    ADD COLUMN IF NOT EXISTS nilai_kerugian_rusak numeric(15,2) DEFAULT 0.00 NOT NULL;

-- 7. Update fungsi fn_proses_kunjungan_konsinyasi (Lengkap & Teruji)
CREATE OR REPLACE FUNCTION public.fn_proses_kunjungan_konsinyasi(
    p_pelanggan_id UUID,
    p_sales_driver_id UUID,
    p_rincian JSONB,
    p_pengguna_id UUID DEFAULT NULL
)
RETURNS JSONB
LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
    v_kunjungan_id UUID;
    v_nomor_kunjungan VARCHAR(100);
    v_pesanan_id UUID := NULL;
    v_nomor_nota VARCHAR(100) := NULL;
    v_total_laku_netto NUMERIC(15, 2) := 0.00;
    r_item RECORD;
    v_stok_titip_lama INT;
    v_laku INT;
    v_subtotal NUMERIC(15, 2);
    v_harga_info JSONB;
    v_harga_deal NUMERIC(15, 2);
    v_stok_rak_baru INT;
    v_stok_gudang_lama INT;
    v_stok_gudang_baru INT;
    v_pengguna_id UUID := p_pengguna_id;
    v_driver_id UUID := p_sales_driver_id;
    v_selisih INT := 0;
    v_harga_pokok NUMERIC(15, 2) := 0.00;
    v_nilai_kerugian NUMERIC(15, 2) := 0.00;
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

    -- 1. Buat Header Kunjungan
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

    -- 4. Jika ada barang laku > 0, generate Faktur Penjualan Konsinyasi Otomatis (adalah_tagihan = true)
    IF v_total_laku_netto > 0 THEN
        v_nomor_nota := 'INV-KONSIN-' || TO_CHAR(NOW(), 'YYYYMMDD-HH24MISS');

        INSERT INTO public.pesanan (
            nomor_nota, pelanggan_id, sales_driver_id,
            tanggal_pesanan, total_bruto, total_diskon, total_netto,
            tipe_pembayaran, status_pembayaran, status_pemrosesan,
            total_dibayar, sisa_tagihan, adalah_tagihan, catatan,
            dibuat_oleh, dibuat_pada, diubah_pada
        ) VALUES (
            v_nomor_nota, p_pelanggan_id, v_driver_id,
            CURRENT_DATE, v_total_laku_netto, 0.00, v_total_laku_netto,
            'konsinyasi', 'belum_lunas', 'selesai',
            0.00, v_total_laku_netto, TRUE, 'Hasil Kunjungan & Opname Rak Konsinyasi: ' || v_nomor_kunjungan,
            v_pengguna_id, NOW(), NOW()
        ) RETURNING id INTO v_pesanan_id;

        -- Insert detail item pesanan laku
        INSERT INTO public.item_pesanan (
            pesanan_id, item_id,
            kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
            harga_satuan_deal, diskon_item_persen, diskon_item_nominal,
            is_bonus, subtotal, dibuat_pada
        )
        SELECT 
            v_pesanan_id, rkk.item_id,
            rkk.jumlah_laku_terjual, 0,
            rkk.harga_satuan_deal, 0.00, 0.00,
            FALSE, rkk.subtotal_laku, NOW()
        FROM public.rincian_kunjungan_konsinyasi rkk
        WHERE rkk.kunjungan_id = v_kunjungan_id AND rkk.jumlah_laku_terjual > 0;

        -- Link pesanan ke kunjungan konsinyasi
        UPDATE public.kunjungan_konsinyasi 
        SET pesanan_id = v_pesanan_id 
        WHERE id = v_kunjungan_id;

        -- Update total piutang berjalan di master pelanggan
        UPDATE public.pelanggan 
        SET total_piutang_berjalan = COALESCE(total_piutang_berjalan, 0) + v_total_laku_netto,
            diubah_pada = NOW()
        WHERE id = p_pelanggan_id;
    END IF;

    RETURN jsonb_build_object(
        'success', true,
        'kunjungan_id', v_kunjungan_id,
        'nomor_kunjungan', v_nomor_kunjungan,
        'total_laku_netto', v_total_laku_netto,
        'pesanan_id', v_pesanan_id,
        'nomor_nota', v_nomor_nota
    );
END;
$$;

-- 8. Buat fungsi fn_catat_pembayaran_konsinyasi
CREATE OR REPLACE FUNCTION public.fn_catat_pembayaran_konsinyasi(
    p_pesanan_id uuid,
    p_akun_kas_id uuid,
    p_nominal_bayar numeric,
    p_dicatat_oleh uuid DEFAULT NULL,
    p_keterangan text DEFAULT NULL
) RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
    v_pesanan RECORD;
    v_sisa_baru NUMERIC(15,2);
    v_status_baru VARCHAR(30);
    v_saldo_lama NUMERIC(15,2);
    v_saldo_baru NUMERIC(15,2);
    v_pengguna_id UUID := p_dicatat_oleh;
BEGIN
    SELECT * INTO v_pesanan FROM public.pesanan WHERE id = p_pesanan_id FOR UPDATE;

    IF v_pesanan IS NULL THEN
        RAISE EXCEPTION 'Pesanan % tidak ditemukan', p_pesanan_id;
    END IF;

    IF v_pesanan.adalah_tagihan = FALSE THEN
        RAISE EXCEPTION 'Pesanan ini adalah dokumen pengiriman/titip, bukan tagihan. Tidak bisa dicatat pembayarannya.';
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
            SELECT id INTO v_pengguna_id FROM public.pengguna WHERE karyawan_id = p_dicatat_oleh LIMIT 1;
        END IF;
    END IF;
    IF v_pengguna_id IS NULL THEN
        SELECT id INTO v_pengguna_id FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
    END IF;

    v_sisa_baru := v_pesanan.sisa_tagihan - p_nominal_bayar;
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

    SELECT saldo_saat_ini INTO v_saldo_lama FROM public.akun_kas WHERE id = p_akun_kas_id;
    IF v_saldo_lama IS NULL THEN
        RAISE EXCEPTION 'Akun kas % tidak ditemukan/tidak aktif', p_akun_kas_id;
    END IF;
    v_saldo_baru := v_saldo_lama + p_nominal_bayar;

    UPDATE public.akun_kas
    SET saldo_saat_ini = v_saldo_baru, diubah_pada = NOW()
    WHERE id = p_akun_kas_id;

    INSERT INTO public.arus_kas (
        akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
        referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
    ) VALUES (
        p_akun_kas_id, CURRENT_DATE, 'masuk', 'penjualan', p_nominal_bayar,
        COALESCE(p_keterangan, 'Pelunasan Nota Konsinyasi: ' || v_pesanan.nomor_nota),
        'pesanan', p_pesanan_id, v_saldo_baru, v_pengguna_id, NOW()
    );

    RETURN jsonb_build_object(
        'success', true,
        'pesanan_id', p_pesanan_id,
        'total_dibayar', v_pesanan.total_dibayar + p_nominal_bayar,
        'sisa_tagihan', v_sisa_baru,
        'status_pembayaran', v_status_baru
    );
END;
$$;

-- 9. Buat trigger fn_trg_proses_pengiriman_konsinyasi pada surat_jalan
CREATE OR REPLACE FUNCTION public.fn_trg_proses_pengiriman_konsinyasi()
RETURNS trigger
LANGUAGE plpgsql SECURITY DEFINER AS $$
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
$$;

DROP TRIGGER IF EXISTS trg_proses_pengiriman_konsinyasi ON public.surat_jalan;
CREATE TRIGGER trg_proses_pengiriman_konsinyasi
    AFTER UPDATE ON public.surat_jalan
    FOR EACH ROW
    EXECUTE FUNCTION public.fn_trg_proses_pengiriman_konsinyasi();

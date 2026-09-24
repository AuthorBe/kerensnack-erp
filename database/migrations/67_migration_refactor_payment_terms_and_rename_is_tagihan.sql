-- =============================================================================
-- MIGRATION 67: REFACTOR TIPE PEMBAYARAN & RENAME PESANAN.IS_TAGIHAN
-- 1. Rename kolom pesanan.adalah_tagihan -> pesanan.is_tagihan
-- 2. Standarisasi Tipe Pembayaran (cash, transfer, qris, konsinyasi, tempo_tanggal, tempo_faktur)
-- 3. Data backfill & integritas model toko konsinyasi vs reguler
-- 4. Update Stored Procedures & Triggers
-- =============================================================================

BEGIN;

-- 1. Rename kolom adalah_tagihan menjadi is_tagihan pada tabel pesanan jika kolom adalah_tagihan ada
DO $$
BEGIN
    IF EXISTS (
        SELECT 1 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'pesanan' 
          AND column_name = 'adalah_tagihan'
    ) THEN
        ALTER TABLE public.pesanan RENAME COLUMN adalah_tagihan TO is_tagihan;
    END IF;
END $$;

-- 2. Lepaskan check constraint lama untuk tipe pembayaran
ALTER TABLE public.pelanggan DROP CONSTRAINT IF EXISTS pelanggan_tipe_pembayaran_default_check;
ALTER TABLE public.pesanan DROP CONSTRAINT IF EXISTS pesanan_tipe_pembayaran_check;

-- 3. Data Migration & Backfill
-- A. Master Pelanggan: Migrasikan tipe tempo lama ke tempo_faktur
UPDATE public.pelanggan 
SET tipe_pembayaran_default = 'tempo_faktur' 
WHERE tipe_pembayaran_default IN ('tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari');

-- B. Sinkronisasi status model toko pelanggan: hanya konsinyasi yang is_konsinyasi = TRUE
UPDATE public.pelanggan 
SET is_konsinyasi = TRUE 
WHERE tipe_pembayaran_default = 'konsinyasi';

UPDATE public.pelanggan 
SET is_konsinyasi = FALSE 
WHERE tipe_pembayaran_default != 'konsinyasi';

-- C. Tabel Pesanan: Migrasikan tipe tempo lama
-- Jika memiliki tanggal_jatuh_tempo terisi -> tempo_tanggal
UPDATE public.pesanan 
SET tipe_pembayaran = 'tempo_tanggal' 
WHERE tipe_pembayaran IN ('tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari')
  AND tanggal_jatuh_tempo IS NOT NULL;

-- Jika tidak memiliki tanggal_jatuh_tempo -> tempo_faktur
UPDATE public.pesanan 
SET tipe_pembayaran = 'tempo_faktur' 
WHERE tipe_pembayaran IN ('tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'kredit')
  AND (tanggal_jatuh_tempo IS NULL OR tipe_pembayaran = 'kredit');

-- D. Sinkronisasi kolom is_tagihan pada pesanan
UPDATE public.pesanan 
SET is_tagihan = FALSE 
WHERE tipe_pembayaran = 'konsinyasi';

UPDATE public.pesanan 
SET is_tagihan = TRUE 
WHERE tipe_pembayaran != 'konsinyasi' 
  AND (is_tagihan IS NULL OR is_tagihan = FALSE);

-- 4. Pasang Check Constraint Baru
ALTER TABLE public.pelanggan ADD CONSTRAINT pelanggan_tipe_pembayaran_default_check 
CHECK (tipe_pembayaran_default IN (
    'cash', 
    'qris', 
    'transfer', 
    'konsinyasi', 
    'tempo_tanggal', 
    'tempo_faktur'
));

ALTER TABLE public.pesanan ADD CONSTRAINT pesanan_tipe_pembayaran_check 
CHECK (tipe_pembayaran IN (
    'cash', 
    'qris', 
    'transfer', 
    'konsinyasi', 
    'sebagian', 
    'tempo_tanggal', 
    'tempo_faktur'
));

-- 5. Perbarui Stored Procedure & Trigger yang mengacu pada is_tagihan

-- A. fn_buat_tagihan_kunjungan_konsinyasi
CREATE OR REPLACE FUNCTION public.fn_buat_tagihan_kunjungan_konsinyasi(
    p_kunjungan_ids uuid[],
    p_pengguna_id uuid DEFAULT NULL::uuid
)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $function$
DECLARE
    v_kid UUID;
    v_pelanggan_id UUID;
    v_driver_id UUID;
    v_nomor_nota VARCHAR(50);
    v_pesanan_id UUID;
    v_total_netto NUMERIC(15,2) := 0;
    v_count INT;
    v_cek_tagihan UUID;
    v_tgl_terakhir DATE;
BEGIN
    IF p_kunjungan_ids IS NULL OR array_length(p_kunjungan_ids, 1) = 0 THEN
        RAISE EXCEPTION 'Daftar ID kunjungan tidak boleh kosong';
    END IF;

    -- Validasi 1: Pastikan semua kunjungan berasal dari pelanggan yang sama
    SELECT COUNT(DISTINCT pelanggan_id), MAX(pelanggan_id), MAX(sales_driver_id)
    INTO v_count, v_pelanggan_id, v_driver_id
    FROM public.kunjungan_konsinyasi
    WHERE id = ANY(p_kunjungan_ids);

    IF v_count > 1 THEN
        RAISE EXCEPTION 'Semua kunjungan yang ditagihkan harus berasal dari 1 pelanggan / toko yang sama';
    END IF;

    IF v_pelanggan_id IS NULL THEN
        RAISE EXCEPTION 'Data kunjungan tidak ditemukan untuk ID yang diberikan';
    END IF;

    -- Validasi 2: Pastikan tidak ada kunjungan yang sudah ditagihkan sebelumnya
    SELECT tk.pesanan_id INTO v_cek_tagihan
    FROM public.tagihan_kunjungan tk
    WHERE tk.kunjungan_id = ANY(p_kunjungan_ids)
    LIMIT 1;

    IF v_cek_tagihan IS NOT NULL THEN
        RAISE EXCEPTION 'Salah satu kunjungan yang dipilih sudah pernah ditagihkan pada pesanan ID %', v_cek_tagihan;
    END IF;

    -- Hitung total laku dari rincian kunjungan
    SELECT COALESCE(SUM(rkk.subtotal_laku), 0)
    INTO v_total_netto
    FROM public.rincian_kunjungan_konsinyasi rkk
    WHERE rkk.kunjungan_id = ANY(p_kunjungan_ids);

    IF v_total_netto <= 0 THEN
        RAISE EXCEPTION 'Total penjualan dari kunjungan yang dipilih adalah Rp 0. Tidak ada tagihan yang dibuat.';
    END IF;

    -- Generate nomor nota tagihan konsinyasi unik
    v_nomor_nota := 'INV-KONS-' || TO_CHAR(CURRENT_DATE, 'YYYYMMDD') || '-' || LPAD(FLOOR(RANDOM() * 9000 + 1000)::TEXT, 4, '0');

    -- Ambil pengguna pencatat jika null
    IF p_pengguna_id IS NULL THEN
        SELECT id INTO p_pengguna_id 
        FROM public.pengguna 
        WHERE status_aktif = TRUE 
        ORDER BY dibuat_pada ASC 
        LIMIT 1;
    END IF;

    -- Insert pesanan (tagihan konsinyasi baru) dengan is_tagihan = TRUE
    INSERT INTO public.pesanan (
        nomor_nota, pelanggan_id, sales_driver_id,
        tanggal_pesanan, total_bruto, total_diskon, total_netto,
        tipe_pembayaran, status_pembayaran, status_pemrosesan,
        total_dibayar, sisa_tagihan, is_tagihan, catatan,
        dibuat_oleh, dibuat_pada, diubah_pada
    ) VALUES (
        v_nomor_nota, v_pelanggan_id, v_driver_id,
        CURRENT_DATE, v_total_netto, 0.00, v_total_netto,
        'konsinyasi', 'belum_lunas', 'selesai',
        0.00, v_total_netto, TRUE,
        'Tagihan Manual Konsinyasi: ' || array_length(p_kunjungan_ids, 1) || ' kunjungan',
        p_pengguna_id, NOW(), NOW()
    ) RETURNING id INTO v_pesanan_id;

    -- Insert item_pesanan
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

    -- Isi junction table tagihan_kunjungan dan update link di kunjungan
    FOREACH v_kid IN ARRAY p_kunjungan_ids LOOP
        INSERT INTO public.tagihan_kunjungan (pesanan_id, kunjungan_id)
        VALUES (v_pesanan_id, v_kid);

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

-- B. fn_catat_pembayaran_konsinyasi
CREATE OR REPLACE FUNCTION public.fn_catat_pembayaran_konsinyasi(
    p_pesanan_id uuid,
    p_akun_kas_id uuid,
    p_nominal_bayar numeric,
    p_dicatat_oleh uuid DEFAULT NULL::uuid,
    p_keterangan text DEFAULT NULL::text,
    p_tanggal_bayar date DEFAULT CURRENT_DATE
)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
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

    IF v_pesanan.is_tagihan = FALSE THEN
        RAISE EXCEPTION 'Pesanan ini adalah dokumen pengiriman/titip, bukan tagihan. Tidak bisa dicatat pembayarannya.';
    END IF;

    IF v_pesanan.status_pembayaran = 'dibatalkan' THEN
        RAISE EXCEPTION 'Tagihan % telah dibatalkan. Pembayaran tidak dapat diproses.', v_pesanan.nomor_nota;
    END IF;

    IF v_pesanan.status_pembayaran = 'lunas' AND v_pesanan.sisa_tagihan <= 0 THEN
        RAISE EXCEPTION 'Tagihan % sudah lunas sepenuhnya.', v_pesanan.nomor_nota;
    END IF;

    IF p_nominal_bayar <= 0 THEN
        RAISE EXCEPTION 'Nominal pembayaran harus lebih dari 0';
    END IF;

    IF p_nominal_bayar > v_pesanan.sisa_tagihan THEN
        RAISE EXCEPTION 'Nominal pembayaran (Rp %) melebihi sisa tagihan (Rp %)',
            TO_CHAR(p_nominal_bayar, 'FM999,999,999,990D00'),
            TO_CHAR(v_pesanan.sisa_tagihan, 'FM999,999,999,990D00');
    END IF;

    IF p_akun_kas_id IS NULL THEN
        RAISE EXCEPTION 'Akun kas penerima pembayaran wajib dipilih';
    END IF;

    IF v_pengguna_id IS NULL THEN
        SELECT id INTO v_pengguna_id FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
    END IF;

    v_sisa_baru := GREATEST(0.00, v_pesanan.sisa_tagihan - p_nominal_bayar);
    IF v_sisa_baru <= 0 THEN
        v_status_baru := 'lunas';
    ELSE
        v_status_baru := 'sebagian';
    END IF;

    UPDATE public.pesanan
    SET total_dibayar = COALESCE(total_dibayar, 0) + p_nominal_bayar,
        sisa_tagihan = v_sisa_baru,
        status_pembayaran = v_status_baru,
        akun_kas_id = p_akun_kas_id,
        diubah_pada = NOW()
    WHERE id = p_pesanan_id;

    UPDATE public.pelanggan
    SET total_piutang_berjalan = GREATEST(0.00, COALESCE(total_piutang_berjalan, 0) - p_nominal_bayar),
        diubah_pada = NOW()
    WHERE id = v_pesanan.pelanggan_id;

    SELECT saldo_saat_ini INTO v_saldo_lama FROM public.akun_kas WHERE id = p_akun_kas_id FOR UPDATE;
    v_saldo_baru := COALESCE(v_saldo_lama, 0) + p_nominal_bayar;

    UPDATE public.akun_kas
    SET saldo_saat_ini = v_saldo_baru,
        diubah_pada = NOW()
    WHERE id = p_akun_kas_id;

    v_ket_kas := COALESCE(p_keterangan, 'Pelunasan Tagihan Konsinyasi: ' || v_pesanan.nomor_nota);

    INSERT INTO public.transaksi_kas (
        akun_kas_id, jenis_transaksi, kategori,
        nominal, saldo_sebelum, saldo_setelah,
        tanggal_transaksi, referensi_tabel, referensi_id,
        keterangan, dibuat_oleh, dibuat_pada
    ) VALUES (
        p_akun_kas_id, 'masuk', 'pendapatan_operasional',
        p_nominal_bayar, v_saldo_lama, v_saldo_baru,
        v_tgl_transaksi, 'pesanan', p_pesanan_id,
        v_ket_kas, v_pengguna_id, NOW()
    );

    RETURN jsonb_build_object(
        'success', true,
        'nomor_nota', v_pesanan.nomor_nota,
        'nominal_bayar', p_nominal_bayar,
        'sisa_tagihan_baru', v_sisa_baru,
        'status_pembayaran', v_status_baru,
        'saldo_kas_baru', v_saldo_baru
    );
END;
$function$;

-- C. fn_revisi_dan_rekonsiliasi_piutang_pelanggan
CREATE OR REPLACE FUNCTION public.fn_revisi_dan_rekonsiliasi_piutang_pelanggan(
    p_pelanggan_id uuid DEFAULT NULL::uuid
)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $function$
DECLARE
    v_updated_count INT := 0;
    v_total_piutang_baru NUMERIC(15, 2) := 0;
BEGIN
    IF p_pelanggan_id IS NOT NULL THEN
        UPDATE public.pelanggan p
        SET total_piutang_berjalan = COALESCE((
            SELECT SUM(pes.sisa_tagihan)
            FROM public.pesanan pes
            WHERE pes.pelanggan_id = p.id
              AND pes.is_tagihan = TRUE
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
        UPDATE public.pelanggan p
        SET total_piutang_berjalan = COALESCE((
            SELECT SUM(pes.sisa_tagihan)
            FROM public.pesanan pes
            WHERE pes.pelanggan_id = p.id
              AND pes.is_tagihan = TRUE
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

-- D. fn_trg_proses_pengiriman_konsinyasi
CREATE OR REPLACE FUNCTION public.fn_trg_proses_pengiriman_konsinyasi()
RETURNS trigger
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
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

        IF v_pesanan.tipe_pembayaran = 'konsinyasi' AND v_pesanan.is_tagihan = FALSE THEN
            v_user_id := v_pesanan.dibuat_oleh;

            FOR r_item IN
                SELECT item_id, kuantitas_satuan_dasar
                FROM public.item_pesanan
                WHERE pesanan_id = NEW.pesanan_id
            LOOP
                SELECT stok_fisik_saat_ini INTO v_stok_gudang_lama
                FROM public.item WHERE id = r_item.item_id;

                v_stok_gudang_baru := GREATEST(0, COALESCE(v_stok_gudang_lama, 0) - r_item.kuantitas_satuan_dasar);

                UPDATE public.item
                SET stok_fisik_saat_ini = v_stok_gudang_baru,
                    diubah_pada = NOW()
                WHERE id = r_item.item_id;

                INSERT INTO public.kartu_stok (
                    item_id, jenis_mutasi, kuantitas_satuan_dasar,
                    stok_sebelum, stok_setelah,
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

            UPDATE public.pesanan
            SET status_pemrosesan = 'selesai',
                diubah_pada = NOW()
            WHERE id = NEW.pesanan_id;
        END IF;
    END IF;

    RETURN NEW;
END;
$function$;

COMMIT;

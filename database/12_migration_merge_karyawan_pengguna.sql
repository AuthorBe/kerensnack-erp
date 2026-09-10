-- database/12_migration_merge_karyawan_pengguna.sql

-- 1. Tambah kolom ke pengguna
ALTER TABLE public.pengguna
    ADD COLUMN IF NOT EXISTS nik VARCHAR(50) UNIQUE,
    ADD COLUMN IF NOT EXISTS posisi VARCHAR(50),
    ADD COLUMN IF NOT EXISTS nomor_telepon VARCHAR(25),
    ADD COLUMN IF NOT EXISTS nomor_polisi_kendaraan VARCHAR(20),
    ADD COLUMN IF NOT EXISTS alamat TEXT,
    ADD COLUMN IF NOT EXISTS tanggal_bergabung DATE,
    ADD COLUMN IF NOT EXISTS bank_nama VARCHAR(100) DEFAULT 'Tunai',
    ADD COLUMN IF NOT EXISTS bank_nomor_rekening VARCHAR(50),
    ADD COLUMN IF NOT EXISTS bank_atas_nama VARCHAR(100),
    ADD COLUMN IF NOT EXISTS karyawan_legacy_id INT UNIQUE;

-- Make nama_pengguna and peran_id nullable for employees without accounts
ALTER TABLE public.pengguna ALTER COLUMN nama_pengguna DROP NOT NULL;
ALTER TABLE public.pengguna ALTER COLUMN peran_id DROP NOT NULL;
-- Karena pengguna_nama_pengguna_key ada, NULL tetap aman. 

-- 2. Tambah pengguna_id ke karyawan
ALTER TABLE public.karyawan
    ADD COLUMN IF NOT EXISTS pengguna_id UUID UNIQUE REFERENCES public.pengguna(id) ON DELETE SET NULL;

-- 3. Insert pengguna for karyawan who DON'T have an account yet
INSERT INTO public.pengguna (
    nama_lengkap, posisi, nik, nomor_telepon, nomor_polisi_kendaraan,
    alamat, tanggal_bergabung, bank_nama, bank_nomor_rekening, bank_atas_nama,
    karyawan_legacy_id, status_aktif
)
SELECT
    k.nama_karyawan, k.posisi, k.nik, k.nomor_telepon, k.nomor_polisi_kendaraan,
    k.alamat, k.tanggal_bergabung, k.bank_nama, k.bank_nomor_rekening, k.bank_atas_nama,
    k.id_legacy, k.status_aktif
FROM public.karyawan k
LEFT JOIN public.pengguna p ON p.karyawan_id = k.id
WHERE p.id IS NULL;

-- 4. Update existing pengguna with data from their linked karyawan
UPDATE public.pengguna p
SET
    nik = k.nik,
    posisi = k.posisi,
    nomor_telepon = k.nomor_telepon,
    nomor_polisi_kendaraan = k.nomor_polisi_kendaraan,
    alamat = k.alamat,
    tanggal_bergabung = k.tanggal_bergabung,
    bank_nama = k.bank_nama,
    bank_nomor_rekening = k.bank_nomor_rekening,
    bank_atas_nama = k.bank_atas_nama,
    karyawan_legacy_id = k.id_legacy,
    nama_lengkap = COALESCE(p.nama_lengkap, k.nama_karyawan)
FROM public.karyawan k
WHERE p.karyawan_id = k.id;

-- 5. Link karyawan.pengguna_id back to pengguna
UPDATE public.karyawan k
SET pengguna_id = p.id
FROM public.pengguna p
WHERE p.karyawan_id = k.id OR (p.karyawan_id IS NULL AND p.nama_lengkap = k.nama_karyawan AND p.posisi = k.posisi);

-- 6. Drop old columns from karyawan
ALTER TABLE public.karyawan
    DROP COLUMN IF EXISTS nama_karyawan,
    DROP COLUMN IF EXISTS nik,
    DROP COLUMN IF EXISTS posisi,
    DROP COLUMN IF EXISTS nomor_telepon,
    DROP COLUMN IF EXISTS nomor_polisi_kendaraan,
    DROP COLUMN IF EXISTS alamat,
    DROP COLUMN IF EXISTS tanggal_bergabung,
    DROP COLUMN IF EXISTS bank_nama,
    DROP COLUMN IF EXISTS bank_nomor_rekening,
    DROP COLUMN IF EXISTS bank_atas_nama,
    DROP COLUMN IF EXISTS id_legacy,
    DROP COLUMN IF EXISTS status_aktif;

-- 7. Drop karyawan_id from pengguna
ALTER TABLE public.pengguna DROP COLUMN IF EXISTS karyawan_id;

-- 8. View v_karyawan_info
CREATE OR REPLACE VIEW public.v_karyawan_info AS
SELECT
    k.id,
    k.pengguna_id,
    p.nama_lengkap AS nama_karyawan,
    p.nik,
    p.posisi,
    p.nomor_telepon,
    p.nomor_polisi_kendaraan,
    p.alamat,
    p.tanggal_bergabung,
    p.bank_nama,
    p.bank_nomor_rekening,
    p.bank_atas_nama,
    p.status_aktif,
    k.tipe_penggajian,
    k.gaji_pokok_bulanan,
    k.uang_kehadiran_harian,
    k.tunjangan_bulanan,
    k.persentase_komisi_sales,
    k.dibuat_pada,
    k.diubah_pada
FROM public.karyawan k
JOIN public.pengguna p ON k.pengguna_id = p.id;

-- 9. Fix triggers/functions
CREATE OR REPLACE FUNCTION public.fn_catat_pembayaran_konsinyasi(
    p_pesanan_id uuid,
    p_akun_kas_id uuid,
    p_nominal_bayar numeric,
    p_dicatat_oleh uuid DEFAULT NULL,
    p_keterangan text DEFAULT NULL,
    p_tanggal_bayar date DEFAULT CURRENT_DATE
) RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER AS $$
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
$$;


CREATE OR REPLACE FUNCTION public.fn_proses_kunjungan_konsinyasi(
    p_pelanggan_id uuid,
    p_sales_driver_id uuid,
    p_rincian jsonb,
    p_keterangan text DEFAULT NULL,
    p_foto_kunjungan text DEFAULT NULL
) RETURNS jsonb
LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
    v_kunjungan_id UUID;
    v_nomor_kunjungan VARCHAR(50);
    v_pengguna_id UUID := p_sales_driver_id;
    v_driver_id UUID := p_sales_driver_id;
    v_pesanan_id UUID;
    v_nomor_nota VARCHAR(50);
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
BEGIN
    v_nomor_kunjungan := 'KONSIN-' || TO_CHAR(NOW(), 'YYYYMMDD-HH24MISS');

    -- Pastikan user_id valid untuk audit trail
    IF v_pengguna_id IS NULL THEN
        SELECT pengguna_id INTO v_pengguna_id FROM public.karyawan WHERE id = p_sales_driver_id LIMIT 1;
        IF v_pengguna_id IS NULL THEN
            SELECT id INTO v_pengguna_id FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
        END IF;
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

    INSERT INTO public.kunjungan_konsinyasi (
        nomor_kunjungan, pelanggan_id, sales_driver_id, tanggal_kunjungan,
        catatan_kunjungan, url_foto_kunjungan, dibuat_oleh, dibuat_pada, diubah_pada
    ) VALUES (
        v_nomor_kunjungan, p_pelanggan_id, v_driver_id, CURRENT_DATE,
        p_keterangan, p_foto_kunjungan, v_pengguna_id, NOW(), NOW()
    ) RETURNING id INTO v_kunjungan_id;

    UPDATE public.pelanggan 
    SET terakhir_opname = NOW(), diubah_pada = NOW() 
    WHERE id = p_pelanggan_id;

    FOR r_item IN 
        SELECT 
            (elem->>'item_id')::UUID AS item_id,
            CASE 
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
        SELECT COALESCE(stok_titip_saat_ini, 0) INTO v_stok_titip_lama
        FROM public.stok_konsinyasi_toko
        WHERE pelanggan_id = p_pelanggan_id AND item_id = r_item.item_id;

        IF v_stok_titip_lama IS NULL THEN
            v_stok_titip_lama := 0;
        END IF;

        SELECT COALESCE(harga_pokok_pembelian, 0.00) INTO v_harga_pokok
        FROM public.item WHERE id = r_item.item_id;

        v_nilai_kerugian := 0.00;

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

        IF r_item.sisa_fisik_param IS NOT NULL THEN
            v_stok_rak_baru := GREATEST(0, r_item.sisa_fisik_param);
            IF r_item.jumlah_laku_param IS NOT NULL THEN
                v_laku := r_item.jumlah_laku_param;
            ELSE
                v_laku := GREATEST(0, v_stok_titip_lama - (v_stok_rak_baru + r_item.retur_bagus + r_item.retur_rusak));
            END IF;
        ELSE
            IF r_item.jumlah_laku_param IS NOT NULL THEN
                v_laku := r_item.jumlah_laku_param;
                v_stok_rak_baru := GREATEST(0, v_stok_titip_lama - (v_laku + r_item.retur_bagus + r_item.retur_rusak));
            ELSE
                v_laku := 0;
                v_stok_rak_baru := GREATEST(0, v_stok_titip_lama - (r_item.retur_bagus + r_item.retur_rusak));
            END IF;
        END IF;

        v_selisih := r_item.selisih_qty;

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

        INSERT INTO public.stok_konsinyasi_toko (
            pelanggan_id, item_id, stok_titip_saat_ini, terakhir_opname_pada, dibuat_pada, diubah_pada
        ) VALUES (
            p_pelanggan_id, r_item.item_id, v_stok_rak_baru, NOW(), NOW(), NOW()
        )
        ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
            stok_titip_saat_ini = EXCLUDED.stok_titip_saat_ini,
            terakhir_opname_pada = NOW(),
            diubah_pada = NOW();

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

    UPDATE public.kunjungan_konsinyasi 
    SET total_laku_nominal = v_total_laku_netto
    WHERE id = v_kunjungan_id;

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

        UPDATE public.kunjungan_konsinyasi 
        SET pesanan_id = v_pesanan_id 
        WHERE id = v_kunjungan_id;

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

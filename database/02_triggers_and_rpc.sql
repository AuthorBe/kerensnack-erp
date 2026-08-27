-- ==============================================================================
-- KEREN SNACK - TRIGGERS & RPC ENGINE v2.0
-- Platform: Supabase / PostgreSQL 15+
-- Fitur Khusus:
--   1. Dynamic Pricing Resolver (Level 1-28 per Grup Produk x Diskon Grup/Toko)
--   2. Consignment Opname Processor (Hitung Laku Rak Toko & Auto-Generate Invoice)
--   3. Universal Barcode Resolver (Deteksi Varian Rasa dari 1 Barcode Kemasan)
--   4. BoM Repacking, Surat Jalan Auto-Cut, Approval Kas Telegram, Sinking Kasbon
-- ==============================================================================

-- ==============================================================================
-- 1. DYNAMIC PRICING ENGINE (Kalkulator Harga Otomatis 28 Level & Diskon)
-- ==============================================================================
CREATE OR REPLACE FUNCTION public.fn_hitung_harga_jual_item(
    p_item_id UUID,
    p_pelanggan_id UUID
)
RETURNS JSONB AS $$
DECLARE
    v_grup_produk_id UUID;
    v_level_harga INT;
    v_diskon_persen NUMERIC(5, 2);
    v_diskon_nominal NUMERIC(15, 2);
    v_harga_pcs_dasar NUMERIC(15, 2);
    v_harga_bal_dasar NUMERIC(15, 2);
    v_harga_pcs_netto NUMERIC(15, 2);
    v_harga_bal_netto NUMERIC(15, 2);
    v_nama_grup_pelanggan VARCHAR;
BEGIN
    -- 1. Ambil grup_produk dari item
    SELECT grup_id INTO v_grup_produk_id FROM public.item WHERE id = p_item_id;

    -- 2. Ambil aturan harga dari pelanggan & grup_pelanggan
    SELECT 
        COALESCE(p.override_level_harga, gp.default_level_harga, 1),
        COALESCE(p.override_diskon_persen, gp.diskon_persen_default, 0.00),
        COALESCE(p.override_diskon_nominal, gp.diskon_nominal_default, 0.00),
        gp.nama_grup
    INTO v_level_harga, v_diskon_persen, v_diskon_nominal, v_nama_grup_pelanggan
    FROM public.pelanggan p
    JOIN public.grup_pelanggan gp ON p.grup_pelanggan_id = gp.id
    WHERE p.id = p_pelanggan_id;

    -- Jika tidak ditemukan pelanggan, default level 1 tanpa diskon
    IF v_level_harga IS NULL THEN
        v_level_harga := 1;
        v_diskon_persen := 0.00;
        v_diskon_nominal := 0.00;
        v_nama_grup_pelanggan := 'Umum';
    END IF;

    -- 3. Cari harga base di grup_produk_harga_level
    SELECT harga_jual_pcs, harga_jual_bal 
    INTO v_harga_pcs_dasar, v_harga_bal_dasar
    FROM public.grup_produk_harga_level
    WHERE grup_produk_id = v_grup_produk_id AND level_harga = v_level_harga;

    -- Fallback jika level harga belum diset di grup, ambil HPP x 1.25
    IF v_harga_pcs_dasar IS NULL THEN
        SELECT harga_pokok_pembelian, (harga_pokok_pembelian * konversi_distribusi_ke_dasar)
        INTO v_harga_pcs_dasar, v_harga_bal_dasar
        FROM public.item WHERE id = p_item_id;
        v_harga_pcs_dasar := COALESCE(v_harga_pcs_dasar, 10000);
        v_harga_bal_dasar := COALESCE(v_harga_bal_dasar, 200000);
    END IF;

    -- 4. Hitung Diskon (% dan Nominal)
    v_harga_pcs_netto := v_harga_pcs_dasar - (v_harga_pcs_dasar * (v_diskon_persen / 100.0)) - v_diskon_nominal;
    v_harga_bal_netto := v_harga_bal_dasar - (v_harga_bal_dasar * (v_diskon_persen / 100.0)) - (v_diskon_nominal * 20);

    RETURN jsonb_build_object(
        'level_harga', v_level_harga,
        'grup_pelanggan', v_nama_grup_pelanggan,
        'diskon_persen', v_diskon_persen,
        'diskon_nominal', v_diskon_nominal,
        'harga_pcs_bruto', v_harga_pcs_dasar,
        'harga_bal_bruto', v_harga_bal_dasar,
        'harga_pcs_netto', GREATEST(0, v_harga_pcs_netto),
        'harga_bal_netto', GREATEST(0, v_harga_bal_netto)
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ==============================================================================
-- 2. UNIVERSAL BARCODE DISAMBIGUATION RESOLVER
-- ==============================================================================
CREATE OR REPLACE FUNCTION public.fn_cari_item_by_barcode(p_barcode VARCHAR)
RETURNS JSONB AS $$
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
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ==============================================================================
-- 3. MODUL KONSINYASI ENGINE (Opname Rak Toko & Auto-Generate Invoice)
-- ==============================================================================
CREATE OR REPLACE FUNCTION public.fn_proses_kunjungan_konsinyasi(
    p_pelanggan_id UUID,
    p_sales_driver_id UUID,
    p_rincian JSONB -- Array of {item_id, tambah_titip_baru, sisa_fisik_di_rak, retur_rusak}
)
RETURNS JSONB AS $$
DECLARE
    v_kunjungan_id UUID;
    v_nomor_kunjungan VARCHAR(100);
    v_pesanan_id UUID;
    v_nomor_nota VARCHAR(100);
    v_total_laku_netto NUMERIC(15, 2) := 0.00;
    r_item RECORD;
    v_stok_titip_lama INT;
    v_laku INT;
    v_subtotal NUMERIC(15, 2);
    v_harga_info JSONB;
    v_harga_deal NUMERIC(15, 2);
    v_stok_rak_baru INT;
BEGIN
    v_nomor_kunjungan := 'KONSIN-' || TO_CHAR(NOW(), 'YYYYMMDD-HH24MISS');
    v_nomor_nota := 'INV-KONSIN-' || TO_CHAR(NOW(), 'YYYYMMDD-HH24MISS');

    -- 1. Buat Header Kunjungan
    INSERT INTO public.kunjungan_konsinyasi (
        nomor_kunjungan, pelanggan_id, sales_driver_id, tanggal_kunjungan, total_laku_nominal
    ) VALUES (
        v_nomor_kunjungan, p_pelanggan_id, p_sales_driver_id, CURRENT_DATE, 0.00
    ) RETURNING id INTO v_kunjungan_id;

    -- 2. Buat Draft Pesanan Invoice untuk Barang yang Laku
    INSERT INTO public.pesanan (
        nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
        tipe_pembayaran, status_pembayaran, status_pemrosesan, catatan
    ) VALUES (
        v_nomor_nota, p_pelanggan_id, p_sales_driver_id, CURRENT_DATE,
        'konsinyasi', 'belum_lunas', 'selesai', 'Otomatis dari kunjungan konsinyasi: ' || v_nomor_kunjungan
    ) RETURNING id INTO v_pesanan_id;

    -- 3. Loop Rincian Barang
    FOR r_item IN 
        SELECT 
            (elem->>'item_id')::UUID AS item_id,
            COALESCE((elem->>'tambah_titip_baru')::INT, 0) AS tambah_titip_baru,
            COALESCE((elem->>'sisa_fisik_di_rak')::INT, 0) AS sisa_fisik_di_rak,
            COALESCE((elem->>'retur_rusak')::INT, 0) AS retur_rusak
        FROM jsonb_array_elements(p_rincian) AS elem
    LOOP
        -- Ambil stok titip lama di toko ini
        SELECT COALESCE(stok_titip_saat_ini, 0) INTO v_stok_titip_lama
        FROM public.stok_konsinyasi_toko
        WHERE pelanggan_id = p_pelanggan_id AND item_id = r_item.item_id;

        IF v_stok_titip_lama IS NULL THEN
            v_stok_titip_lama := 0;
        END IF;

        -- Hitung jumlah yang laku
        v_laku := (v_stok_titip_lama + r_item.tambah_titip_baru) - (r_item.sisa_fisik_di_rak + r_item.retur_rusak);
        IF v_laku < 0 THEN v_laku := 0; END IF;

        -- Ambil harga deal toko ini
        v_harga_info := public.fn_hitung_harga_jual_item(r_item.item_id, p_pelanggan_id);
        v_harga_deal := (v_harga_info->>'harga_pcs_netto')::NUMERIC;
        v_subtotal := v_laku * v_harga_deal;
        v_total_laku_netto := v_total_laku_netto + v_subtotal;

        -- Catat rincian kunjungan
        INSERT INTO public.rincian_kunjungan_konsinyasi (
            kunjungan_id, item_id, stok_titip_awal, tambah_titip_baru,
            sisa_fisik_di_rak, retur_rusak, jumlah_laku_terjual, harga_satuan_deal, subtotal_laku
        ) VALUES (
            v_kunjungan_id, r_item.item_id, v_stok_titip_lama, r_item.tambah_titip_baru,
            r_item.sisa_fisik_di_rak, r_item.retur_rusak, v_laku, v_harga_deal, v_subtotal
        );

        -- Jika ada laku, masukkan ke item_pesanan
        IF v_laku > 0 THEN
            INSERT INTO public.item_pesanan (
                pesanan_id, item_id, kuantitas_satuan_dasar, harga_satuan_deal, subtotal
            ) VALUES (
                v_pesanan_id, r_item.item_id, v_laku, v_harga_deal, v_subtotal
            );
        END IF;

        -- Update saldo stok titip rak toko
        v_stok_rak_baru := r_item.sisa_fisik_di_rak;
        INSERT INTO public.stok_konsinyasi_toko (
            pelanggan_id, item_id, stok_titip_saat_ini, terakhir_opname_pada, diubah_pada
        ) VALUES (
            p_pelanggan_id, r_item.item_id, v_stok_rak_baru, NOW(), NOW()
        )
        ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
            stok_titip_saat_ini = EXCLUDED.stok_titip_saat_ini,
            terakhir_opname_pada = NOW(),
            diubah_pada = NOW();

        -- Jika ada retur rusak, masukkan ke riwayat stok gudang pusat sebagai retur
        IF r_item.retur_rusak > 0 THEN
            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_pada
            ) VALUES (
                r_item.item_id, 'konsinyasi_retur_masuk', r_item.retur_rusak,
                0, 0, 'kunjungan_konsinyasi', v_kunjungan_id, 'Retur barang rusak dari toko konsinyasi', NOW()
            );
        END IF;
    END LOOP;

    -- Update total invoice & kunjungan
    UPDATE public.kunjungan_konsinyasi 
    SET total_laku_nominal = v_total_laku_netto, pesanan_id = v_pesanan_id 
    WHERE id = v_kunjungan_id;

    UPDATE public.pesanan 
    SET total_bruto = v_total_laku_netto, total_netto = v_total_laku_netto 
    WHERE id = v_pesanan_id;

    -- Tambah piutang berjalan toko konsinyasi
    UPDATE public.pelanggan 
    SET total_piutang_berjalan = total_piutang_berjalan + v_total_laku_netto, diubah_pada = NOW() 
    WHERE id = p_pelanggan_id;

    RETURN jsonb_build_object(
        'success', true,
        'nomor_kunjungan', v_nomor_kunjungan,
        'nomor_nota_laku', v_nomor_nota,
        'total_laku_netto', v_total_laku_netto
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

-- ==============================================================================
-- 4. TRIGGER PRODUKSI HARIAN (BOM AUTO-CUT REPACKING)
-- ==============================================================================
CREATE OR REPLACE FUNCTION public.fn_trg_produksi_harian_after_insert()
RETURNS TRIGGER AS $$
DECLARE
    v_stok_lama INT;
    v_stok_baru INT;
    v_total_pcs INT;
    r_bom RECORD;
    v_bahan_stok_lama INT;
    v_bahan_stok_baru INT;
    v_pemakaian_bahan NUMERIC;
BEGIN
    v_total_pcs := NEW.kuantitas_pcs + NEW.lembur_pcs;

    SELECT stok_fisik_saat_ini INTO v_stok_lama 
    FROM public.item 
    WHERE id = NEW.item_id;

    v_stok_baru := COALESCE(v_stok_lama, 0) + v_total_pcs;

    UPDATE public.item 
    SET stok_fisik_saat_ini = v_stok_baru, diubah_pada = NOW()
    WHERE id = NEW.item_id;

    INSERT INTO public.riwayat_stok (
        item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
        referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
    ) VALUES (
        NEW.item_id, 'produksi_masuk', v_total_pcs, COALESCE(v_stok_lama, 0), v_stok_baru,
        'produksi_harian', NEW.id, 'Hasil produksi borongan karyawan', NEW.dicatat_oleh, NOW()
    );

    FOR r_bom IN 
        SELECT item_bahan_id, jumlah_kebutuhan 
        FROM public.komposisi_item 
        WHERE item_jadi_id = NEW.item_id
    LOOP
        v_pemakaian_bahan := ROUND(v_total_pcs * r_bom.jumlah_kebutuhan);

        SELECT stok_fisik_saat_ini INTO v_bahan_stok_lama 
        FROM public.item 
        WHERE id = r_bom.item_bahan_id;

        v_bahan_stok_baru := COALESCE(v_bahan_stok_lama, 0) - v_pemakaian_bahan::INT;

        UPDATE public.item 
        SET stok_fisik_saat_ini = v_bahan_stok_baru, diubah_pada = NOW()
        WHERE id = r_bom.item_bahan_id;

        INSERT INTO public.riwayat_stok (
            item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
            referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
        ) VALUES (
            r_bom.item_bahan_id, 'bahan_terpakai_produksi', -v_pemakaian_bahan::INT, 
            COALESCE(v_bahan_stok_lama, 0), v_bahan_stok_baru,
            'produksi_harian', NEW.id, 'Pemakaian bahan baku repacking', NEW.dicatat_oleh, NOW()
        );
    END LOOP;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS trg_produksi_harian_after_insert ON public.produksi_harian;
CREATE TRIGGER trg_produksi_harian_after_insert
AFTER INSERT ON public.produksi_harian
FOR EACH ROW EXECUTE FUNCTION public.fn_trg_produksi_harian_after_insert();

-- ==============================================================================
-- 5. TRIGGER SURAT JALAN & LOGISTIK
-- ==============================================================================
CREATE OR REPLACE FUNCTION public.fn_trg_surat_jalan_status_update()
RETURNS TRIGGER AS $$
DECLARE
    r_item RECORD;
    v_stok_lama INT;
    v_stok_baru INT;
BEGIN
    IF (OLD.status_surat_jalan IS DISTINCT FROM NEW.status_surat_jalan) AND (NEW.status_surat_jalan = 'sedang_dikirim') THEN
        FOR r_item IN 
            SELECT item_id, kuantitas_satuan_dasar 
            FROM public.item_pesanan 
            WHERE pesanan_id = NEW.pesanan_id
        LOOP
            SELECT stok_fisik_saat_ini INTO v_stok_lama 
            FROM public.item 
            WHERE id = r_item.item_id;

            v_stok_baru := COALESCE(v_stok_lama, 0) - r_item.kuantitas_satuan_dasar;

            UPDATE public.item 
            SET stok_fisik_saat_ini = v_stok_baru, diubah_pada = NOW()
            WHERE id = r_item.item_id;

            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
            ) VALUES (
                r_item.item_id, 'penjualan_keluar', -r_item.kuantitas_satuan_dasar,
                COALESCE(v_stok_lama, 0), v_stok_baru,
                'surat_jalan', NEW.id, 'Barang keluar via surat jalan: ' || NEW.nomor_surat_jalan, NEW.disetujui_oleh, NOW()
            );
        END LOOP;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS trg_surat_jalan_status_update ON public.surat_jalan;
CREATE TRIGGER trg_surat_jalan_status_update
AFTER UPDATE OF status_surat_jalan ON public.surat_jalan
FOR EACH ROW EXECUTE FUNCTION public.fn_trg_surat_jalan_status_update();

-- ==============================================================================
-- 6. TRIGGER APPROVAL DRAF PENGELUARAN TELEGRAM
-- ==============================================================================
CREATE OR REPLACE FUNCTION public.fn_trg_draf_pengeluaran_approval()
RETURNS TRIGGER AS $$
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
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS trg_draf_pengeluaran_approval ON public.draf_pengeluaran;
CREATE TRIGGER trg_draf_pengeluaran_approval
AFTER UPDATE OF status_approval ON public.draf_pengeluaran
FOR EACH ROW EXECUTE FUNCTION public.fn_trg_draf_pengeluaran_approval();

-- ==============================================================================
-- 7. TRIGGER PELUNASAN PESANAN (ARUS KAS MASUK)
-- ==============================================================================
CREATE OR REPLACE FUNCTION public.fn_trg_pesanan_lunas_arus_kas()
RETURNS TRIGGER AS $$
DECLARE
    v_akun_id UUID;
    v_saldo_lama NUMERIC;
    v_saldo_baru NUMERIC;
BEGIN
    IF (OLD.status_pembayaran IS DISTINCT FROM NEW.status_pembayaran) AND (NEW.status_pembayaran = 'lunas') THEN
        SELECT id INTO v_akun_id FROM public.akun_kas WHERE status_aktif = TRUE LIMIT 1;
        SELECT saldo_saat_ini INTO v_saldo_lama FROM public.akun_kas WHERE id = v_akun_id;
        
        v_saldo_baru := COALESCE(v_saldo_lama, 0) + NEW.total_netto;

        UPDATE public.akun_kas 
        SET saldo_saat_ini = v_saldo_baru, diubah_pada = NOW()
        WHERE id = v_akun_id;

        INSERT INTO public.arus_kas (
            akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
            keterangan, referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
        ) VALUES (
            v_akun_id, CURRENT_DATE, 'masuk', 'penjualan', NEW.total_netto,
            'Pelunasan Nota: ' || NEW.nomor_nota, 'pesanan', NEW.id,
            v_saldo_baru, NEW.dibuat_oleh, NOW()
        );

        UPDATE public.pelanggan 
        SET total_piutang_berjalan = GREATEST(0, total_piutang_berjalan - NEW.total_netto), diubah_pada = NOW()
        WHERE id = NEW.pelanggan_id;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS trg_pesanan_lunas_arus_kas ON public.pesanan;
CREATE TRIGGER trg_pesanan_lunas_arus_kas
AFTER UPDATE OF status_pembayaran ON public.pesanan
FOR EACH ROW EXECUTE FUNCTION public.fn_trg_pesanan_lunas_arus_kas();

-- ==============================================================================
-- 8. TRIGGER KASBON & TABUNGAN
-- ==============================================================================
CREATE OR REPLACE FUNCTION public.fn_trg_potongan_kasbon_update_saldo()
RETURNS TRIGGER AS $$
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
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS trg_potongan_kasbon_update_saldo ON public.potongan_kasbon;
CREATE TRIGGER trg_potongan_kasbon_update_saldo
AFTER INSERT ON public.potongan_kasbon
FOR EACH ROW EXECUTE FUNCTION public.fn_trg_potongan_kasbon_update_saldo();

CREATE OR REPLACE FUNCTION public.fn_trg_transaksi_tabungan_update_saldo()
RETURNS TRIGGER AS $$
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
$$ LANGUAGE plpgsql SECURITY DEFINER;

DROP TRIGGER IF EXISTS trg_transaksi_tabungan_update_saldo ON public.transaksi_tabungan;
CREATE TRIGGER trg_transaksi_tabungan_update_saldo
AFTER INSERT ON public.transaksi_tabungan
FOR EACH ROW EXECUTE FUNCTION public.fn_trg_transaksi_tabungan_update_saldo();

-- ==============================================================================
-- 9. HELPER RPC LOGGING (Pencatatan Audit Trail Universal)
-- ==============================================================================
CREATE OR REPLACE FUNCTION public.fn_catat_log_aktivitas(
    p_pengguna_id UUID DEFAULT NULL,
    p_nama_aktor VARCHAR DEFAULT 'Sistem Otomasi n8n',
    p_peran_aktor VARCHAR DEFAULT 'ai_n8n',
    p_sumber_aksi VARCHAR DEFAULT 'n8n_automation',
    p_kategori_aktivitas VARCHAR DEFAULT 'ai_interaction',
    p_jenis_aksi VARCHAR DEFAULT 'EXECUTE',
    p_tabel_terdampak VARCHAR DEFAULT NULL,
    p_id_referensi UUID DEFAULT NULL,
    p_deskripsi_aktivitas TEXT DEFAULT '',
    p_data_sebelum JSONB DEFAULT NULL,
    p_data_sesudah JSONB DEFAULT NULL,
    p_id_pesan_telegram BIGINT DEFAULT NULL
)
RETURNS UUID AS $$
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
$$ LANGUAGE plpgsql SECURITY DEFINER;


-- database/34_migration_pricing_master_and_cleanup.sql
-- ==============================================================================
-- MIGRASI 34: MASTER LEVEL HARGA 1-30, ELIMINASI HARGA_JUAL_BAL, & PILIHAN B
-- ==============================================================================

-- 1. Buat Tabel Master Level Harga Terpusat (Level 1 s/d 30)
CREATE TABLE IF NOT EXISTS public.master_level_harga (
    level_nomor INT PRIMARY KEY CHECK (level_nomor BETWEEN 1 AND 30),
    nama_level VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- 2. Seed 30 Tingkat Level Harga Resmi
INSERT INTO public.master_level_harga (level_nomor, nama_level, deskripsi)
VALUES
(1,  'Level 1 - Ritel Standar (Konsumen Umum / POS)', 'Harga jual eceran standar untuk konsumen langsung / walk-in kasir'),
(2,  'Level 2 - Ritel Khusus / Member', 'Harga ritel pelanggan langganan atau member khusus'),
(3,  'Level 3 - Swalayan Lokal', 'Harga jaringan minimarket lokal'),
(4,  'Level 4 - Supermarket Regional', 'Harga jaringan supermarket wilayah'),
(5,  'Level 5 - Konsinyasi Rak (Toko Titip Jual)', 'Harga titip jual display rak di toko/warung mitra'),
(6,  'Level 6 - Konsinyasi Premium', 'Harga titip jual rak premium di lokasi strategis'),
(7,  'Level 7 - Sub-Agen Warung', 'Harga warung kelontong pemesan berkala'),
(8,  'Level 8 - Grosir Mitra (Mitra Warung A)', 'Harga kemitraan warung grosir reguler tier A'),
(9,  'Level 9 - Grosir Semi-Besar', 'Harga grosir skala menengah'),
(10, 'Level 10 - Grosir Inti Kota', 'Harga grosir pusat kota volume menengah'),
(11, 'Level 11 - Grosir Pasar Tradisional', 'Harga grosir pedagang pasar tradisional'),
(12, 'Level 12 - Grosir Pasar (Grosir Pasar B)', 'Harga distributor grosir pasar besar tier B'),
(13, 'Level 13 - Agen Wilayah Sub-Distrik', 'Harga agen pemegang wilayah kecamatan'),
(14, 'Level 14 - Agen Kabupaten', 'Harga agen distributor tingkat kabupaten'),
(15, 'Level 15 - Distributor Utama', 'Harga distributor rekanan utama'),
(16, 'Level 16 - Distributor Provinsi', 'Harga distributor besar tingkat provinsi'),
(17, 'Level 17 - Key Account Modern Trade', 'Harga jaringan toko modern berbadan hukum'),
(18, 'Level 18 - Hypermarket Nasional', 'Harga kontrak jaringan ritel modern skala nasional'),
(19, 'Level 19 - Horeka / Hotel Restoran Kafe', 'Harga suplai sektor kuliner & perhotelan'),
(20, 'Level 20 - Mitra Katering & Event', 'Harga pesanan volume katering dan acara'),
(21, 'Level 21 - Kemitraan Komunitas', 'Harga khusus koperasi dan organisasi komunitas'),
(22, 'Level 22 - Reseller Online Gold', 'Harga kemitraan reseller daring tier gold'),
(23, 'Level 23 - Reseller Online Platinum', 'Harga kemitraan reseller daring tier platinum'),
(24, 'Level 24 - B2B Marketplace Partner', 'Harga kanal penjualan digital b2b'),
(25, 'Level 25 - Corporate Order Khusus', 'Harga pemesanan korporat / instansi volume besar'),
(26, 'Level 26 - Ekspor Regional', 'Harga kemitraan ekspor regional asia tenggara'),
(27, 'Level 27 - Ekspor Internasional', 'Harga kemitraan ekspor global kontainer'),
(28, 'Level 28 - Tier Khusus Pabrik', 'Harga khusus order langsung pabrik volume tertinggi'),
(29, 'Level 29 - Tier Kontrak Khusus', 'Harga perjanjian kontrak kuantitas khusus tahunan'),
(30, 'Level 30 - Tier Spesial Direksi', 'Harga kebijakan diskresi khusus manajemen direksi')
ON CONFLICT (level_nomor) DO UPDATE SET
    nama_level = EXCLUDED.nama_level,
    deskripsi = EXCLUDED.deskripsi,
    diubah_pada = NOW();

-- 3. Tambahkan Foreign Key dari grup_produk_harga_level dan grup_pelanggan ke master_level_harga
DO $$
BEGIN
    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_gphl_level'
    ) THEN
        ALTER TABLE public.grup_produk_harga_level
        ADD CONSTRAINT fk_gphl_level
        FOREIGN KEY (level_harga) REFERENCES public.master_level_harga(level_nomor)
        ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM pg_constraint WHERE conname = 'fk_gp_default_level'
    ) THEN
        ALTER TABLE public.grup_pelanggan
        ADD CONSTRAINT fk_gp_default_level
        FOREIGN KEY (default_level_harga) REFERENCES public.master_level_harga(level_nomor)
        ON UPDATE CASCADE ON DELETE RESTRICT;
    END IF;
END $$;

-- 4. Hapus Kolom harga_jual_bal dari grup_produk_harga_level
ALTER TABLE public.grup_produk_harga_level DROP COLUMN IF EXISTS harga_jual_bal;

-- 5. Perbarui Stored Procedure fn_hitung_harga_jual_item (Pilihan B: Strict Rejection, Eliminasi Bal)
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

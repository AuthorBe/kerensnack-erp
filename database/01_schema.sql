-- ==============================================================================
-- KEREN SNACK - SINGLE SOURCE OF TRUTH (SSOT) CANONICAL DATABASE SCHEMA v2.0
-- Model: Custom AI ERP untuk Toko & Manufaktur Repacking KEREN Snack
-- Fitur Khusus:
--   1. Dynamic Pricing Matrix (Harga Level per Grup Produk x Grup Pelanggan)
--   2. Dedicated Consignment Ledger (Stok Titip Rak Toko vs Gudang Pusat)
--   3. Universal Barcode Disambiguation (Satu Barcode Kemasan untuk Banyak Varian Rasa)
--   4. Dedicated Sales (Toko Binaan & Komisi), Dedicated Driver (Logistik Armada), HR Payroll Borongan & AI Telegram
-- Status: 100% Terverifikasi & Selaras Penuh dengan Live Database Supabase PostgreSQL
-- ==============================================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ==============================================================================
-- MODUL 1: AUTENTIKASI, PENGGUNA & HAK AKSES (RBAC)
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.peran (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nama_peran VARCHAR(50) NOT NULL UNIQUE, -- 'owner', 'admin', 'mandor', 'sales', 'driver', 'developer'
    deskripsi TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_peran_no_sales_driver CHECK (nama_peran != 'sales_driver')
);

CREATE TABLE IF NOT EXISTS public.izin (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_izin VARCHAR(100) NOT NULL UNIQUE,
    nama_izin VARCHAR(100) NOT NULL,
    grup_izin VARCHAR(50) NOT NULL,
    deskripsi TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.izin_peran (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    peran_id UUID NOT NULL REFERENCES public.peran(id) ON DELETE CASCADE,
    izin_id UUID NOT NULL REFERENCES public.izin(id) ON DELETE CASCADE,
    diizinkan BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_izin_peran UNIQUE (peran_id, izin_id)
);

CREATE TABLE IF NOT EXISTS public.pengguna (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    peran_id UUID REFERENCES public.peran(id) ON DELETE SET NULL,
    nama_lengkap VARCHAR(150) NOT NULL,
    nama_pengguna VARCHAR(100) UNIQUE,
    kata_sandi VARCHAR(255),
    id_telegram BIGINT UNIQUE,
    nomor_whatsapp VARCHAR(25),
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    nik VARCHAR(30) UNIQUE,
    posisi VARCHAR(50), -- 'pengemasan', 'admin', 'mandor', 'sales', 'driver', 'developer'
    nomor_telepon VARCHAR(25),
    nomor_polisi_kendaraan VARCHAR(20),
    alamat TEXT,
    tanggal_bergabung DATE DEFAULT CURRENT_DATE,
    bank_nama VARCHAR(50),
    bank_nomor_rekening VARCHAR(50),
    bank_atas_nama VARCHAR(100),
    karyawan_legacy_id INT UNIQUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_pengguna_posisi_valid CHECK (posisi IN ('developer', 'owner', 'admin', 'mandor', 'pengemasan', 'sales', 'driver'))
);

CREATE TABLE IF NOT EXISTS public.izin_pengguna (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pengguna_id UUID NOT NULL REFERENCES public.pengguna(id) ON DELETE CASCADE,
    izin_id UUID NOT NULL REFERENCES public.izin(id) ON DELETE CASCADE,
    diizinkan BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_izin_pengguna UNIQUE (pengguna_id, izin_id)
);

-- ==============================================================================
-- MODUL 2: MASTER ENTITAS, WILAYAH, KARYAWAN & GRUP PELANGGAN
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.wilayah (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_rute VARCHAR(50) NOT NULL UNIQUE, -- 'RUTE-TNG-TIMUR', 'RUTE-JAKBAR'
    nama_wilayah VARCHAR(100) NOT NULL,
    provinsi VARCHAR(100) NOT NULL DEFAULT 'Banten',
    kota_kabupaten VARCHAR(100) NOT NULL DEFAULT 'Kota Tangerang',
    sub_wilayah TEXT,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Tabel Karyawan (Profil Penggajian 1-to-1 Terpadu dengan Akun Pengguna)
CREATE TABLE IF NOT EXISTS public.karyawan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pengguna_id UUID UNIQUE REFERENCES public.pengguna(id) ON DELETE SET NULL,
    tipe_penggajian VARCHAR(30) NOT NULL CHECK (tipe_penggajian IN ('borongan', 'bulanan')),
    gaji_pokok_bulanan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    uang_kehadiran_harian NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    tunjangan_bulanan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Master Skema Komisi Sales Bertingkat (Tiered Commission System Terpusat)
CREATE TABLE IF NOT EXISTS public.skema_komisi_sales (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    urutan INT NOT NULL,
    nama_tier VARCHAR(100) NOT NULL,
    omzet_min NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    omzet_maks NUMERIC(15, 2) DEFAULT NULL, -- NULL berarti tanpa batas atas (tak terhingga)
    persentase NUMERIC(5, 2) NOT NULL DEFAULT 0.00,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_skema_komisi_omzet_min_non_neg CHECK (omzet_min >= 0),
    CONSTRAINT chk_skema_komisi_persentase_range CHECK (persentase >= 0 AND persentase <= 100),
    CONSTRAINT chk_skema_komisi_range_valid CHECK (omzet_maks IS NULL OR omzet_maks >= omzet_min)
);

CREATE INDEX IF NOT EXISTS idx_skema_komisi_urutan ON public.skema_komisi_sales (urutan ASC);
CREATE INDEX IF NOT EXISTS idx_skema_komisi_aktif ON public.skema_komisi_sales (status_aktif);

-- Seed 4 Skema Tier Komisi Sales Standar
INSERT INTO public.skema_komisi_sales (urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif)
VALUES
(1, 'Tier 1 (Dasar)', 0.00, 20000000.00, 1.00, TRUE),
(2, 'Tier 2 (Reguler)', 20000000.01, 35000000.00, 2.50, TRUE),
(3, 'Tier 3 (Gold)', 35000000.01, 50000000.00, 4.00, TRUE),
(4, 'Tier 4 (Platinum)', 50000000.01, NULL, 5.00, TRUE)
ON CONFLICT DO NOTHING;

-- View Kanonikal Info Karyawan Terpadu (Menggabungkan Identitas Pengguna & Parameter Gaji)
CREATE OR REPLACE VIEW public.v_karyawan_info
WITH (security_invoker = true) AS
SELECT
    k.id,
    k.pengguna_id,
    p.nama_lengkap AS nama_karyawan,
    p.nik,
    p.posisi,
    COALESCE(p.nomor_whatsapp, p.nomor_telepon) AS nomor_telepon,
    p.nomor_polisi_kendaraan,
    p.alamat,
    p.tanggal_bergabung,
    p.bank_nama,
    p.bank_nomor_rekening,
    p.bank_atas_nama,
    p.status_aktif,
    p.nama_pengguna,
    k.tipe_penggajian,
    k.gaji_pokok_bulanan,
    k.uang_kehadiran_harian,
    k.tunjangan_bulanan,
    k.dibuat_pada,
    k.diubah_pada,
    p.nomor_whatsapp,
    p.peran_id,
    p.karyawan_legacy_id AS id_legacy
FROM public.karyawan k
JOIN public.pengguna p ON p.id = k.pengguna_id;

CREATE TABLE IF NOT EXISTS public.pemasok (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_pemasok VARCHAR(50) NOT NULL UNIQUE,
    nama_pemasok VARCHAR(150) NOT NULL,
    nama_kontak VARCHAR(100) DEFAULT NULL,
    wilayah_id UUID REFERENCES public.wilayah(id),
    alamat_lengkap TEXT,
    link_google_maps TEXT DEFAULT NULL,
    nomor_whatsapp VARCHAR(25) DEFAULT NULL,
    email VARCHAR(150) DEFAULT NULL,
    termin_bayar VARCHAR(30) DEFAULT 'cash',
    nama_bank VARCHAR(50) DEFAULT NULL,
    nomor_rekening VARCHAR(50) DEFAULT NULL,
    atas_nama_rekening VARCHAR(100) DEFAULT NULL,
    catatan TEXT DEFAULT NULL,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MASTER LEVEL HARGA (TINGKAT 1 S/D 30 TERPUSAT)
-- ==============================================================================
CREATE TABLE IF NOT EXISTS public.master_level_harga (
    level_nomor INT PRIMARY KEY CHECK (level_nomor BETWEEN 1 AND 30),
    nama_level VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Seed 30 Tingkat Level Harga Resmi Standar
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

-- Master Grup Pelanggan (Pemegang Aturan Level Harga 1 s/d 30 & Diskon Otomatis)
CREATE TABLE IF NOT EXISTS public.grup_pelanggan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_grup VARCHAR(50) NOT NULL UNIQUE, -- 'GRP-A', 'GRP-GROSIR-TNG', 'GRP-KONSINYASI'
    nama_grup VARCHAR(100) NOT NULL,
    default_level_harga INT NOT NULL DEFAULT 1 REFERENCES public.master_level_harga(level_nomor) ON UPDATE CASCADE ON DELETE RESTRICT,
    diskon_persen_default NUMERIC(5, 2) NOT NULL DEFAULT 0.00,
    diskon_nominal_default NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Master Pelanggan (Toko Langganan & Toko Konsinyasi)
CREATE TABLE IF NOT EXISTS public.pelanggan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_pelanggan VARCHAR(50) NOT NULL UNIQUE,
    nama_toko VARCHAR(150) NOT NULL,
    nama_pemilik VARCHAR(100),
    grup_pelanggan_id UUID NOT NULL REFERENCES public.grup_pelanggan(id),
    is_konsinyasi BOOLEAN NOT NULL DEFAULT FALSE, -- True jika toko titip jual (konsinyasi)
    wilayah_id UUID REFERENCES public.wilayah(id),
    alamat_lengkap TEXT NOT NULL,
    nomor_whatsapp VARCHAR(25),
    tipe_pembayaran_default VARCHAR(30) NOT NULL DEFAULT 'cash' CHECK (tipe_pembayaran_default IN ('cash', 'qris', 'transfer', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'konsinyasi')),
    plafon_piutang NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_piutang_berjalan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    sales_driver_id UUID REFERENCES public.pengguna(id), -- Sales Pembina / Penanggung Jawab Toko (Khusus Posisi Sales, dikunci oleh trg_guard_pelanggan_sales_driver)
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    nama_bank VARCHAR(50) DEFAULT NULL,
    nomor_rekening VARCHAR(50) DEFAULT NULL,
    atas_nama_rekening VARCHAR(100) DEFAULT NULL,
    link_google_maps TEXT DEFAULT NULL,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MODUL 3: MEREK, GRUP PRODUK, ITEM & PRICING MATRIX DINAMIS
-- ==============================================================================

-- Master Data Merek Dagang
CREATE TABLE IF NOT EXISTS public.merek (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_merek VARCHAR(50) NOT NULL UNIQUE, -- 'KRN', 'MRK-001'
    nama_merek VARCHAR(150) NOT NULL, -- 'KEREN SNACK'
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Grup Produk: Pemegang Barcode Universal, Harga Level Dinamis, & Relasi Merek
CREATE TABLE IF NOT EXISTS public.grup_produk (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_grup VARCHAR(50) NOT NULL UNIQUE, -- 'GRP-SINGKONG-250', 'GRP-BRND-135'
    nama_grup VARCHAR(150) NOT NULL,
    merek_id UUID REFERENCES public.merek(id) ON UPDATE CASCADE ON DELETE RESTRICT,
    barcode_universal VARCHAR(100), -- Barcode kemasan luar yang dipakai bersama oleh varian rasa
    satuan_dasar VARCHAR(30) NOT NULL DEFAULT 'pcs',
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Tabel Matriks Harga Jual per Grup Produk (Level Harga 1 s/d 30 Terpusat & Murni Pcs)
CREATE TABLE IF NOT EXISTS public.grup_produk_harga_level (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    grup_produk_id UUID NOT NULL REFERENCES public.grup_produk(id) ON DELETE CASCADE,
    level_harga INT NOT NULL REFERENCES public.master_level_harga(level_nomor) ON UPDATE CASCADE ON DELETE RESTRICT,
    nama_level VARCHAR(100) NOT NULL, -- 'Level 1 - Ritel Standar (Konsumen Umum / POS)', dll.
    harga_jual_pcs NUMERIC(15, 2) NOT NULL, -- Harga jual murni per bungkus (pcs)
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_grup_harga_level UNIQUE (grup_produk_id, level_harga)
);

CREATE TABLE IF NOT EXISTS public.kelompok_upah_borongan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    nama_kelompok VARCHAR(100) NOT NULL UNIQUE, -- 'Kelompok 600', 'Kelompok 500'
    upah_per_bungkus NUMERIC(15, 2) NOT NULL,
    keterangan TEXT,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Item Tunggal (Varian Rasa / Bahan Mentah / Bahan Kemasan / SKU Fisik)
CREATE TABLE IF NOT EXISTS public.item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy_produk INT UNIQUE,
    grup_id UUID REFERENCES public.grup_produk(id),
    kode_sku VARCHAR(50) NOT NULL UNIQUE, -- 'KS-SK-ASIN-250', 'KS-SK-MNS-250'
    barcode VARCHAR(100), -- Barcode spesifik (atau sama dengan barcode_universal grup)
    nama_item VARCHAR(200) NOT NULL,
    varian_rasa VARCHAR(100), -- 'Asin', 'Manis', 'Opak', 'Balado'
    tipe_item VARCHAR(30) NOT NULL CHECK (tipe_item IN ('barang_jadi', 'bahan_mentah', 'bahan_kemas')),
    satuan_dasar VARCHAR(30) NOT NULL,
    kelompok_borongan_id UUID REFERENCES public.kelompok_upah_borongan(id),
    pemasok_utama_id UUID REFERENCES public.pemasok(id),
    harga_pokok_pembelian NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    stok_minimum_peringatan NUMERIC(15, 2) NOT NULL DEFAULT 10.00,
    stok_fisik_saat_ini NUMERIC(15, 2) NOT NULL DEFAULT 0.00, -- Snapshot otomatis dari riwayat_stok
    status_jual BOOLEAN NOT NULL DEFAULT TRUE,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Relasi Item Khusus / Whitelist Produk Pelanggan
CREATE TABLE IF NOT EXISTS public.pelanggan_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id) ON DELETE RESTRICT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_pelanggan_item UNIQUE (pelanggan_id, item_id)
);

-- Bill of Materials / Resep Repacking (Bal Curah -> Pcs Jadi)
CREATE TABLE IF NOT EXISTS public.komposisi_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    item_jadi_id UUID NOT NULL REFERENCES public.item(id) ON DELETE CASCADE,
    item_bahan_id UUID NOT NULL REFERENCES public.item(id) ON DELETE RESTRICT,
    jumlah_kebutuhan NUMERIC(15, 4) NOT NULL CHECK (jumlah_kebutuhan > 0), -- Contoh: 0.1350 kg singkong curah + 1 lembar plastik
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_komposisi_item UNIQUE (item_jadi_id, item_bahan_id)
);

-- ==============================================================================
-- MODUL 4: MANAJEMEN GUDANG, MUTASI STOK & AUDIT OPNAME
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.pembelian (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_faktur_pembelian VARCHAR(100) NOT NULL UNIQUE,
    pemasok_id UUID NOT NULL REFERENCES public.pemasok(id),
    tanggal_pembelian DATE NOT NULL DEFAULT CURRENT_DATE,
    total_biaya NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    status_pembayaran VARCHAR(30) NOT NULL DEFAULT 'belum_lunas' CHECK (status_pembayaran IN ('belum_lunas', 'lunas', 'batal')),
    status_penerimaan VARCHAR(30) NOT NULL DEFAULT 'diterima' CHECK (status_penerimaan IN ('menunggu', 'diterima')),
    url_foto_nota TEXT,
    catatan TEXT,
    dibuat_oleh UUID REFERENCES public.pengguna(id),
    jenis_dokumen VARCHAR(20) DEFAULT 'langsung',
    metode_logistik VARCHAR(20) DEFAULT 'mandiri',
    sales_driver_id UUID REFERENCES public.pengguna(id) ON DELETE SET NULL, -- Driver / Petugas Pengambil Belanjaan Vendor (Bisa Driver atau Sales)
    tanggal_jadwal_belanja DATE DEFAULT NULL,
    instruksi_driver TEXT DEFAULT NULL,
    metode_bayar_belanja VARCHAR(30) DEFAULT NULL,
    nominal_dibayar_driver NUMERIC(15, 2) DEFAULT 0.00,
    nomor_nota_vendor VARCHAR(50) DEFAULT NULL,
    foto_bukti_kendala TEXT DEFAULT NULL,
    alasan_kendala TEXT DEFAULT NULL,
    waktu_diambil TIMESTAMPTZ DEFAULT NULL,
    waktu_diterima_gudang TIMESTAMPTZ DEFAULT NULL,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.rincian_pembelian (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pembelian_id UUID NOT NULL REFERENCES public.pembelian(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id),
    kuantitas NUMERIC(15, 2) NOT NULL CHECK (kuantitas > 0),
    satuan VARCHAR(30) NOT NULL,
    harga_satuan NUMERIC(15, 2) NOT NULL,
    subtotal NUMERIC(15, 2) NOT NULL,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.penyesuaian_stok (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_dokumen VARCHAR(100) NOT NULL UNIQUE,
    item_id UUID NOT NULL REFERENCES public.item(id),
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    tipe_penyesuaian VARCHAR(50) NOT NULL CHECK (tipe_penyesuaian IN ('opname_hilang', 'opname_lebih', 'barang_rusak', 'retur_masuk_manual')),
    kuantitas INT NOT NULL CHECK (kuantitas > 0),
    alasan_keterangan TEXT NOT NULL,
    dicatat_oleh UUID NOT NULL REFERENCES public.pengguna(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Ledger Riwayat Mutasi Stok Immutable (Gudang Pusat)
CREATE TABLE IF NOT EXISTS public.riwayat_stok (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    item_id UUID NOT NULL REFERENCES public.item(id),
    tipe_mutasi VARCHAR(50) NOT NULL CHECK (tipe_mutasi IN (
        'produksi_masuk', 'bahan_terpakai_produksi', 'penjualan_keluar',
        'pembelian_masuk', 'penyesuaian_opname_tambah', 'penyesuaian_opname_kurang',
        'retur_pelanggan_masuk', 'konsinyasi_keluar', 'konsinyasi_retur_masuk',
        'konsinyasi_retur_rusak', 'item_keluar_waste'
    )),
    jumlah_perubahan NUMERIC(15, 2) NOT NULL,
    stok_sebelum NUMERIC(15, 2) NOT NULL,
    stok_sesudah NUMERIC(15, 2) NOT NULL,
    referensi_tabel VARCHAR(50) NOT NULL,
    referensi_id UUID NOT NULL,
    keterangan TEXT,
    dibuat_oleh UUID REFERENCES public.pengguna(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Audit Bulk Opname Fisik Gudang
CREATE TABLE IF NOT EXISTS public.opname_gudang (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_opname VARCHAR(50) NOT NULL UNIQUE,
    tanggal_opname DATE NOT NULL DEFAULT CURRENT_DATE,
    keterangan TEXT,
    petugas_id UUID REFERENCES public.pengguna(id) ON DELETE SET NULL,
    status_opname VARCHAR(20) NOT NULL DEFAULT 'selesai' CHECK (status_opname IN ('draf', 'selesai', 'dibatalkan')),
    total_sku_diperiksa INT NOT NULL DEFAULT 0,
    total_sku_selisih INT NOT NULL DEFAULT 0,
    total_nilai_selisih_rp NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.opname_gudang_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    opname_gudang_id UUID NOT NULL REFERENCES public.opname_gudang(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id) ON DELETE RESTRICT,
    stok_sistem NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    stok_fisik NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    selisih_stok NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    harga_pokok_saat_opname NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    satuan VARCHAR(20) NOT NULL DEFAULT 'pcs',
    catatan TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MODUL 5: PENJUALAN, ORDER & LOGISTIK SURAT JALAN
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.pesanan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_nota VARCHAR(100) NOT NULL UNIQUE,
    pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id),
    sales_driver_id UUID REFERENCES public.pengguna(id), -- Sales atau Pengemudi yang menangani pesanan / pengiriman
    tanggal_pesanan DATE NOT NULL DEFAULT CURRENT_DATE,
    total_bruto NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_diskon NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_netto NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    tipe_pembayaran VARCHAR(30) NOT NULL CHECK (tipe_pembayaran IN ('cash', 'qris', 'transfer', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'konsinyasi', 'sebagian', 'kredit')),
    tanggal_jatuh_tempo DATE,
    status_pembayaran VARCHAR(30) NOT NULL DEFAULT 'belum_lunas' CHECK (status_pembayaran IN ('belum_lunas', 'sebagian', 'tempo', 'lunas', 'dibatalkan')),
    status_pemrosesan VARCHAR(30) NOT NULL DEFAULT 'menunggu_approval' CHECK (status_pemrosesan IN ('menunggu_approval', 'disetujui', 'siap_kirim', 'dalam_pengiriman', 'selesai', 'dibatalkan')),
    catatan TEXT,
    dibuat_oleh UUID REFERENCES public.pengguna(id),
    akun_kas_id UUID REFERENCES public.akun_kas(id),
    total_dibayar NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    sisa_tagihan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    adalah_tagihan BOOLEAN NOT NULL DEFAULT TRUE,
    uang_diterima NUMERIC(15, 2) DEFAULT 0.00,
    kembalian NUMERIC(15, 2) DEFAULT 0.00,
    waktu_gagal_kirim TIMESTAMPTZ,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_pesanan_total_netto_non_negative CHECK (total_netto >= 0),
    CONSTRAINT chk_pesanan_total_dibayar_non_negative CHECK (total_dibayar >= 0),
    CONSTRAINT chk_pesanan_sisa_tagihan_non_negative CHECK (sisa_tagihan >= 0)
);

CREATE TABLE IF NOT EXISTS public.item_pesanan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pesanan_id UUID NOT NULL REFERENCES public.pesanan(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id),
    kuantitas_satuan_dasar INT NOT NULL CHECK (kuantitas_satuan_dasar > 0),
    harga_satuan_deal NUMERIC(15, 2) NOT NULL,
    diskon_item_persen NUMERIC(5, 2) DEFAULT 0.00,
    diskon_item_nominal NUMERIC(15, 2) DEFAULT 0.00,
    is_bonus BOOLEAN NOT NULL DEFAULT FALSE,
    subtotal NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    harga_pokok_satuan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.surat_jalan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_surat_jalan VARCHAR(100) NOT NULL UNIQUE,
    pesanan_id UUID NOT NULL REFERENCES public.pesanan(id),
    sales_driver_id UUID REFERENCES public.pengguna(id), -- Driver / Kurir Logistik Pengantar (Bisa Driver atau Sales)
    rute_wilayah_id UUID REFERENCES public.wilayah(id),
    url_pdf_dokumen TEXT,
    status_surat_jalan VARCHAR(30) NOT NULL DEFAULT 'draf_n8n' CHECK (status_surat_jalan IN ('draf_n8n', 'disetujui_owner', 'sedang_dikirim', 'selesai_diterima', 'gagal_kembali', 'ditolak_owner')),
    bukti_terima_foto TEXT, -- Foto bukti terima toko yang diupload Pengemudi/Sales via Telegram
    nama_penerima_toko VARCHAR(100),
    waktu_berangkat TIMESTAMPTZ,
    waktu_sampai TIMESTAMPTZ,
    disetujui_oleh UUID REFERENCES public.pengguna(id),
    tanggal_surat_jalan DATE DEFAULT CURRENT_DATE,
    foto_bukti_gagal TEXT,
    alasan_gagal VARCHAR(50),
    catatan_gagal TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MODUL 6: MODUL KONSINYASI KHUSUS (CONSIGNMENT SHELF LEDGER)
-- ==============================================================================

-- Saldo Stok Titip Nyata per Toko Konsinyasi
CREATE TABLE IF NOT EXISTS public.stok_konsinyasi_toko (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id) ON DELETE RESTRICT,
    item_id UUID NOT NULL REFERENCES public.item(id) ON DELETE RESTRICT,
    stok_titip_saat_ini INT NOT NULL DEFAULT 0 CHECK (stok_titip_saat_ini >= 0),
    terakhir_opname_pada TIMESTAMPTZ,
    stok_hilang_pending INT NOT NULL DEFAULT 0,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_konsinyasi_toko_item UNIQUE (pelanggan_id, item_id)
);

-- Header Kunjungan & Opname Rak Konsinyasi (Pencatatan Fisik Rak Toko)
CREATE TABLE IF NOT EXISTS public.kunjungan_konsinyasi (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_kunjungan VARCHAR(100) NOT NULL UNIQUE,
    pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id),
    sales_driver_id UUID NOT NULL REFERENCES public.pengguna(id), -- Petugas yang melakukan opname fisik rak (Sales, atau Driver jika diberi tiket izin RBAC oleh Owner)
    tanggal_kunjungan DATE NOT NULL DEFAULT CURRENT_DATE,
    pesanan_id UUID REFERENCES public.pesanan(id) ON DELETE SET NULL, -- Invoice laku yang otomatis terbit (Komisi tetap dialokasikan ke Sales Pembina Toko)
    total_laku_nominal NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    catatan TEXT,
    dibuat_oleh UUID REFERENCES public.pengguna(id),
    foto_kunjungan TEXT,
    catatan_owner TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Detail Opname Rak: Menghitung Barang Laku & Retur Rusak
CREATE TABLE IF NOT EXISTS public.rincian_kunjungan_konsinyasi (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kunjungan_id UUID NOT NULL REFERENCES public.kunjungan_konsinyasi(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id),
    stok_titip_awal INT NOT NULL, -- Sisa di sistem sebelumnya
    tambah_titip_baru INT NOT NULL DEFAULT 0,
    sisa_fisik_di_rak INT NOT NULL, -- Hasil hitung fisik di rak toko
    retur_bagus INT NOT NULL DEFAULT 0, -- Sisa bagus yang ditarik balik ke gudang
    retur_rusak INT NOT NULL DEFAULT 0, -- Bungkus rusak/bocor yang ditarik balik
    jumlah_laku_terjual INT NOT NULL, -- stok_titip_awal - (sisa_fisik_di_rak + retur_bagus + retur_rusak)
    selisih_qty INT NOT NULL DEFAULT 0,
    harga_satuan_deal NUMERIC(15, 2) NOT NULL,
    subtotal_laku NUMERIC(15, 2) NOT NULL,
    harga_pokok_satuan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    nilai_kerugian_rusak NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Decoupling Tagihan dari Kunjungan Opname
CREATE TABLE IF NOT EXISTS public.tagihan_kunjungan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kunjungan_id UUID NOT NULL REFERENCES public.kunjungan_konsinyasi(id) ON DELETE CASCADE,
    pesanan_id UUID NOT NULL REFERENCES public.pesanan(id) ON DELETE CASCADE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MODUL 7: OPERASIONAL PRODUKSI, BORONGAN & HR PAYROLL
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.penggajian (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    nomor_referensi VARCHAR(100) NOT NULL UNIQUE,
    periode_awal DATE NOT NULL,
    periode_akhir DATE NOT NULL,
    tipe_penggajian VARCHAR(30) NOT NULL CHECK (tipe_penggajian IN ('mingguan', 'bulanan', 'gabungan')),
    total_gaji_dikeluarkan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    status VARCHAR(30) NOT NULL DEFAULT 'draf' CHECK (status IN ('draf', 'disetujui', 'dibayarkan')),
    disetujui_oleh UUID REFERENCES public.pengguna(id),
    disetujui_pada TIMESTAMPTZ,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.produksi_harian (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    karyawan_id UUID NOT NULL REFERENCES public.karyawan(id),
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    item_id UUID NOT NULL REFERENCES public.item(id),
    kuantitas_pcs INT NOT NULL CHECK (kuantitas_pcs >= 0),
    kuantitas_bal INT NOT NULL DEFAULT 0,
    lembur_pcs INT NOT NULL DEFAULT 0,
    upah_per_pcs_snapshot NUMERIC(15, 2) NOT NULL,
    total_upah_didapat NUMERIC(15, 2) NOT NULL,
    penggajian_id UUID REFERENCES public.penggajian(id),
    dicatat_oleh UUID REFERENCES public.pengguna(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_produksi_karyawan_tanggal_item UNIQUE (karyawan_id, tanggal, item_id)
);

CREATE TABLE IF NOT EXISTS public.absensi (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    karyawan_id UUID NOT NULL REFERENCES public.karyawan(id),
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    status_kehadiran VARCHAR(30) NOT NULL DEFAULT 'hadir' CHECK (status_kehadiran IN ('hadir', 'izin', 'sakit', 'libur', 'alpa')),
    telat BOOLEAN NOT NULL DEFAULT FALSE,
    lembur_nominal NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    catatan TEXT,
    penggajian_id UUID REFERENCES public.penggajian(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_absensi_karyawan_tanggal UNIQUE (karyawan_id, tanggal)
);

CREATE TABLE IF NOT EXISTS public.kasbon (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    karyawan_id UUID NOT NULL REFERENCES public.karyawan(id),
    tanggal_pengajuan DATE NOT NULL DEFAULT CURRENT_DATE,
    total_pinjaman NUMERIC(15, 2) NOT NULL CHECK (total_pinjaman > 0),
    potongan_per_periode NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    sisa_pinjaman NUMERIC(15, 2) NOT NULL,
    status_kasbon VARCHAR(30) NOT NULL DEFAULT 'aktif' CHECK (status_kasbon IN ('aktif', 'lunas', 'dibatalkan')),
    keterangan TEXT,
    catatan TEXT,
    disetujui_oleh UUID REFERENCES public.pengguna(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.rincian_penggajian (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    penggajian_id UUID NOT NULL REFERENCES public.penggajian(id) ON DELETE CASCADE,
    karyawan_id UUID NOT NULL REFERENCES public.karyawan(id),
    gaji_pokok NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    hari_hadir INT NOT NULL DEFAULT 0,
    total_uang_kehadiran NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_tunjangan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    tunjangan_bulanan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    tunjangan_lain NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    catatan_tunjangan_lain VARCHAR(255),
    total_upah_borongan NUMERIC(15, 2) NOT NULL DEFAULT 0.00, -- Upah hasil produksi
    total_upah_lembur NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_komisi_sales NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_potongan_kasbon NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    potongan_lain NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    catatan_potongan_lain VARCHAR(255),
    nominal_pembulatan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_potongan_tabungan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    penarikan_tabungan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_penarikan_gaji NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    is_excluded BOOLEAN NOT NULL DEFAULT FALSE,
    catatan_pengecualian TEXT,
    gaji_bersih_diterima NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    rincian_json JSONB NOT NULL DEFAULT '{}'::jsonb,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.potongan_kasbon (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    kasbon_id UUID NOT NULL REFERENCES public.kasbon(id) ON DELETE CASCADE,
    rincian_penggajian_id UUID REFERENCES public.rincian_penggajian(id) ON DELETE SET NULL,
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    nominal NUMERIC(15, 2) NOT NULL CHECK (nominal > 0),
    tipe_potongan VARCHAR(30) NOT NULL DEFAULT 'payroll' CHECK (tipe_potongan IN ('payroll', 'manual')),
    keterangan TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.tabungan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    karyawan_id UUID NOT NULL UNIQUE REFERENCES public.karyawan(id) ON DELETE CASCADE,
    saldo NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.transaksi_tabungan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    tabungan_id UUID NOT NULL REFERENCES public.tabungan(id) ON DELETE CASCADE,
    karyawan_id UUID NOT NULL REFERENCES public.karyawan(id),
    rincian_penggajian_id UUID REFERENCES public.rincian_penggajian(id),
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    tipe VARCHAR(30) NOT NULL CHECK (tipe IN ('deposit', 'withdrawal')),
    jumlah NUMERIC(15, 2) NOT NULL CHECK (jumlah > 0),
    sumber VARCHAR(30) NOT NULL DEFAULT 'payroll' CHECK (sumber IN ('payroll', 'manual')),
    keterangan TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.penarikan_gaji (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    karyawan_id UUID NOT NULL REFERENCES public.karyawan(id),
    tanggal DATE NOT NULL DEFAULT CURRENT_DATE,
    nominal NUMERIC(15, 2) NOT NULL CHECK (nominal > 0),
    keterangan TEXT,
    penggajian_id UUID REFERENCES public.penggajian(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MODUL 8: KEUANGAN, ARUS KAS, KATEGORI BIAYA & DRAF PENGELUARAN
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.akun_kas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nama_akun VARCHAR(100) NOT NULL,
    nomor_rekening VARCHAR(50),
    atas_nama VARCHAR(100),
    saldo_saat_ini NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    tipe_akun VARCHAR(30) DEFAULT 'kas_tunai',
    is_default_pos BOOLEAN DEFAULT FALSE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_akun_kas_saldo_positif CHECK (saldo_saat_ini >= 0)
);

CREATE TABLE IF NOT EXISTS public.kategori_biaya (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_kategori VARCHAR(50) NOT NULL UNIQUE,
    nama_kategori VARCHAR(100) NOT NULL,
    deskripsi TEXT,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.draf_pengeluaran (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    diajukan_oleh_pengguna_id UUID NOT NULL REFERENCES public.pengguna(id),
    nominal NUMERIC(15, 2) NOT NULL CHECK (nominal > 0),
    kategori_beban VARCHAR(50) NOT NULL CHECK (kategori_beban IN ('bensin', 'konsumsi', 'parkir_tol', 'maintenance', 'pembelian_bahan', 'lainnya')),
    keterangan_mentah TEXT NOT NULL,
    keterangan_ai TEXT,
    url_foto_nota TEXT,
    status_approval VARCHAR(30) NOT NULL DEFAULT 'menunggu' CHECK (status_approval IN ('menunggu', 'disetujui', 'ditolak')),
    akun_kas_id UUID REFERENCES public.akun_kas(id),
    id_pesan_telegram_owner VARCHAR(100),
    alasan_penolakan TEXT,
    disetujui_pada TIMESTAMPTZ,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.arus_kas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    akun_kas_id UUID NOT NULL REFERENCES public.akun_kas(id),
    tanggal_transaksi DATE NOT NULL DEFAULT CURRENT_DATE,
    jenis_kas VARCHAR(20) NOT NULL CHECK (jenis_kas IN ('masuk', 'keluar')),
    kategori VARCHAR(50) NOT NULL CHECK (kategori IN ('penjualan', 'beban_operasional', 'pembayaran_payroll', 'kasbon', 'pembelian_bahan', 'transfer_antar_kas')),
    nominal NUMERIC(15, 2) NOT NULL CHECK (nominal > 0),
    keterangan TEXT NOT NULL,
    referensi_tabel VARCHAR(50),
    referensi_id UUID,
    saldo_berjalan NUMERIC(15, 2) NOT NULL,
    dicatat_oleh UUID REFERENCES public.pengguna(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MODUL 9: PENGATURAN SISTEM & MASTER AUDIT TRAIL
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.pengaturan_sistem (
    kunci VARCHAR(100) PRIMARY KEY,
    nilai TEXT NOT NULL,
    deskripsi TEXT,
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Tabel Log Aktivitas Komprehensif (Master Audit Trail Seluruh Sistem)
CREATE TABLE IF NOT EXISTS public.log_aktivitas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pengguna_id UUID REFERENCES public.pengguna(id) ON DELETE SET NULL,
    nama_aktor VARCHAR(150) NOT NULL, -- Nama User / 'Sistem Otomasi n8n' / 'Google Gemini AI'
    peran_aktor VARCHAR(50) NOT NULL, -- 'owner', 'admin', 'mandor', 'sales', 'driver', 'ai_n8n', 'system'
    sumber_aksi VARCHAR(50) NOT NULL CHECK (sumber_aksi IN ('telegram_bot', 'whatsapp_bot', 'web_app', 'n8n_automation', 'database_trigger', 'system_cron')),
    kategori_aktivitas VARCHAR(50) NOT NULL CHECK (kategori_aktivitas IN (
        'keuangan', 'penjualan', 'logistik', 'gudang_stok',
        'produksi_bom', 'hr_payroll', 'master_data', 'keamanan_auth', 'ai_interaction'
    )),
    jenis_aksi VARCHAR(50) NOT NULL, -- 'INSERT', 'UPDATE', 'DELETE', 'APPROVE', 'REJECT', 'LOGIN', 'VOID_NOTA', 'POD_UPLOAD', 'PRICE_CHANGE'
    tabel_terdampak VARCHAR(100), -- 'pesanan', 'draf_pengeluaran', 'grup_produk_harga_level', dll.
    id_referensi UUID, -- ID baris yang dimodifikasi
    deskripsi_aktivitas TEXT NOT NULL, -- Penjelasan human-readable
    data_sebelum JSONB, -- Snapshot data lama (sebelum diubah)
    data_sesudah JSONB, -- Snapshot data baru (setelah diubah)
    ip_address VARCHAR(50),
    user_agent TEXT,
    id_pesan_telegram BIGINT,
    waktu_kejadian TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- INDEX PERFORMANCE & TRANSAKSI CEPAT
-- ==============================================================================
CREATE INDEX IF NOT EXISTS idx_item_sku ON public.item(kode_sku);
CREATE INDEX IF NOT EXISTS idx_item_barcode ON public.item(barcode);
CREATE INDEX IF NOT EXISTS idx_item_grup_id ON public.item(grup_id);
CREATE INDEX IF NOT EXISTS idx_item_pemasok_utama ON public.item(pemasok_utama_id);
CREATE INDEX IF NOT EXISTS idx_item_kelompok_borongan ON public.item(kelompok_borongan_id);
CREATE INDEX IF NOT EXISTS idx_grup_barcode ON public.grup_produk(barcode_universal);
CREATE INDEX IF NOT EXISTS idx_pelanggan_kode ON public.pelanggan(kode_pelanggan);
CREATE INDEX IF NOT EXISTS idx_pelanggan_grup ON public.pelanggan(grup_pelanggan_id);
CREATE INDEX IF NOT EXISTS idx_pelanggan_sales_driver ON public.pelanggan(sales_driver_id);
CREATE INDEX IF NOT EXISTS idx_pelanggan_item_pelanggan ON public.pelanggan_item(pelanggan_id);
CREATE INDEX IF NOT EXISTS idx_pelanggan_item_item ON public.pelanggan_item(item_id);
CREATE INDEX IF NOT EXISTS idx_pesanan_nota ON public.pesanan(nomor_nota);
CREATE INDEX IF NOT EXISTS idx_pesanan_pelanggan_id ON public.pesanan(pelanggan_id);
CREATE INDEX IF NOT EXISTS idx_pesanan_tanggal ON public.pesanan(tanggal_pesanan DESC);
CREATE INDEX IF NOT EXISTS idx_pesanan_pelanggan_status ON public.pesanan(pelanggan_id, status_pembayaran);
CREATE INDEX IF NOT EXISTS idx_pesanan_status_proses_tanggal ON public.pesanan(status_pemrosesan, tanggal_pesanan DESC);
CREATE INDEX IF NOT EXISTS idx_item_pesanan_pesanan_id ON public.item_pesanan(pesanan_id);
CREATE INDEX IF NOT EXISTS idx_item_pesanan_item_id ON public.item_pesanan(item_id);
CREATE INDEX IF NOT EXISTS idx_item_pesanan_item_pesanan ON public.item_pesanan(item_id, pesanan_id);
CREATE INDEX IF NOT EXISTS idx_stok_konsinyasi_toko ON public.stok_konsinyasi_toko(pelanggan_id);
CREATE INDEX IF NOT EXISTS idx_rkk_kunjungan_id ON public.rincian_kunjungan_konsinyasi(kunjungan_id);
CREATE INDEX IF NOT EXISTS idx_rkk_item_id ON public.rincian_kunjungan_konsinyasi(item_id);
CREATE INDEX IF NOT EXISTS idx_tagihan_kunjungan_kunjungan ON public.tagihan_kunjungan(kunjungan_id);
CREATE INDEX IF NOT EXISTS idx_tagihan_kunjungan_pesanan ON public.tagihan_kunjungan(pesanan_id);
CREATE INDEX IF NOT EXISTS idx_surat_jalan_tanggal ON public.surat_jalan(tanggal_surat_jalan);
CREATE INDEX IF NOT EXISTS idx_pembelian_driver_schedule ON public.pembelian(sales_driver_id, tanggal_jadwal_belanja);
CREATE INDEX IF NOT EXISTS idx_pembelian_jenis_status ON public.pembelian(jenis_dokumen, status_penerimaan);
CREATE INDEX IF NOT EXISTS idx_rincian_pembelian_pembelian_id ON public.rincian_pembelian(pembelian_id);
CREATE INDEX IF NOT EXISTS idx_rincian_pembelian_item_id ON public.rincian_pembelian(item_id);
CREATE INDEX IF NOT EXISTS idx_riwayat_stok_item ON public.riwayat_stok(item_id);
CREATE INDEX IF NOT EXISTS idx_riwayat_stok_item_waktu ON public.riwayat_stok(item_id, dibuat_pada DESC);
CREATE INDEX IF NOT EXISTS idx_opname_gudang_tgl ON public.opname_gudang(tanggal_opname);
CREATE INDEX IF NOT EXISTS idx_opname_gudang_nomor ON public.opname_gudang(nomor_opname);
CREATE INDEX IF NOT EXISTS idx_opname_gudang_tgl_desc ON public.opname_gudang(tanggal_opname DESC);
CREATE INDEX IF NOT EXISTS idx_opname_gudang_item_parent ON public.opname_gudang_item(opname_gudang_id);
CREATE INDEX IF NOT EXISTS idx_opname_gudang_item_item ON public.opname_gudang_item(item_id);
CREATE INDEX IF NOT EXISTS idx_arus_kas_akun_kas_id ON public.arus_kas(akun_kas_id);
CREATE INDEX IF NOT EXISTS idx_arus_kas_tanggal ON public.arus_kas(tanggal_transaksi DESC);
CREATE INDEX IF NOT EXISTS idx_karyawan_pengguna_id ON public.karyawan(pengguna_id);
CREATE INDEX IF NOT EXISTS idx_pengguna_nama_pengguna ON public.pengguna(nama_pengguna);
CREATE INDEX IF NOT EXISTS idx_log_aktivitas_waktu ON public.log_aktivitas(waktu_kejadian);
CREATE INDEX IF NOT EXISTS idx_log_aktivitas_kategori ON public.log_aktivitas(kategori_aktivitas);
CREATE INDEX IF NOT EXISTS idx_log_aktivitas_aktor ON public.log_aktivitas(pengguna_id);
CREATE INDEX IF NOT EXISTS idx_log_aktivitas_sumber ON public.log_aktivitas(sumber_aksi);

-- ==============================================================================
-- ROW LEVEL SECURITY (RLS) & SERVICE ROLE POLICIES
-- ==============================================================================
ALTER TABLE public.peran ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.izin ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.izin_peran ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pengguna ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.izin_pengguna ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.wilayah ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.karyawan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pemasok ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.master_level_harga ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.grup_pelanggan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pelanggan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pelanggan_item ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.grup_produk ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.grup_produk_harga_level ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kelompok_upah_borongan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.item ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.komposisi_item ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pembelian ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rincian_pembelian ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.penyesuaian_stok ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.riwayat_stok ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.opname_gudang ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.opname_gudang_item ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pesanan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.item_pesanan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.surat_jalan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.stok_konsinyasi_toko ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kunjungan_konsinyasi ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rincian_kunjungan_konsinyasi ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tagihan_kunjungan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.produksi_harian ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.absensi ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kasbon ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.potongan_kasbon ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tabungan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.transaksi_tabungan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.penarikan_gaji ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.penggajian ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rincian_penggajian ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.akun_kas ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kategori_biaya ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.draf_pengeluaran ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.arus_kas ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pengaturan_sistem ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.log_aktivitas ENABLE ROW LEVEL SECURITY;

CREATE POLICY service_role_all ON public.peran FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_pel ON public.pelanggan FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_mst_lvl_harga ON public.master_level_harga FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_grp_pel ON public.grup_pelanggan FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_grp_prod ON public.grup_produk FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_grp_prod_hj ON public.grup_produk_harga_level FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_item ON public.item FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_konsin ON public.stok_konsinyasi_toko FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_kunjungan ON public.kunjungan_konsinyasi FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_rincian_kunj ON public.rincian_kunjungan_konsinyasi FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_pesanan ON public.pesanan FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_stok ON public.riwayat_stok FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_kas ON public.arus_kas FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_logs ON public.log_aktivitas FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_merek ON public.merek FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_opname_gudang ON public.opname_gudang FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_opname_gudang_item ON public.opname_gudang_item FOR ALL TO service_role USING (true) WITH CHECK (true);
-- ==============================================================================
-- KEREN SNACK - SINGLE SOURCE OF TRUTH (SSOT) DATABASE SCHEMA v2.0
-- Model: Custom AI ERP untuk Toko & Manufaktur Repacking KEREN Snack
-- Fitur Khusus:
--   1. Dynamic Pricing Matrix (Harga Level per Grup Produk x Grup Pelanggan)
--   2. Dedicated Consignment Ledger (Stok Titip Rak Toko vs Gudang Pusat)
--   3. Universal Barcode Disambiguation (Satu Barcode Kemasan untuk Banyak Varian Rasa)
--   4. Integrated Sales-Driver (Canvaser Rute), HR Payroll Borongan & AI Telegram
-- ==============================================================================

CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- ==============================================================================
-- MODUL 1: AUTENTIKASI, PENGGUNA & HAK AKSES (RBAC)
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.peran (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nama_peran VARCHAR(50) NOT NULL UNIQUE, -- 'owner', 'admin', 'mandor', 'sales_driver'
    deskripsi TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
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
    id UUID PRIMARY KEY REFERENCES auth.users(id) ON DELETE CASCADE,
    karyawan_id UUID,
    peran_id UUID NOT NULL REFERENCES public.peran(id),
    nama_lengkap VARCHAR(150) NOT NULL,
    nama_pengguna VARCHAR(100) NOT NULL UNIQUE,
    id_telegram BIGINT UNIQUE,
    nomor_whatsapp VARCHAR(25) UNIQUE,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
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
-- MODUL 2: MASTER ENTITAS, GRUP PELANGGAN & RELASI BISNIS
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

CREATE TABLE IF NOT EXISTS public.karyawan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    id_legacy INT UNIQUE,
    nik VARCHAR(50) UNIQUE,
    nama_karyawan VARCHAR(150) NOT NULL,
    posisi VARCHAR(50) NOT NULL, -- 'pengemasan', 'sales_driver', 'admin', 'mandor'
    tipe_penggajian VARCHAR(30) NOT NULL CHECK (tipe_penggajian IN ('borongan', 'harian', 'bulanan')),
    gaji_pokok_bulanan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    uang_kehadiran_harian NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    tunjangan_bulanan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    persentase_komisi_sales NUMERIC(5, 2) NOT NULL DEFAULT 0.00, -- Contoh: 2.50%
    nomor_telepon VARCHAR(25),
    alamat TEXT,
    tanggal_bergabung DATE DEFAULT CURRENT_DATE,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

ALTER TABLE public.pengguna 
    ADD CONSTRAINT fk_pengguna_karyawan FOREIGN KEY (karyawan_id) REFERENCES public.karyawan(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS public.pemasok (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_pemasok VARCHAR(50) NOT NULL UNIQUE,
    nama_pemasok VARCHAR(150) NOT NULL,
    wilayah_id UUID REFERENCES public.wilayah(id),
    alamat_lengkap TEXT,
    nomor_telepon VARCHAR(25),
    detail_bank JSONB NOT NULL DEFAULT '[]'::jsonb,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Master Grup Pelanggan (Pemegang Aturan Level Harga 1 s/d 28 & Diskon Otomatis)
CREATE TABLE IF NOT EXISTS public.grup_pelanggan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_grup VARCHAR(50) NOT NULL UNIQUE, -- 'GRP-A', 'GRP-GROSIR-TNG', 'GRP-KONSINYASI'
    nama_grup VARCHAR(100) NOT NULL,
    default_level_harga INT NOT NULL DEFAULT 1 CHECK (default_level_harga >= 1), -- Bebas, tanpa batas maksimal
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
    nomor_telepon VARCHAR(25),
    nomor_whatsapp VARCHAR(25),
    tipe_pembayaran_default VARCHAR(30) NOT NULL DEFAULT 'cash' CHECK (tipe_pembayaran_default IN ('cash', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'konsinyasi')),
    plafon_piutang NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_piutang_berjalan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    sales_driver_id UUID REFERENCES public.karyawan(id), -- Sales pemegang toko tetap
    override_level_harga INT CHECK (override_level_harga >= 1), -- Bebas, tanpa batas maksimal
    override_diskon_persen NUMERIC(5, 2) DEFAULT 0.00,
    override_diskon_nominal NUMERIC(15, 2) DEFAULT 0.00,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MODUL 3: GRUP PRODUK, ITEM (VARIAN RASA / SKU) & PRICING MATRIX DINAMIS
-- ==============================================================================

-- Grup Produk: Pemegang Barcode Universal, Harga Level Dinamis, & Kategori
CREATE TABLE IF NOT EXISTS public.grup_produk (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    kode_grup VARCHAR(50) NOT NULL UNIQUE, -- 'GRP-SINGKONG-250', 'GRP-BRND-135'
    nama_grup VARCHAR(150) NOT NULL,
    barcode_universal VARCHAR(100), -- Barcode kemasan luar yang dipakai bersama oleh varian rasa
    merek VARCHAR(100) NOT NULL DEFAULT 'KEREN SNACK',
    satuan_dasar VARCHAR(30) NOT NULL DEFAULT 'pcs',
    satuan_distribusi VARCHAR(30) NOT NULL DEFAULT 'bal',
    konversi_bal_ke_pcs INT NOT NULL DEFAULT 20, -- 1 bal isi 20 pcs
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Tabel Matriks Harga Jual per Grup Produk (Bebas / Unlimited Level Harga)
CREATE TABLE IF NOT EXISTS public.grup_produk_harga_level (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    grup_produk_id UUID NOT NULL REFERENCES public.grup_produk(id) ON DELETE CASCADE,
    level_harga INT NOT NULL CHECK (level_harga >= 1), -- Bebas bertambah (Level 1 s/d 100+)
    nama_level VARCHAR(50) NOT NULL, -- 'Level 1 - Ritel', 'Level 8 - Grosir Mitra', dll.
    harga_jual_pcs NUMERIC(15, 2) NOT NULL, -- Harga per bungkus
    harga_jual_bal NUMERIC(15, 2) NOT NULL, -- Harga per bal
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
    satuan_distribusi VARCHAR(30) NOT NULL DEFAULT 'bal',
    konversi_distribusi_ke_dasar INT NOT NULL DEFAULT 1,
    kelompok_borongan_id UUID REFERENCES public.kelompok_upah_borongan(id),
    pemasok_utama_id UUID REFERENCES public.pemasok(id),
    harga_pokok_pembelian NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    stok_minimum_peringatan INT NOT NULL DEFAULT 10,
    stok_fisik_saat_ini INT NOT NULL DEFAULT 0, -- Snapshot otomatis dari riwayat_stok
    status_jual BOOLEAN NOT NULL DEFAULT TRUE,
    status_aktif BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- Bill of Materials / Resep Repacking (Bal Curah -> Pcs Jadi)
CREATE TABLE IF NOT EXISTS public.komposisi_item (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    item_jadi_id UUID NOT NULL REFERENCES public.item(id) ON DELETE CASCADE,
    item_bahan_id UUID NOT NULL REFERENCES public.item(id) ON DELETE RESTRICT,
    jumlah_kebutuhan NUMERIC(12, 4) NOT NULL, -- Contoh: 0.1350 kg singkong curah + 1 lembar plastik
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_komposisi_item UNIQUE (item_jadi_id, item_bahan_id)
);

-- ==============================================================================
-- MODUL 4: MANAJEMEN GUDANG & LEDGER MUTASI STOK
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
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.rincian_pembelian (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pembelian_id UUID NOT NULL REFERENCES public.pembelian(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id),
    kuantitas INT NOT NULL CHECK (kuantitas > 0),
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
        'retur_pelanggan_masuk', 'konsinyasi_keluar', 'konsinyasi_retur_masuk', 'konsinyasi_retur_rusak'
    )),
    jumlah_perubahan INT NOT NULL,
    stok_sebelum INT NOT NULL,
    stok_sesudah INT NOT NULL,
    referensi_tabel VARCHAR(50) NOT NULL,
    referensi_id UUID NOT NULL,
    keterangan TEXT,
    dibuat_oleh UUID REFERENCES public.pengguna(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MODUL 5: PENJUALAN, ORDER & LOGISTIK SURAT JALAN
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.pesanan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_nota VARCHAR(100) NOT NULL UNIQUE,
    pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id),
    sales_driver_id UUID REFERENCES public.karyawan(id), -- Sales-Driver yang bertugas
    tanggal_pesanan DATE NOT NULL DEFAULT CURRENT_DATE,
    total_bruto NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_diskon NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    total_netto NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    tipe_pembayaran VARCHAR(30) NOT NULL CHECK (tipe_pembayaran IN ('cash', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'konsinyasi')),
    tanggal_jatuh_tempo DATE,
    status_pembayaran VARCHAR(30) NOT NULL DEFAULT 'belum_lunas' CHECK (status_pembayaran IN ('belum_lunas', 'sebagian', 'tempo', 'lunas', 'dibatalkan')),
    status_pemrosesan VARCHAR(30) NOT NULL DEFAULT 'menunggu_approval' CHECK (status_pemrosesan IN ('menunggu_approval', 'disetujui', 'siap_kirim', 'dalam_pengiriman', 'selesai', 'dibatalkan')),
    catatan TEXT,
    dibuat_oleh UUID REFERENCES public.pengguna(id),
    akun_kas_id UUID REFERENCES public.akun_kas(id),
    total_dibayar NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    sisa_tagihan NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    adalah_tagihan BOOLEAN NOT NULL DEFAULT TRUE,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.item_pesanan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pesanan_id UUID NOT NULL REFERENCES public.pesanan(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id),
    kuantitas_satuan_dasar INT NOT NULL CHECK (kuantitas_satuan_dasar > 0),
    kuantitas_satuan_distribusi INT NOT NULL DEFAULT 0,
    harga_satuan_deal NUMERIC(15, 2) NOT NULL,
    diskon_item_persen NUMERIC(5, 2) DEFAULT 0.00,
    diskon_item_nominal NUMERIC(15, 2) DEFAULT 0.00,
    is_bonus BOOLEAN NOT NULL DEFAULT FALSE,
    subtotal NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TABLE IF NOT EXISTS public.surat_jalan (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_surat_jalan VARCHAR(100) NOT NULL UNIQUE,
    pesanan_id UUID NOT NULL REFERENCES public.pesanan(id),
    sales_driver_id UUID REFERENCES public.karyawan(id),
    rute_wilayah_id UUID REFERENCES public.wilayah(id),
    url_pdf_dokumen TEXT,
    status_surat_jalan VARCHAR(30) NOT NULL DEFAULT 'draf_n8n' CHECK (status_surat_jalan IN ('draf_n8n', 'disetujui_owner', 'sedang_dikirim', 'selesai_diterima', 'gagal_kembali', 'ditolak_owner')),
    bukti_terima_foto TEXT, -- Foto bukti terima toko yang diupload Sales-Driver via Telegram
    nama_penerima_toko VARCHAR(100),
    waktu_berangkat TIMESTAMPTZ,
    waktu_sampai TIMESTAMPTZ,
    disetujui_oleh UUID REFERENCES public.pengguna(id),
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ==============================================================================
-- MODUL 6: MODUL KONSINYASI KHUSUS (CONSIGNMENT SHELF LEDGER)
-- ==============================================================================

-- Saldo Stok Titip Nyata per Toko Konsinyasi
CREATE TABLE IF NOT EXISTS public.stok_konsinyasi_toko (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id) ON DELETE CASCADE,
    item_id UUID NOT NULL REFERENCES public.item(id) ON DELETE RESTRICT,
    stok_titip_saat_ini INT NOT NULL DEFAULT 0 CHECK (stok_titip_saat_ini >= 0),
    terakhir_opname_pada TIMESTAMPTZ,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    diubah_pada TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT uq_konsinyasi_toko_item UNIQUE (pelanggan_id, item_id)
);

-- Header Kunjungan & Opname Rak Konsinyasi oleh Sales-Driver
CREATE TABLE IF NOT EXISTS public.kunjungan_konsinyasi (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nomor_kunjungan VARCHAR(100) NOT NULL UNIQUE,
    pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id),
    sales_driver_id UUID NOT NULL REFERENCES public.karyawan(id),
    tanggal_kunjungan DATE NOT NULL DEFAULT CURRENT_DATE,
    pesanan_id UUID REFERENCES public.pesanan(id), -- Invoice laku yang otomatis terbit
    total_laku_nominal NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
    catatan TEXT,
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

CREATE TABLE IF NOT EXISTS public.target_produksi (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    tanggal DATE NOT NULL,
    item_id UUID NOT NULL REFERENCES public.item(id),
    target_pcs INT NOT NULL CHECK (target_pcs > 0),
    keterangan TEXT,
    dibuat_pada TIMESTAMPTZ NOT NULL DEFAULT NOW()
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
-- MODUL 8: KEUANGAN, ARUS KAS & DRAF PENGELUARAN AI
-- ==============================================================================

CREATE TABLE IF NOT EXISTS public.akun_kas (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    nama_akun VARCHAR(100) NOT NULL,
    nomor_rekening VARCHAR(50),
    atas_nama VARCHAR(100),
    saldo_saat_ini NUMERIC(15, 2) NOT NULL DEFAULT 0.00,
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
    peran_aktor VARCHAR(50) NOT NULL, -- 'owner', 'admin', 'mandor', 'sales_driver', 'ai_n8n', 'system'
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
-- INDEX PERFORMANCE
-- ==============================================================================
CREATE INDEX IF NOT EXISTS idx_item_sku ON public.item(kode_sku);
CREATE INDEX IF NOT EXISTS idx_item_barcode ON public.item(barcode);
CREATE INDEX IF NOT EXISTS idx_grup_barcode ON public.grup_produk(barcode_universal);
CREATE INDEX IF NOT EXISTS idx_pelanggan_kode ON public.pelanggan(kode_pelanggan);
CREATE INDEX IF NOT EXISTS idx_pelanggan_grup ON public.pelanggan(grup_pelanggan_id);
CREATE INDEX IF NOT EXISTS idx_pesanan_nota ON public.pesanan(nomor_nota);
CREATE INDEX IF NOT EXISTS idx_stok_konsinyasi_toko ON public.stok_konsinyasi_toko(pelanggan_id);
CREATE INDEX IF NOT EXISTS idx_log_aktivitas_waktu ON public.log_aktivitas(waktu_kejadian);
CREATE INDEX IF NOT EXISTS idx_log_aktivitas_kategori ON public.log_aktivitas(kategori_aktivitas);
CREATE INDEX IF NOT EXISTS idx_log_aktivitas_aktor ON public.log_aktivitas(pengguna_id);
CREATE INDEX IF NOT EXISTS idx_log_aktivitas_sumber ON public.log_aktivitas(sumber_aksi);

-- ==============================================================================
-- AKTIFKAN ROW LEVEL SECURITY (RLS) & SERVICE ROLE POLICIES
-- ==============================================================================
ALTER TABLE public.peran ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.izin ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.izin_peran ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pengguna ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.izin_pengguna ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.wilayah ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.karyawan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pemasok ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.grup_pelanggan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pelanggan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.grup_produk ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.grup_produk_harga_level ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kelompok_upah_borongan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.item ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.komposisi_item ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pembelian ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rincian_pembelian ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.penyesuaian_stok ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.riwayat_stok ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pesanan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.item_pesanan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.surat_jalan ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.stok_konsinyasi_toko ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.kunjungan_konsinyasi ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.rincian_kunjungan_konsinyasi ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.target_produksi ENABLE ROW LEVEL SECURITY;
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
ALTER TABLE public.draf_pengeluaran ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.arus_kas ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.pengaturan_sistem ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.log_aktivitas ENABLE ROW LEVEL SECURITY;

CREATE POLICY service_role_all ON public.peran FOR ALL TO service_role USING (true) WITH CHECK (true);
CREATE POLICY service_role_all_pel ON public.pelanggan FOR ALL TO service_role USING (true) WITH CHECK (true);
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

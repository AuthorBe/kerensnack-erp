-- database/28_migration_fase3_relasi_master.sql
-- Migrasi Fase 3: Proteksi Relasi Master Data & Operasional Lapangan
-- 1. Mengubah FK stok_konsinyasi_toko dari CASCADE ke RESTRICT demi proteksi aset fisik konsinyasi
-- 2. Menambahkan CHECK constraint saldo non-negatif pada akun_kas non-kredit/giro

-- 1. Alter Foreign Key stok_konsinyasi_toko (ON DELETE RESTRICT)
ALTER TABLE public.stok_konsinyasi_toko
    DROP CONSTRAINT IF EXISTS stok_konsinyasi_toko_pelanggan_id_fkey;

ALTER TABLE public.stok_konsinyasi_toko
    ADD CONSTRAINT stok_konsinyasi_toko_pelanggan_id_fkey
    FOREIGN KEY (pelanggan_id) REFERENCES public.pelanggan(id) ON DELETE RESTRICT;

-- 2. Add CHECK constraint pada akun_kas (Mencegah Saldo Negatif / Overdraft tidak sah)
ALTER TABLE public.akun_kas
    DROP CONSTRAINT IF EXISTS chk_akun_kas_saldo_positif;

ALTER TABLE public.akun_kas
    ADD CONSTRAINT chk_akun_kas_saldo_positif
    CHECK (tipe_akun IN ('kartu_kredit', 'giro') OR saldo_saat_ini >= 0);

-- database/29_migration_fase4_master_polish.sql
-- FASE 4: Ergonomi UI/UX Master Data & Data Hygiene (P2/P3 - Polish)
-- Dibuat: 2026-09-15

-- 1. Bersihkan kunci usang 'nama_toko' dari public.pengaturan_sistem
-- Identitas usaha resmi telah dikelola secara terpusat melalui CompanySetting ('perusahaan_nama', dll.)
DELETE FROM public.pengaturan_sistem WHERE kunci = 'nama_toko';

-- 2. Pastikan nilai default profil perusahaan tersedia jika belum ada
INSERT INTO public.pengaturan_sistem (kunci, nilai, deskripsi) VALUES
('perusahaan_nama', 'KEREN SNACK INDONESIA', 'Nama resmi usaha / brand pada kop dokumen'),
('perusahaan_tagline', 'Produsen & Distributor Aneka Makanan Ringan Berkualitas', 'Slogan / sub-judul usaha pada kop'),
('perusahaan_alamat', 'Jl. Industri Snack No. 88, Jawa Barat', 'Alamat operasional kantor / gudang'),
('perusahaan_telepon', '0812-3456-7890', 'Nomor telepon / WhatsApp operasional'),
('perusahaan_email', 'admin@kerensnack.com', 'Email resmi perusahaan untuk korespondensi'),
('perusahaan_website', 'www.kerensnack.com', 'Alamat website resmi perusahaan'),
('perusahaan_catatan_faktur', 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa persetujuan tertulis.', 'Catatan kaki / syarat ketentuan pada faktur penjualan'),
('perusahaan_nama_bank', 'BCA', 'Nama bank rekening penerima pembayaran'),
('perusahaan_nomor_rekening', '8820-123-4567', 'Nomor rekening bank penerima'),
('perusahaan_atas_nama_bank', 'KEREN SNACK INDONESIA', 'Nama pemilik rekening bank')
ON CONFLICT (kunci) DO NOTHING;

-- 3. Catat jejak migrasi
COMMENT ON TABLE public.pemasok IS 'Master vendor supplier bahan baku curah & kemasan (relational bank fields prioritized)';
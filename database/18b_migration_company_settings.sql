-- ============================================================================
-- MIGRATION 18: PENGATURAN INFORMASI PERUSAHAAN (COMPANY SETTINGS)
-- Database: PostgreSQL Supabase (KEREN SNACK ERP)
-- ============================================================================

-- 1. Pendaftaran Izin Baru di Master Izin
INSERT INTO public.izin (kode_izin, nama_izin, grup_izin, deskripsi)
VALUES (
    'settings.company_manage',
    'Kelola Profil & Info Perusahaan',
    'Hak Akses & Sistem',
    'Mengubah nama usaha, slogan, alamat operasional, kontak, rekening bank, dan catatan kop faktur/surat jalan'
)
ON CONFLICT (kode_izin) DO UPDATE SET
    nama_izin = EXCLUDED.nama_izin,
    grup_izin = EXCLUDED.grup_izin,
    deskripsi = EXCLUDED.deskripsi;

-- 2. Berikan Izin ke Role Owner dan Admin (Developer universal bypass)
DO $$
DECLARE
    v_izin_id UUID;
    v_owner_id UUID;
    v_admin_id UUID;
BEGIN
    SELECT id INTO v_izin_id FROM public.izin WHERE kode_izin = 'settings.company_manage';
    SELECT id INTO v_owner_id FROM public.peran WHERE nama_peran = 'owner';
    SELECT id INTO v_admin_id FROM public.peran WHERE nama_peran = 'admin';

    IF v_izin_id IS NOT NULL THEN
        IF v_owner_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_owner_id, v_izin_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;

        IF v_admin_id IS NOT NULL THEN
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_admin_id, v_izin_id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END IF;
    END IF;
END $$;

-- 3. Inisialisasi Data Profil Perusahaan ke public.pengaturan_sistem
INSERT INTO public.pengaturan_sistem (kunci, nilai, deskripsi, diubah_pada)
VALUES 
    ('perusahaan_nama', 'KEREN SNACK INDONESIA', 'Nama resmi usaha / brand pada kop dokumen', NOW()),
    ('perusahaan_tagline', 'Produsen & Distributor Aneka Makanan Ringan Berkualitas', 'Slogan / sub-judul usaha pada kop', NOW()),
    ('perusahaan_alamat', 'Jl. Industri Snack No. 88, Jawa Barat', 'Alamat operasional kantor / gudang', NOW()),
    ('perusahaan_telepon', '0812-3456-7890', 'Nomor telepon / WhatsApp operasional', NOW()),
    ('perusahaan_email', 'admin@kerensnack.com', 'Email resmi perusahaan untuk korespondensi', NOW()),
    ('perusahaan_website', 'www.kerensnack.com', 'Alamat website resmi perusahaan', NOW()),
    ('perusahaan_catatan_faktur', 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa persetujuan tertulis.', 'Catatan kaki / syarat ketentuan pada faktur penjualan', NOW()),
    ('perusahaan_nama_bank', 'BCA', 'Nama bank rekening penerima pembayaran', NOW()),
    ('perusahaan_nomor_rekening', '8820-123-4567', 'Nomor rekening bank penerima', NOW()),
    ('perusahaan_atas_nama_bank', 'KEREN SNACK INDONESIA', 'Nama pemilik rekening bank', NOW()),
    ('perusahaan_logo_url', '', 'URL atau path file logo resmi perusahaan', NOW())
ON CONFLICT (kunci) DO UPDATE SET
    deskripsi = EXCLUDED.deskripsi;

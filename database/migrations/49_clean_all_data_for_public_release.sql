-- ==============================================================================
-- MIGRATION 49: PEMBERSIHAN TOTAL DATA UNTUK PUBLIC RELEASE
-- Tujuan: Hapus SEMUA data operasional & master data bisnis.
--         Sisakan HANYA: struktur skema, peran/izin, master_level_harga,
--         pengaturan_sistem (dikosongkan), akun kas default, dan 1 akun developer.
-- Catatan: Trigger trg_guard_developer_account melindungi akun developer.
--          Script ini menghapus semua user KECUALI developer.
-- Dijalankan di: Supabase PostgreSQL (SQL Editor / psql)
-- ==============================================================================

BEGIN;

-- ==============================================================================
-- FASE 0: BACKUP INFO DEVELOPER
-- ==============================================================================
DO $$
DECLARE
    v_dev_count INT;
BEGIN
    SELECT COUNT(*) INTO v_dev_count
    FROM public.pengguna p
    JOIN public.peran pr ON pr.id = p.peran_id
    WHERE pr.nama_peran = 'developer';

    IF v_dev_count = 0 THEN
        RAISE NOTICE 'PERINGATAN: Tidak ditemukan akun developer. Akan dibuat ulang di migration 50.';
    ELSE
        RAISE NOTICE 'OK: Ditemukan % akun developer — akan dilindungi selama proses pembersihan.', v_dev_count;
    END IF;
END $$;

-- ==============================================================================
-- FASE 1: HAPUS DATA TRANSAKSIONAL LEAF TABLES (tidak ada dependent)
-- ==============================================================================

TRUNCATE TABLE public.log_aktivitas RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.arus_kas RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.rincian_kunjungan_konsinyasi RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.tagihan_kunjungan RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.potongan_kasbon RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.transaksi_tabungan RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.penarikan_gaji RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.opname_gudang_item RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.rincian_pembelian RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.item_pesanan RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.penyesuaian_stok RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.rincian_penggajian RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.pelanggan_item RESTART IDENTITY CASCADE;

-- ==============================================================================
-- FASE 2: HAPUS DATA TRANSAKSIONAL PARENT TABLES
-- ==============================================================================

TRUNCATE TABLE public.kunjungan_konsinyasi RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.stok_konsinyasi_toko RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.surat_jalan RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.pesanan RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.riwayat_stok RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.opname_gudang RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.pembelian RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.produksi_harian RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.absensi RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.kasbon RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.tabungan RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.penggajian RESTART IDENTITY CASCADE;

-- ==============================================================================
-- FASE 3: HAPUS MASTER DATA OPERASIONAL BISNIS
-- ==============================================================================

TRUNCATE TABLE public.komposisi_item RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.item RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.grup_produk_harga_level RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.grup_produk RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.kelompok_upah_borongan RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.skema_komisi_sales RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.pelanggan RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.grup_pelanggan RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.pemasok RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.wilayah RESTART IDENTITY CASCADE;
TRUNCATE TABLE public.kategori_biaya RESTART IDENTITY CASCADE;

-- ==============================================================================
-- FASE 4: HAPUS AKUN KAS LAMA
-- ==============================================================================

TRUNCATE TABLE public.akun_kas RESTART IDENTITY CASCADE;

-- ==============================================================================
-- FASE 5: HAPUS KARYAWAN (profil penggajian)
-- ==============================================================================

TRUNCATE TABLE public.karyawan RESTART IDENTITY CASCADE;

-- ==============================================================================
-- FASE 6: HAPUS IZIN OVERRIDE PER PENGGUNA
-- ==============================================================================

TRUNCATE TABLE public.izin_pengguna RESTART IDENTITY CASCADE;

-- ==============================================================================
-- FASE 7: HAPUS SEMUA USER KECUALI DEVELOPER
-- Trigger trg_guard_developer_account akan RAISE EXCEPTION jika kita
-- mencoba DELETE pada akun developer — jadi kita aman DELETE non-developer saja.
-- ==============================================================================

DO $$
DECLARE
    v_dev_role_id UUID;
    v_deleted_count INT;
BEGIN
    SELECT id INTO v_dev_role_id FROM public.peran WHERE nama_peran = 'developer' LIMIT 1;

    IF v_dev_role_id IS NULL THEN
        -- Tidak ada peran developer — hapus semua user (dev akan dibuat di migration 50)
        DELETE FROM public.pengguna;
        GET DIAGNOSTICS v_deleted_count = ROW_COUNT;
        RAISE NOTICE 'Peran developer tidak ditemukan — seluruh % user dihapus.', v_deleted_count;
    ELSE
        -- Hapus hanya user non-developer (trigger melindungi yang ber-role developer)
        DELETE FROM public.pengguna WHERE peran_id IS DISTINCT FROM v_dev_role_id OR peran_id IS NULL;
        GET DIAGNOSTICS v_deleted_count = ROW_COUNT;
        RAISE NOTICE 'DONE: % akun non-developer dihapus.', v_deleted_count;
    END IF;
END $$;

-- ==============================================================================
-- FASE 8: RE-SEED AKUN KAS DEFAULT (2 akun standar bersih)
-- ==============================================================================

INSERT INTO public.akun_kas (id, nama_akun, nomor_rekening, atas_nama, saldo_saat_ini, tipe_akun, is_default_pos, status_aktif)
VALUES
    ('22222222-2222-2222-2222-222222222201', 'Kasir Utama Toko (Tunai)', '-', 'Kasir Toko',    0.00, 'kas_tunai',      TRUE,  TRUE),
    ('22222222-2222-2222-2222-222222222202', 'Rekening Bank Usaha',      '-', 'Pemilik Usaha', 0.00, 'rekening_bank',  FALSE, TRUE)
ON CONFLICT (id) DO UPDATE SET
    saldo_saat_ini = 0.00,
    status_aktif   = TRUE;

-- ==============================================================================
-- FASE 8b: RE-SEED GRUP PELANGGAN DEFAULT (diperlukan sistem POS & konsinyasi)
-- ==============================================================================

INSERT INTO public.grup_pelanggan (id, kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default)
VALUES
    ('44444444-4444-4444-4444-444444444401', 'GRP-UMUM-RITEL',  'Grup Ritel Standar',     1,  0.00, 0.00),
    ('44444444-4444-4444-4444-444444444402', 'GRP-MITRA-A',     'Grup Mitra Warung A',    8,  5.00, 0.00),
    ('44444444-4444-4444-4444-444444444403', 'GRP-GROSIR-B',    'Grup Grosir Pasar B',    12, 0.00, 500.00),
    ('44444444-4444-4444-4444-444444444404', 'GRP-KONSINYASI',  'Grup Toko Titip Jual',   5,  0.00, 0.00)
ON CONFLICT (kode_grup) DO NOTHING;

-- ==============================================================================
-- FASE 8c: RE-SEED PELANGGAN WALK-IN DEFAULT (wajib untuk sistem POS kasir)
-- ==============================================================================

INSERT INTO public.pelanggan (kode_pelanggan, nama_toko, grup_pelanggan_id, is_konsinyasi, alamat_lengkap, tipe_pembayaran_default, plafon_piutang)
VALUES ('CUST-001', 'Toko Umum / Walk-in Cash', '44444444-4444-4444-4444-444444444401', FALSE, 'Langsung / Walk-in', 'cash', 0.00)
ON CONFLICT (kode_pelanggan) DO UPDATE SET
    nama_toko               = EXCLUDED.nama_toko,
    grup_pelanggan_id       = EXCLUDED.grup_pelanggan_id,
    is_konsinyasi           = EXCLUDED.is_konsinyasi,
    alamat_lengkap          = EXCLUDED.alamat_lengkap,
    tipe_pembayaran_default = EXCLUDED.tipe_pembayaran_default,
    plafon_piutang          = EXCLUDED.plafon_piutang;

-- ==============================================================================
-- FASE 9: RESET PENGATURAN SISTEM (ganti data spesifik bisnis dengan placeholder)
-- ==============================================================================

DELETE FROM public.pengaturan_sistem WHERE kunci LIKE 'perusahaan_%';

INSERT INTO public.pengaturan_sistem (kunci, nilai, deskripsi, diubah_pada) VALUES
    ('jam_masuk_kerja',           '08:00',                              'Jam standar masuk kerja harian',                                               NOW()),
    ('komisi_sales_default',      '2.50',                               'Persentase default komisi penjualan sales (%)',                                 NOW()),
    ('versi_schema',              '1.0.0',                              'Versi skema database',                                                          NOW()),
    ('perusahaan_nama',           'Nama Perusahaan Anda',               'Nama resmi usaha / brand pada kop dokumen',                                     NOW()),
    ('perusahaan_tagline',        'Slogan Usaha Anda',                  'Slogan / sub-judul usaha pada kop',                                             NOW()),
    ('perusahaan_alamat',         'Alamat Operasional Anda',            'Alamat operasional kantor / gudang',                                            NOW()),
    ('perusahaan_telepon',        '08xx-xxxx-xxxx',                     'Nomor telepon / WhatsApp operasional',                                          NOW()),
    ('perusahaan_email',          'email@perusahaan.com',               'Email resmi perusahaan untuk korespondensi',                                     NOW()),
    ('perusahaan_website',        'www.perusahaan.com',                 'Alamat website resmi perusahaan',                                               NOW()),
    ('perusahaan_catatan_faktur', 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan tanpa persetujuan tertulis.', 'Catatan kaki / syarat ketentuan pada faktur penjualan', NOW()),
    ('perusahaan_nama_bank',      'Nama Bank',                          'Nama bank rekening penerima pembayaran',                                         NOW()),
    ('perusahaan_nomor_rekening', 'xxxx-xxxx-xxxx',                     'Nomor rekening bank penerima',                                                  NOW()),
    ('perusahaan_atas_nama_bank', 'Nama Pemilik Rekening',              'Nama pemilik rekening bank',                                                     NOW()),
    ('perusahaan_logo_url',       '',                                   'URL atau path file logo resmi perusahaan',                                       NOW())
ON CONFLICT (kunci) DO UPDATE SET
    nilai       = EXCLUDED.nilai,
    deskripsi   = EXCLUDED.deskripsi,
    diubah_pada = NOW();

-- ==============================================================================
-- FASE 10: VERIFIKASI AKHIR
-- ==============================================================================

DO $$
DECLARE
    v_user_count     INT;
    v_dev_count      INT;
    v_peran_count    INT;
    v_izin_count     INT;
    v_pesanan_count  INT;
    v_karyawan_count INT;
    v_produk_count   INT;
    v_akun_kas_count INT;
    v_log_count      INT;
    v_pelanggan_count INT;
BEGIN
    SELECT COUNT(*) INTO v_user_count     FROM public.pengguna;
    SELECT COUNT(*) INTO v_dev_count      FROM public.pengguna p JOIN public.peran pr ON pr.id = p.peran_id WHERE pr.nama_peran = 'developer';
    SELECT COUNT(*) INTO v_peran_count    FROM public.peran;
    SELECT COUNT(*) INTO v_izin_count     FROM public.izin;
    SELECT COUNT(*) INTO v_pesanan_count  FROM public.pesanan;
    SELECT COUNT(*) INTO v_karyawan_count FROM public.karyawan;
    SELECT COUNT(*) INTO v_produk_count   FROM public.item;
    SELECT COUNT(*) INTO v_akun_kas_count FROM public.akun_kas;
    SELECT COUNT(*) INTO v_log_count      FROM public.log_aktivitas;
    SELECT COUNT(*) INTO v_pelanggan_count FROM public.pelanggan;

    RAISE NOTICE '==============================================';
    RAISE NOTICE 'VERIFIKASI HASIL PEMBERSIHAN DATABASE:';
    RAISE NOTICE '  Total Pengguna     : % (developer: %)', v_user_count, v_dev_count;
    RAISE NOTICE '  Total Peran        : %', v_peran_count;
    RAISE NOTICE '  Total Izin         : %', v_izin_count;
    RAISE NOTICE '  Total Pesanan      : % (HARUS 0)', v_pesanan_count;
    RAISE NOTICE '  Total Karyawan     : % (HARUS 0)', v_karyawan_count;
    RAISE NOTICE '  Total Item/Produk  : % (HARUS 0)', v_produk_count;
    RAISE NOTICE '  Total Pelanggan    : % (HARUS 0)', v_pelanggan_count;
    RAISE NOTICE '  Total Akun Kas     : %', v_akun_kas_count;
    RAISE NOTICE '  Total Log Aktivitas: % (HARUS 0)', v_log_count;
    RAISE NOTICE '==============================================';

    -- Hard assertion
    IF v_pesanan_count > 0 THEN
        RAISE EXCEPTION 'ASSERTION GAGAL: Masih ada % pesanan tersisa!', v_pesanan_count;
    END IF;
    IF v_karyawan_count > 0 THEN
        RAISE EXCEPTION 'ASSERTION GAGAL: Masih ada % karyawan tersisa!', v_karyawan_count;
    END IF;
    IF v_produk_count > 0 THEN
        RAISE EXCEPTION 'ASSERTION GAGAL: Masih ada % item/produk tersisa!', v_produk_count;
    END IF;
    IF v_pelanggan_count > 0 THEN
        RAISE EXCEPTION 'ASSERTION GAGAL: Masih ada % pelanggan tersisa!', v_pelanggan_count;
    END IF;
    IF v_log_count > 0 THEN
        RAISE EXCEPTION 'ASSERTION GAGAL: Masih ada % log aktivitas tersisa!', v_log_count;
    END IF;

    RAISE NOTICE 'SUKSES: Database berhasil dibersihkan total untuk public release!';
END $$;

COMMIT;

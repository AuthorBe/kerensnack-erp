-- ==============================================================================
-- 42_cleanup_legacy_sales_commission.sql
-- Pembersihan Total Teknis Komisi Lama (Flat Statis) & Patenisasi Komisi Bertingkat
-- Menghapus kolom persentase_komisi_sales dari tabel karyawan, trigger terkait,
-- dan memperbarui view v_karyawan_info agar sinkron 100% dengan skema komisi bertingkat.
-- ==============================================================================

-- 1. Hapus trigger dan fungsi guard lama yang terkait dengan persentase_komisi_sales
DROP TRIGGER IF EXISTS trg_guard_karyawan_driver_no_commission ON public.karyawan;
DROP TRIGGER IF EXISTS trg_guard_pengguna_driver_reset_commission ON public.pengguna;
DROP FUNCTION IF EXISTS public.fn_guard_karyawan_driver_no_commission();
DROP FUNCTION IF EXISTS public.fn_guard_pengguna_driver_reset_commission();

-- 2. Perbarui view v_karyawan_info tanpa kolom persentase_komisi_sales
DROP VIEW IF EXISTS public.v_karyawan_info CASCADE;

CREATE VIEW public.v_karyawan_info AS
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
    p.nama_pengguna,
    k.tipe_penggajian,
    k.gaji_pokok_bulanan,
    k.uang_kehadiran_harian,
    k.tunjangan_bulanan,
    k.dibuat_pada,
    k.diubah_pada
FROM public.karyawan k
JOIN public.pengguna p ON k.pengguna_id = p.id;

-- 3. Hapus kolom persentase_komisi_sales dari tabel public.karyawan
ALTER TABLE public.karyawan DROP COLUMN IF EXISTS persentase_komisi_sales;

-- 4. Verifikasi dan pastikan Master Skema Komisi Sales Bertingkat (skema_komisi_sales) paten
CREATE INDEX IF NOT EXISTS idx_skema_komisi_urutan ON public.skema_komisi_sales (urutan ASC);
CREATE INDEX IF NOT EXISTS idx_skema_komisi_aktif ON public.skema_komisi_sales (status_aktif);

COMMENT ON TABLE public.skema_komisi_sales IS 'Master skema tier komisi sales bertingkat berdasarkan akumulasi omzet kas masuk bulanan (Paten)';

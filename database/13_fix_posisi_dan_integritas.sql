-- database/13_fix_posisi_dan_integritas.sql
-- Perbaikan data integritas pasca merge karyawan-pengguna
-- Dibuat: 2026-09-10

-- 1. Untuk pengguna yang punya akun login tapi belum punya posisi,
--    set posisi default berdasarkan role mereka.
--    Ini opsional tapi direkomendasikan untuk audit trail & profil lengkap.
UPDATE public.pengguna
SET posisi = CASE
    WHEN pr.nama_peran = 'developer' THEN 'developer'
    WHEN pr.nama_peran = 'owner' THEN 'owner'
    WHEN pr.nama_peran = 'admin' THEN 'admin'
    WHEN pr.nama_peran = 'sales' THEN 'sales'
    WHEN pr.nama_peran = 'driver' THEN 'driver'
    ELSE pr.nama_peran
END
FROM public.peran pr
WHERE public.pengguna.peran_id = pr.id
  AND public.pengguna.posisi IS NULL
  AND public.pengguna.nama_pengguna IS NOT NULL;  -- hanya yang punya akun login

-- 2. Pastikan kata_sandi ada (dari migration 06 yang seharusnya dilewati)
ALTER TABLE public.pengguna ADD COLUMN IF NOT EXISTS kata_sandi TEXT NULL;

-- 3. Tambah index performa untuk query login
CREATE INDEX IF NOT EXISTS idx_pengguna_nama_pengguna_lower ON public.pengguna (LOWER(nama_pengguna));

-- 4. Tambah index untuk lookup karyawan via pengguna_id
CREATE INDEX IF NOT EXISTS idx_karyawan_pengguna_id ON public.karyawan (pengguna_id);

-- 5. Update view v_karyawan_info untuk expose `nama_pengguna` dan `kata_sandi` info
--    agar ProfileController dan sistem lain bisa join lebih efisien
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
    p.nama_pengguna,
    k.tipe_penggajian,
    k.gaji_pokok_bulanan,
    k.uang_kehadiran_harian,
    k.tunjangan_bulanan,
    k.persentase_komisi_sales,
    k.dibuat_pada,
    k.diubah_pada
FROM public.karyawan k
JOIN public.pengguna p ON k.pengguna_id = p.id;

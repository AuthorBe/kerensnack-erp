-- database/migrations/71_add_jenis_kelamin_and_tanggal_lahir_to_pengguna.sql
-- Menambahkan kolom jenis_kelamin dan tanggal_lahir pada tabel public.pengguna
-- serta memperbarui view public.v_karyawan_info dengan security_invoker = true

BEGIN;

-- 1. Tambahkan kolom jenis_kelamin dan tanggal_lahir pada tabel public.pengguna
ALTER TABLE public.pengguna 
ADD COLUMN IF NOT EXISTS jenis_kelamin VARCHAR(10) DEFAULT 'L';

ALTER TABLE public.pengguna 
ADD COLUMN IF NOT EXISTS tanggal_lahir DATE DEFAULT NULL;

-- 2. Tambahkan constraint validasi jenis kelamin
ALTER TABLE public.pengguna 
DROP CONSTRAINT IF EXISTS chk_pengguna_jenis_kelamin;

ALTER TABLE public.pengguna 
ADD CONSTRAINT chk_pengguna_jenis_kelamin 
CHECK (jenis_kelamin IS NULL OR jenis_kelamin IN ('L', 'P', 'laki-laki', 'perempuan', 'Laki-laki', 'Perempuan', 'pria', 'wanita', 'Pria', 'Wanita'));

-- 3. Drop dan buat ulang view kanonikal v_karyawan_info dengan kolom jenis_kelamin dan tanggal_lahir
DROP VIEW IF EXISTS public.v_karyawan_info CASCADE;

CREATE VIEW public.v_karyawan_info
WITH (security_invoker = true) AS
SELECT
    k.id,
    k.pengguna_id,
    p.nama_lengkap AS nama_karyawan,
    p.nama_panggilan,
    p.jenis_kelamin,
    p.tanggal_lahir,
    p.nik,
    p.nik_pending,
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

COMMIT;

-- database/44_restrict_tipe_penggajian_to_borongan_and_bulanan.sql
-- 1. Pastikan seluruh data karyawan lama yang bertipe 'harian' (jika ada) dimutakhirkan ke 'bulanan'
UPDATE public.karyawan
SET tipe_penggajian = 'bulanan'
WHERE tipe_penggajian = 'harian';

-- 2. Perbarui CHECK constraint pada tabel public.karyawan agar hanya menerima 'borongan' dan 'bulanan'
ALTER TABLE public.karyawan DROP CONSTRAINT IF EXISTS karyawan_tipe_penggajian_check;
ALTER TABLE public.karyawan ADD CONSTRAINT karyawan_tipe_penggajian_check CHECK (tipe_penggajian IN ('borongan', 'bulanan'));

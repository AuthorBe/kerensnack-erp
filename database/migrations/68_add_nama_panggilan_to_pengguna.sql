-- database/68_add_nama_panggilan_to_pengguna.sql
-- Menambahkan kolom nama_panggilan pada tabel pengguna dan memperbarui view v_karyawan_info

BEGIN;

ALTER TABLE public.pengguna 
ADD COLUMN IF NOT EXISTS nama_panggilan VARCHAR(50) DEFAULT NULL;

-- Drop dan buat ulang view kanonikal v_karyawan_info agar urutan kolom rapi
DROP VIEW IF EXISTS public.v_karyawan_info CASCADE;

CREATE VIEW public.v_karyawan_info
WITH (security_invoker = true) AS
SELECT
    k.id,
    k.pengguna_id,
    p.nama_lengkap AS nama_karyawan,
    p.nama_panggilan,
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

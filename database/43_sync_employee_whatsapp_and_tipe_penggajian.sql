-- database/43_sync_employee_whatsapp_and_tipe_penggajian.sql
-- 1. Migrasi data kontak: Salin nilai nomor_telepon ke nomor_whatsapp jika nomor_whatsapp masih kosong
UPDATE public.pengguna
SET nomor_whatsapp = nomor_telepon
WHERE (nomor_whatsapp IS NULL OR TRIM(nomor_whatsapp) = '')
  AND (nomor_telepon IS NOT NULL AND TRIM(nomor_telepon) != '');

-- 2. Pastikan sebaliknya jika nomor_whatsapp terisi tapi nomor_telepon kosong
UPDATE public.pengguna
SET nomor_telepon = nomor_whatsapp
WHERE (nomor_telepon IS NULL OR TRIM(nomor_telepon) = '')
  AND (nomor_whatsapp IS NOT NULL AND TRIM(nomor_whatsapp) != '');

-- 3. Perbarui view kanonikal v_karyawan_info dengan menjaga urutan kolom lama dan menambahkan nomor_whatsapp
CREATE OR REPLACE VIEW public.v_karyawan_info AS
SELECT
    k.id,
    k.pengguna_id,
    p.nama_lengkap AS nama_karyawan,
    p.nik,
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

-- ==============================================================================
-- MIGRATION 65: IZINKAN KARYAWAN TANPA NIK (NIK PENDING)
-- ==============================================================================
-- Menambahkan kolom nik_pending BOOLEAN pada tabel pengguna untuk menandai
-- karyawan yang belum memiliki NIK/KTP saat pertama kali didaftarkan.
--
-- Logika constraint baru:
--   - Posisi 'developer' → bebas (dikecualikan)
--   - nik_pending = TRUE AND nik IS NULL → diizinkan (belum punya NIK)
--   - nik IS NOT NULL AND nik ~ '^[0-9]{16}$' → diizinkan (NIK valid)
--   - Semua kondisi lain → ditolak (constraint violation)
--
-- CATATAN PENTING:
--   - NULL unik di PostgreSQL (setiap NULL dianggap berbeda) → tidak
--     melanggar UNIQUE INDEX pengguna_nik_key saat banyak karyawan pending.
--   - Update NIK dari pending ke asli dilakukan HANYA via form edit manual
--     di /employees, tidak melalui sinkronisasi Excel.
-- ==============================================================================

BEGIN;

-- 1. Tambahkan kolom nik_pending
ALTER TABLE public.pengguna
    ADD COLUMN IF NOT EXISTS nik_pending BOOLEAN NOT NULL DEFAULT FALSE;

-- 2. Hapus CHECK CONSTRAINT lama
ALTER TABLE public.pengguna
    DROP CONSTRAINT IF EXISTS chk_pengguna_nik_16_digit;

-- 3. Tambahkan CHECK CONSTRAINT baru yang mengizinkan nik_pending
ALTER TABLE public.pengguna
    ADD CONSTRAINT chk_pengguna_nik_16_digit CHECK (
        posisi = 'developer'
        OR (nik_pending = TRUE AND nik IS NULL)
        OR (nik IS NOT NULL AND nik ~ '^[0-9]{16}$')
    );

-- 4. Komentar kolom untuk dokumentasi
COMMENT ON COLUMN public.pengguna.nik_pending IS
    'TRUE jika karyawan belum memiliki NIK/KTP saat didaftarkan. NIK akan NULL. Wajib diisi via form edit setelah KTP tersedia.';

-- 5. Update view v_karyawan_info agar menyertakan kolom nik_pending
--    (dibutuhkan oleh frontend untuk menampilkan badge "NIK Belum Ada")
--    CATATAN: Harus DROP CASCADE + CREATE karena PostgreSQL melarang
--    perubahan urutan kolom via CREATE OR REPLACE VIEW
DROP VIEW IF EXISTS public.v_karyawan_info CASCADE;

CREATE VIEW public.v_karyawan_info
WITH (security_invoker = true) AS
SELECT
    k.id,
    k.pengguna_id,
    p.nama_lengkap AS nama_karyawan,
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

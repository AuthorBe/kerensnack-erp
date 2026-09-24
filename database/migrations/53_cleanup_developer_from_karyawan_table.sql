-- ==============================================================================
-- KEREN SNACK ERP - DATABASE MIGRATION 53: CLEANUP DEVELOPER FROM KARYAWAN TABLE
-- ==============================================================================
-- Deskripsi:
-- Menghapus entri akun teknis 'developer' dari tabel 'public.karyawan'.
-- Akun Developer adalah root sistem teknis di 'public.pengguna', bukan karyawan
-- operasional pabrik yang mengelola penggajian/payroll atau absensi.
-- ==============================================================================

BEGIN;

-- Hapus entri karyawan untuk pengguna berposisi 'developer'
DELETE FROM public.karyawan 
WHERE pengguna_id IN (
    SELECT p.id 
    FROM public.pengguna p
    JOIN public.peran pr ON p.peran_id = pr.id
    WHERE p.posisi = 'developer' OR pr.nama_peran = 'developer'
);

COMMIT;

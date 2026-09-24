-- ==============================================================================
-- MIGRATION 69: UPDATE ADMIN ROLE DESCRIPTION
-- Menghapus bagian 'Mandor Produksi Terpadu' dari deskripsi peran admin
-- Menjadi: 'Admin Operasional & Administrasi Bisnis'
-- ==============================================================================

BEGIN;

UPDATE public.peran
SET deskripsi = 'Admin Operasional & Administrasi Bisnis',
    diubah_pada = NOW()
WHERE nama_peran = 'admin';

COMMIT;

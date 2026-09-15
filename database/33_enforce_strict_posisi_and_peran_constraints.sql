-- ==============================================================================
-- MIGRATION 33: PENGUNCIAN KANONIKAL LEVEL BASIS DATA POSISI & PERAN RESMI
-- ==============================================================================
-- Mencegah secara permanen di level database engine PostgreSQL kemunculan kembali
-- posisi atau peran 'sales_driver' (atau penggabungan posisi yang tidak sah).
-- ==============================================================================

-- 1. Kunci Daftar Posisi Sah pada public.pengguna
-- Posisi hanya boleh: developer, owner, admin, mandor, pengemasan, sales, driver
ALTER TABLE public.pengguna
DROP CONSTRAINT IF EXISTS chk_pengguna_posisi_valid;

ALTER TABLE public.pengguna
ADD CONSTRAINT chk_pengguna_posisi_valid
CHECK (posisi IN ('developer', 'owner', 'admin', 'mandor', 'pengemasan', 'sales', 'driver'));

-- 2. Kunci Peran Sistem agar Tidak Mengandung 'sales_driver'
ALTER TABLE public.peran
DROP CONSTRAINT IF EXISTS chk_peran_no_sales_driver;

ALTER TABLE public.peran
ADD CONSTRAINT chk_peran_no_sales_driver
CHECK (nama_peran != 'sales_driver');

-- 3. Perbaiki Deskripsi Izin RBAC yang Masih Memuat Frasa Warisan 'Sales-Driver'
UPDATE public.izin
SET deskripsi = 'Mengatur alokasi pembagian toko konsinyasi ke Sales Pembina Toko'
WHERE kode_izin = 'consignment.assignment'
  AND deskripsi ILIKE '%sales-driver%';

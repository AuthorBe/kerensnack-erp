-- ============================================================================
-- MIGRATION 98: GROUP EXECUTIVE MENU PERMISSIONS (RBAC UNIFICATION)
-- Database: PostgreSQL Supabase (KEREN SNACK ERP)
-- Mengelompokkan izin Executive Dashboard dan Pusat Unduh Laporan ke dalam
-- grup izin tunggal 'Executive Menu' agar selaras dengan menu bilah samping.
-- ============================================================================

BEGIN;

-- 1. Perbarui owner.dashboard ke grup 'Executive Menu'
UPDATE public.izin
SET grup_izin = 'Executive Menu',
    nama_izin = 'Executive Dashboard & Ringkasan Laba',
    deskripsi = 'Akses Executive Dashboard: omset total, profit margin, komparasi multi-kanal, dan live stream performa bisnis'
WHERE kode_izin = 'owner.dashboard';

-- 2. Perbarui reports.download_hub ke grup 'Executive Menu'
UPDATE public.izin
SET grup_izin = 'Executive Menu',
    nama_izin = 'Pusat Unduh Laporan & Ekspor Terpusat',
    deskripsi = 'Mengakses portal pusat unduhan laporan dan mengekspor rekapitulasi data keuangan, penjualan, konsinyasi, stok gudang, dan log sistem'
WHERE kode_izin = 'reports.download_hub';

COMMIT;

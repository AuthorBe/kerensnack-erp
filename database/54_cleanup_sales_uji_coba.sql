-- ==============================================================================
-- MIGRASI 54: PEMBERSIHAN TOTAL DATA SALES UJI COBA & DEPENDENSINYA
-- Standar: Zero Persistent Mock Data (AGENTS.md)
-- Target: 'Sales Uji Coba' (sales_uji_1), 'Toko Uji Konsinyasi' (TK-UJI-01), 
--         beserta seluruh transaksi & relasi terkait.
-- ==============================================================================

BEGIN;

-- 1. Hapus Relasi Tagihan Kunjungan Konsinyasi
DELETE FROM public.tagihan_kunjungan 
WHERE kunjungan_id IN (
    SELECT id FROM public.kunjungan_konsinyasi 
    WHERE id = '66666666-6666-6666-6666-666666666661'
       OR sales_driver_id IN ('88888888-8888-8888-8888-888888888881', '99999999-9999-9999-9999-999999999991')
       OR pelanggan_id = '77777777-7777-7777-7777-777777777771'
);

-- 2. Hapus Rincian Kunjungan Konsinyasi
DELETE FROM public.rincian_kunjungan_konsinyasi 
WHERE kunjungan_id IN (
    SELECT id FROM public.kunjungan_konsinyasi 
    WHERE id = '66666666-6666-6666-6666-666666666661'
       OR sales_driver_id IN ('88888888-8888-8888-8888-888888888881', '99999999-9999-9999-9999-999999999991')
       OR pelanggan_id = '77777777-7777-7777-7777-777777777771'
);

-- 3. Hapus Kunjungan Konsinyasi
DELETE FROM public.kunjungan_konsinyasi 
WHERE id = '66666666-6666-6666-6666-666666666661'
   OR sales_driver_id IN ('88888888-8888-8888-8888-888888888881', '99999999-9999-9999-9999-999999999991')
   OR pelanggan_id = '77777777-7777-7777-7777-777777777771'
   OR dibuat_oleh = '88888888-8888-8888-8888-888888888881';

-- 4. Hapus Surat Jalan Uji Coba (jika ada)
DELETE FROM public.surat_jalan 
WHERE pesanan_id = '55555555-5555-5555-5555-555555555551'
   OR sales_driver_id IN ('88888888-8888-8888-8888-888888888881', '99999999-9999-9999-9999-999999999991');

-- 5. Hapus Item Pesanan
DELETE FROM public.item_pesanan 
WHERE pesanan_id = '55555555-5555-5555-5555-555555555551'
   OR pesanan_id IN (
       SELECT id FROM public.pesanan 
       WHERE pelanggan_id = '77777777-7777-7777-7777-777777777771'
          OR sales_driver_id IN ('88888888-8888-8888-8888-888888888881', '99999999-9999-9999-9999-999999999991')
          OR dibuat_oleh = '88888888-8888-8888-8888-888888888881'
   );

-- 6. Hapus Pesanan / Faktur Uji Coba
DELETE FROM public.pesanan 
WHERE id = '55555555-5555-5555-5555-555555555551'
   OR pelanggan_id = '77777777-7777-7777-7777-777777777771'
   OR sales_driver_id IN ('88888888-8888-8888-8888-888888888881', '99999999-9999-9999-9999-999999999991')
   OR dibuat_oleh = '88888888-8888-8888-8888-888888888881'
   OR nomor_nota = 'INV-KONSIN-UJI-01';

-- 7. Hapus Stok Konsinyasi Toko Uji Coba
DELETE FROM public.stok_konsinyasi_toko 
WHERE pelanggan_id = '77777777-7777-7777-7777-777777777771';

-- 8. Lepas / Hapus Pelanggan Toko Uji Coba
DELETE FROM public.pelanggan 
WHERE id = '77777777-7777-7777-7777-777777777771'
   OR kode_pelanggan = 'TK-UJI-01';

-- Update jika ada pelanggan lain yang pernah terhubung ke sales_driver_id ini
UPDATE public.pelanggan 
SET sales_driver_id = NULL 
WHERE sales_driver_id IN ('88888888-8888-8888-8888-888888888881', '99999999-9999-9999-9999-999999999991');

-- 9. Hapus Modul Karyawan Terkait
DELETE FROM public.transaksi_tabungan 
WHERE karyawan_id = '99999999-9999-9999-9999-999999999991';

DELETE FROM public.tabungan 
WHERE karyawan_id = '99999999-9999-9999-9999-999999999991';

DELETE FROM public.potongan_kasbon 
WHERE kasbon_id IN (SELECT id FROM public.kasbon WHERE karyawan_id = '99999999-9999-9999-9999-999999999991');

DELETE FROM public.kasbon 
WHERE karyawan_id = '99999999-9999-9999-9999-999999999991'
   OR disetujui_oleh = '88888888-8888-8888-8888-888888888881';

DELETE FROM public.rincian_penggajian 
WHERE karyawan_id = '99999999-9999-9999-9999-999999999991';

DELETE FROM public.penarikan_gaji 
WHERE karyawan_id = '99999999-9999-9999-9999-999999999991';

DELETE FROM public.absensi 
WHERE karyawan_id = '99999999-9999-9999-9999-999999999991';

DELETE FROM public.produksi_harian 
WHERE karyawan_id = '99999999-9999-9999-9999-999999999991'
   OR dicatat_oleh = '88888888-8888-8888-8888-888888888881';

DELETE FROM public.karyawan 
WHERE id = '99999999-9999-9999-9999-999999999991'
   OR pengguna_id = '88888888-8888-8888-8888-888888888881';

-- 10. Hapus Izin & Log Pengguna
DELETE FROM public.izin_pengguna 
WHERE pengguna_id = '88888888-8888-8888-8888-888888888881';

DELETE FROM public.log_aktivitas 
WHERE pengguna_id = '88888888-8888-8888-8888-888888888881';

-- 11. Hapus Pengguna Sales Uji Coba
DELETE FROM public.pengguna 
WHERE id = '88888888-8888-8888-8888-888888888881'
   OR nama_pengguna = 'sales_uji_1';

-- 12. Pastikan Master Skema Komisi Sales Bertingkat Memiliki 4 Tier Standar
INSERT INTO public.skema_komisi_sales (urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif)
SELECT 1, 'Tier 1 (Dasar)', 0.00, 20000000.00, 1.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM public.skema_komisi_sales WHERE urutan = 1);

INSERT INTO public.skema_komisi_sales (urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif)
SELECT 2, 'Tier 2 (Reguler)', 20000000.01, 35000000.00, 2.50, TRUE
WHERE NOT EXISTS (SELECT 1 FROM public.skema_komisi_sales WHERE urutan = 2);

INSERT INTO public.skema_komisi_sales (urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif)
SELECT 3, 'Tier 3 (Gold)', 35000000.01, 50000000.00, 4.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM public.skema_komisi_sales WHERE urutan = 3);

INSERT INTO public.skema_komisi_sales (urutan, nama_tier, omzet_min, omzet_maks, persentase, status_aktif)
SELECT 4, 'Tier 4 (Platinum)', 50000000.01, NULL, 5.00, TRUE
WHERE NOT EXISTS (SELECT 1 FROM public.skema_komisi_sales WHERE urutan = 4);

COMMIT;

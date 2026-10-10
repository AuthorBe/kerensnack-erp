-- Migration: 100_revisi_data_kasbon_karyawan.sql
-- Keterangan: Penyesuaian data kasbon karyawan aktif agar persis sesuai docs/PROMPT/data_kasbon.
-- Dilakukan perbaikan sisa pinjaman & status kasbon untuk:
-- 1. Mona Chulyani: Sisa kasbon utama disesuaikan menjadi Rp 1.300.000 (03 Okt), kasbon kecil Rp 24.000 & Rp 12.000 ditandai lunas.
-- 2. Sri Nurjanah: Kasbon lama Rp 50.000 ditandai lunas, kasbon baru Rp 700.000 (03 Okt) dicatat aktif.
-- 3. Asiyah: Kasbon baru Rp 12.000 (09 Okt) dicatat aktif.
-- 4. Karyawan lainnya tanpa nama di data_kasbon dipastikan lunas / tidak memiliki kasbon aktif.
-- 5. Karyawan Erniya (Rp 600.000), Karno (Rp 500.000), Karyati (Rp 500.000), Titin Hartati (Rp 200.000), Watira (Rp 200.000) sudah sesuai patokan.

BEGIN;

-- 1. Mona Chulyani: Kasbon utama (ID 97d24ff7-4a76-5215-95db-a753b20316c3) disesuaikan sisa Rp 1.300.000 (aktif)
UPDATE public.kasbon 
SET sisa_pinjaman = 1300000.00,
    status_kasbon = 'aktif',
    diubah_pada = NOW() 
WHERE id = '97d24ff7-4a76-5215-95db-a753b20316c3';

-- Mona Chulyani: Kasbon tambahan 24.000 dan 12.000 ditandai lunas
UPDATE public.kasbon 
SET sisa_pinjaman = 0.00,
    status_kasbon = 'lunas',
    diubah_pada = NOW() 
WHERE id IN ('84e7e0ac-a5eb-5b2f-ab9c-94cbd3f58860', 'e6f02f84-ae6f-50bb-9b7a-2f7c3320e29a');

-- 2. Sri Nurjanah: Kasbon lama Rp 50.000 ditandai lunas
UPDATE public.kasbon 
SET sisa_pinjaman = 0.00,
    status_kasbon = 'lunas',
    diubah_pada = NOW() 
WHERE id = '7b290c8f-9ce7-5a24-98b3-e8987672be01';

-- Sri Nurjanah: Kasbon aktif baru Rp 700.000 per 03 Okt 2026
INSERT INTO public.kasbon (
    id, karyawan_id, tanggal_pengajuan, total_pinjaman,
    potongan_per_periode, sisa_pinjaman, status_kasbon,
    keterangan, catatan, dibuat_pada, diubah_pada
) VALUES (
    '5cdade21-6246-5b97-bdc7-5ee426d5fdf6',
    'ea3dad1e-7cb7-4191-8655-91a7e38f8c7a',
    '2026-10-03',
    700000.00,
    100000.00,
    700000.00,
    'aktif',
    'Pinjaman kasbon',
    'Revisi data kasbon per 03 Okt 2026',
    '2026-10-03 08:00:00+07',
    NOW()
) ON CONFLICT (id) DO UPDATE SET
    total_pinjaman = EXCLUDED.total_pinjaman,
    sisa_pinjaman = EXCLUDED.sisa_pinjaman,
    status_kasbon = EXCLUDED.status_kasbon,
    diubah_pada = NOW();

-- 3. Asiyah: Kasbon aktif baru Rp 12.000 per 09 Okt 2026
INSERT INTO public.kasbon (
    id, karyawan_id, tanggal_pengajuan, total_pinjaman,
    potongan_per_periode, sisa_pinjaman, status_kasbon,
    keterangan, catatan, dibuat_pada, diubah_pada
) VALUES (
    '72b0d9a5-a4be-5b20-b862-7a4ec3da97ac',
    'e6b1fce6-8abc-4107-b536-6a231bc4bd3e',
    '2026-10-09',
    12000.00,
    12000.00,
    12000.00,
    'aktif',
    'Pinjaman kasbon',
    'Revisi data kasbon per 09 Okt 2026',
    '2026-10-09 08:00:00+07',
    NOW()
) ON CONFLICT (id) DO UPDATE SET
    total_pinjaman = EXCLUDED.total_pinjaman,
    sisa_pinjaman = EXCLUDED.sisa_pinjaman,
    status_kasbon = EXCLUDED.status_kasbon,
    diubah_pada = NOW();

-- 4. Invariant Protection: Pastikan tidak ada karyawan di luar daftar patokan yang memiliki kasbon aktif
UPDATE public.kasbon
SET sisa_pinjaman = 0.00,
    status_kasbon = 'lunas',
    diubah_pada = NOW()
WHERE karyawan_id NOT IN (
    '834b7771-576c-4d23-b2e5-7aee2a2a0bfe', -- Erniya
    'e952afea-3e96-4197-8b26-6b31ca14a62c', -- Mona Chulyani
    'e6b1fce6-8abc-4107-b536-6a231bc4bd3e', -- Asiyah
    'ea3dad1e-7cb7-4191-8655-91a7e38f8c7a', -- Sri Nurjanah
    '9169c666-a5ad-4243-a9ba-66e34052b555', -- Watira
    'd47b73d6-b5ad-4741-8e1a-6ecc586a24a8', -- Karno
    'd2e56406-0863-48a5-97fb-ca3fee3ded45', -- Karyati
    '5558578f-f9cb-487b-8f1f-70e81144b14e'  -- Titin Hartati
) AND (status_kasbon = 'aktif' OR sisa_pinjaman > 0);

COMMIT;

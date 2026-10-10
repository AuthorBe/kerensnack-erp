-- Migration 100: Rekonsiliasi & Penyeimbangan Riwayat Pelunasan Kasbon Historis
-- Memastikan 100% konsistensi matematis antara kasbon, potongan_kasbon, dan saldo berjalan buku besar.

BEGIN;

-- Non-aktifkan sementara trigger update saldo agar baris penyeimbang pelunasan
-- pada pinjaman historis yang sudah berstatus lunas dapat masuk dengan aman.
ALTER TABLE public.potongan_kasbon DISABLE TRIGGER trg_potongan_kasbon_update_saldo;

-- 1. Sri Nurjanah (Pinjaman Historis 7b290c8f Rp 50.000)
INSERT INTO public.potongan_kasbon (
    id, kasbon_id, tanggal, nominal, tipe_potongan, keterangan, dibuat_pada
) VALUES (
    '11111111-7b29-5000-8000-000000000050',
    '7b290c8f-9ce7-5a24-98b3-e8987672be01',
    '2026-09-12',
    50000.00,
    'manual',
    'Pelunasan Kasbon Historis',
    '2026-09-12 05:00:00+07'
) ON CONFLICT (id) DO NOTHING;

-- 2. Mona Chulyani (Pinjaman Historis 84e7e0ac Rp 24.000)
INSERT INTO public.potongan_kasbon (
    id, kasbon_id, tanggal, nominal, tipe_potongan, keterangan, dibuat_pada
) VALUES (
    '22222222-84e7-5000-8000-000000000024',
    '84e7e0ac-a5eb-5b2f-ab9c-94cbd3f58860',
    '2026-09-05',
    24000.00,
    'manual',
    'Pelunasan Kasbon Snack (2 pcs)',
    '2026-09-05 07:00:00+07'
) ON CONFLICT (id) DO NOTHING;

-- 3. Mona Chulyani (Pinjaman Historis e6f02f84 Rp 12.000)
INSERT INTO public.potongan_kasbon (
    id, kasbon_id, tanggal, nominal, tipe_potongan, keterangan, dibuat_pada
) VALUES (
    '33333333-e6f0-5000-8000-000000000012',
    'e6f02f84-ae6f-50bb-9b7a-2f7c3320e29a',
    '2026-09-12',
    12000.00,
    'manual',
    'Pelunasan Kasbon Snack (1 pcs)',
    '2026-09-12 05:00:00+07'
) ON CONFLICT (id) DO NOTHING;

-- 4. Mona Chulyani (Pinjaman Historis 97d24ff7 Rp 14.000 Penyesuaian Saldo)
INSERT INTO public.potongan_kasbon (
    id, kasbon_id, tanggal, nominal, tipe_potongan, keterangan, dibuat_pada
) VALUES (
    '44444444-97d2-5000-8000-000000000014',
    '97d24ff7-4a76-5215-95db-a753b20316c3',
    '2026-10-03',
    14000.00,
    'manual',
    'Penyesuaian Pelunasan Kasbon Historis (Revisi Saldo)',
    '2026-10-03 07:00:00+07'
) ON CONFLICT (id) DO NOTHING;

-- Aktifkan kembali trigger update saldo
ALTER TABLE public.potongan_kasbon ENABLE TRIGGER trg_potongan_kasbon_update_saldo;

COMMIT;

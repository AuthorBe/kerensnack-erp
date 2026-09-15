-- ==============================================================================
-- MIGRATION 31: PEMISAHAN DEFINITIF AKUN, ROLE & HAK AKSES DRIVER VS SALES
-- ==============================================================================

-- 1. Koreksi Akun Pengguna Sales Default
-- Akun 'Sales' yang sebelumnya memiliki username 'driver' dialihkan ke username 'sales'
UPDATE public.pengguna
SET nama_pengguna = 'sales',
    posisi = 'sales',
    peran_id = '41676225-d31e-42b8-80d5-26d93762e014' -- Peran Sales
WHERE id = '00000000-0000-0000-0000-000000000004';

-- 2. Koreksi Akun Pengguna Driver (Pak Joko)
-- Berikan username 'driver' resmi dan kaitkan ke peran 'driver'
UPDATE public.pengguna
SET nama_pengguna = 'driver',
    posisi = 'driver',
    peran_id = '84649dc6-5c10-4d2e-a134-763fb2bb1d59' -- Peran Driver
WHERE id = '5b13dd6c-f851-4e29-9fce-bd72ae0ebbf9';

-- 3. Pastikan Komisi Sales Driver Murni adalah 0.00%
UPDATE public.karyawan k
SET persentase_komisi_sales = 0.00
FROM public.pengguna p
WHERE k.pengguna_id = p.id AND p.posisi = 'driver';

-- 4. Bersihkan Hak Akses / Izin Toko Binaan & Konsinyasi dari Peran Driver
-- Driver murni hanya bertugas Logistik Pengiriman & Ambil Belanjaan Supplier (serta COD Kas)
DELETE FROM public.izin_peran
WHERE peran_id = '84649dc6-5c10-4d2e-a134-763fb2bb1d59'
  AND izin_id IN (
      SELECT id FROM public.izin
      WHERE kode_izin IN (
          'consignment.opname_assigned',
          'consignment.view_assigned',
          'master.customers_view_assigned',
          'orders.view_assigned'
      )
  );

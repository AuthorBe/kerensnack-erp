-- database/15_clean_up_sales_driver.sql

-- 1. Pindahkan semua posisi 'sales_driver' menjadi 'sales' 
-- karena sales bertanggung jawab pada toko, sedangkan driver murni hanya pengiriman.
UPDATE public.pengguna
SET posisi = 'sales'
WHERE posisi = 'sales_driver';

-- 2. Jika masih ada peran bernama 'sales_driver' (walau kayaknya udah dihapus), kita ubah
UPDATE public.peran 
SET nama_peran = 'sales'
WHERE nama_peran = 'sales_driver';

-- database/44_backfill_missing_cash_transactions.sql
-- Backfill 4 Transaksi September 2026 yang terlewat dari Buku Kas (public.arus_kas)
-- dan Rekonsiliasi Saldo Berjalan public.akun_kas

BEGIN;

-- 1. KRS-2609-0006: Penjualan Toko FODDMAX (Rp 225.000 via Transfer ke Akun BCA Bisnis KEREN Snack)
-- Tanggal Transaksi: 2026-09-12
INSERT INTO public.arus_kas (
    nomor_transaksi,
    akun_kas_id,
    tanggal_transaksi,
    jenis_kas,
    kategori,
    nominal,
    keterangan,
    referensi_tabel,
    referensi_id,
    saldo_berjalan,
    dicatat_oleh,
    dibuat_pada
)
SELECT
    'BKM-2609-0023',
    '22222222-2222-2222-2222-222222222202',
    '2026-09-12',
    'masuk',
    'penjualan',
    225000.00,
    'Penerimaan Pembayaran Lunas Pesanan Toko #KRS-2609-0006 (FODDMAX)',
    'pesanan',
    'fd525f97-0809-4502-a868-c6249a984810',
    7757250.00,
    '00000000-0000-0000-0000-000000000001',
    '2026-09-12 10:00:00+07'
WHERE NOT EXISTS (
    SELECT 1 FROM public.arus_kas 
    WHERE referensi_tabel = 'pesanan' 
      AND referensi_id = 'fd525f97-0809-4502-a868-c6249a984810' 
      AND jenis_kas = 'masuk'
);

-- 2. KRS-2609-0002: Penjualan Toko FODDMAX (Rp 165.000 via QRIS Kasir Toko)
-- Tanggal Transaksi: 2026-09-03
INSERT INTO public.arus_kas (
    nomor_transaksi,
    akun_kas_id,
    tanggal_transaksi,
    jenis_kas,
    kategori,
    nominal,
    keterangan,
    referensi_tabel,
    referensi_id,
    saldo_berjalan,
    dicatat_oleh,
    dibuat_pada
)
SELECT
    'BKM-2609-0024',
    '22222222-2222-2222-2222-222222222201',
    '2026-09-03',
    'masuk',
    'penjualan',
    165000.00,
    'Penerimaan Pembayaran Lunas Pesanan Toko #KRS-2609-0002 (FODDMAX)',
    'pesanan',
    '8fc68e56-b8de-4a96-912d-fe2ea42e13ca',
    2077500.00,
    '00000000-0000-0000-0000-000000000001',
    '2026-09-03 10:00:00+07'
WHERE NOT EXISTS (
    SELECT 1 FROM public.arus_kas 
    WHERE referensi_tabel = 'pesanan' 
      AND referensi_id = '8fc68e56-b8de-4a96-912d-fe2ea42e13ca' 
      AND jenis_kas = 'masuk'
);

-- 3. KRS-2609-0007: Uang Muka (DP) Pesanan Toko FODDMAX (Rp 100.000 via Transfer ke Akun BCA Bisnis)
-- Tanggal Transaksi: 2026-09-12
INSERT INTO public.arus_kas (
    nomor_transaksi,
    akun_kas_id,
    tanggal_transaksi,
    jenis_kas,
    kategori,
    nominal,
    keterangan,
    referensi_tabel,
    referensi_id,
    saldo_berjalan,
    dicatat_oleh,
    dibuat_pada
)
SELECT
    'BKM-2609-0025',
    '22222222-2222-2222-2222-222222222202',
    '2026-09-12',
    'masuk',
    'penjualan',
    100000.00,
    'Penerimaan Uang Muka (DP) Pesanan Toko #KRS-2609-0007 (FODDMAX)',
    'pesanan',
    '0ddeaa8a-19b6-49d0-a9da-720d230af072',
    7857250.00,
    '00000000-0000-0000-0000-000000000001',
    '2026-09-12 11:30:00+07'
WHERE NOT EXISTS (
    SELECT 1 FROM public.arus_kas 
    WHERE referensi_tabel = 'pesanan' 
      AND referensi_id = '0ddeaa8a-19b6-49d0-a9da-720d230af072' 
      AND jenis_kas = 'masuk'
);

-- 4. PB-20260914-001: Pembelian Bahan Baku PO Lunas (Rp 330.000 via Transfer Kantor dari BCA Bisnis)
-- Tanggal Transaksi: 2026-09-14
INSERT INTO public.arus_kas (
    nomor_transaksi,
    akun_kas_id,
    tanggal_transaksi,
    jenis_kas,
    kategori,
    nominal,
    keterangan,
    referensi_tabel,
    referensi_id,
    saldo_berjalan,
    dicatat_oleh,
    dibuat_pada
)
SELECT
    'BKK-2609-0004',
    '22222222-2222-2222-2222-222222222202',
    '2026-09-14',
    'keluar',
    'pembelian_bahan',
    330000.00,
    'Pembayaran PO pembelian vendor (Transfer): PB-20260914-001',
    'pembelian',
    'b621f320-65d4-40ac-815e-59051e147491',
    7527250.00,
    '00000000-0000-0000-0000-000000000001',
    '2026-09-14 21:07:56+07'
WHERE NOT EXISTS (
    SELECT 1 FROM public.arus_kas 
    WHERE referensi_tabel = 'pembelian' 
      AND referensi_id = 'b621f320-65d4-40ac-815e-59051e147491' 
      AND jenis_kas = 'keluar'
);

-- 5. Rekonsiliasi Saldo Berjalan Akun Kas (akun_kas.saldo_saat_ini)
-- Saldo di-update sesuai total netto buku kas arus_kas
UPDATE public.akun_kas ak
SET saldo_saat_ini = sub.net_saldo,
    diubah_pada = NOW()
FROM (
    SELECT 
        akun_kas_id,
        COALESCE(SUM(CASE WHEN jenis_kas IN ('masuk', 'transfer_masuk') THEN nominal ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN jenis_kas IN ('keluar', 'transfer_keluar') THEN nominal ELSE 0 END), 0) as net_saldo
    FROM public.arus_kas
    GROUP BY akun_kas_id
) sub
WHERE ak.id = sub.akun_kas_id;

COMMIT;

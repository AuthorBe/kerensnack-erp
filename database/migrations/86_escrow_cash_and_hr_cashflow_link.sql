-- ==============================================================================
-- MIGRATION 86: ESCROW CASH & CLOSED-LOOP HR CASHFLOW LINK
-- ==============================================================================
-- Menambahkan flag is_escrow pada akun_kas untuk proteksi tabungan karyawan,
-- mendaftarkan akun kas escrow default 'Kas Tabungan Karyawan (Terkunci)',
-- serta menghubungkan transaksi kasbon, cicilan manual, penarikan gaji harian,
-- dan transaksi tabungan ke akun kas fisik riil.
-- ==============================================================================

BEGIN;

-- 1. Tambah kolom is_escrow pada akun_kas
ALTER TABLE public.akun_kas
ADD COLUMN IF NOT EXISTS is_escrow BOOLEAN NOT NULL DEFAULT FALSE;

-- 2. Daftarkan akun kas escrow khusus tabungan karyawan jika belum ada
INSERT INTO public.akun_kas (
    id, nama_akun, nomor_rekening, atas_nama, saldo_saat_ini, status_aktif, tipe_akun, is_escrow, is_default_pos, dibuat_pada, diubah_pada
) VALUES (
    '22222222-2222-2222-2222-222222222203',
    'Kas Tabungan Karyawan (Terkunci)',
    '-',
    'Titipan Tabungan Karyawan',
    0.00,
    TRUE,
    'kas_tabungan',
    TRUE,
    FALSE,
    NOW(),
    NOW()
) ON CONFLICT (id) DO UPDATE SET
    nama_akun = EXCLUDED.nama_akun,
    tipe_akun = 'kas_tabungan',
    is_escrow = TRUE;

-- 3. Tambah relasi akun_kas_id pada tabel kasbon (pencairan pinjaman)
ALTER TABLE public.kasbon
ADD COLUMN IF NOT EXISTS akun_kas_id UUID REFERENCES public.akun_kas(id) ON DELETE RESTRICT;

CREATE INDEX IF NOT EXISTS idx_kasbon_akun_kas ON public.kasbon(akun_kas_id);

-- 4. Tambah relasi akun_kas_id pada tabel potongan_kasbon (pembayaran cicilan manual)
ALTER TABLE public.potongan_kasbon
ADD COLUMN IF NOT EXISTS akun_kas_id UUID REFERENCES public.akun_kas(id) ON DELETE RESTRICT;

CREATE INDEX IF NOT EXISTS idx_potongan_kasbon_akun_kas ON public.potongan_kasbon(akun_kas_id);

-- 5. Tambah relasi akun_kas_id pada tabel penarikan_gaji (pengambilan uang harian)
ALTER TABLE public.penarikan_gaji
ADD COLUMN IF NOT EXISTS akun_kas_id UUID REFERENCES public.akun_kas(id) ON DELETE RESTRICT;

CREATE INDEX IF NOT EXISTS idx_penarikan_gaji_akun_kas ON public.penarikan_gaji(akun_kas_id);

-- 6. Tambah relasi akun_kas_id pada tabel transaksi_tabungan (setor / tarik simpanan)
ALTER TABLE public.transaksi_tabungan
ADD COLUMN IF NOT EXISTS akun_kas_id UUID REFERENCES public.akun_kas(id) ON DELETE RESTRICT;

CREATE INDEX IF NOT EXISTS idx_transaksi_tabungan_akun_kas ON public.transaksi_tabungan(akun_kas_id);

COMMIT;

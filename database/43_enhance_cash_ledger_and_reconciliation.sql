-- ==============================================================================
-- 43_enhance_cash_ledger_and_reconciliation.sql
-- Modul Keuangan: Penambahan Nomor Bukti Kas, Indeks Performa & Rekonsiliasi Saldo
-- ==============================================================================

-- 1. Tambah kolom nomor_transaksi jika belum ada
ALTER TABLE public.arus_kas ADD COLUMN IF NOT EXISTS nomor_transaksi VARCHAR(50) UNIQUE;

-- 2. Tambah Composite Indexes untuk percepatan filter transaksi dan laporan
CREATE INDEX IF NOT EXISTS idx_arus_kas_tgl_jenis ON public.arus_kas(tanggal_transaksi DESC, jenis_kas);
CREATE INDEX IF NOT EXISTS idx_arus_kas_akun_tgl ON public.arus_kas(akun_kas_id, tanggal_transaksi DESC);
CREATE INDEX IF NOT EXISTS idx_arus_kas_nomor_tx ON public.arus_kas(nomor_transaksi);

-- 3. Rekonsiliasi Saldo Awal Historis Akun BCA Bisnis (Rp 400.000) & Kasir Toko (Rp 130.000)
-- Menghilangkan diskrepansi matematis dari testing awal 27 Agustus 2026
DO $$
DECLARE
    v_bca_id UUID := '22222222-2222-2222-2222-222222222202';
    v_kasir_id UUID := '22222222-2222-2222-2222-222222222201';
    v_bca_exists INT;
    v_kasir_exists INT;
BEGIN
    -- Cek rekonsiliasi BCA
    SELECT COUNT(*) INTO v_bca_exists 
    FROM public.arus_kas 
    WHERE akun_kas_id = v_bca_id AND kategori = 'modal_awal' AND keterangan LIKE '%Penetapan Saldo Awal Resmi Akun BCA%';

    IF v_bca_exists = 0 THEN
        INSERT INTO public.arus_kas (
            akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
            keterangan, saldo_berjalan, dibuat_pada
        ) VALUES (
            v_bca_id, '2026-08-27', 'masuk', 'modal_awal', 400000.00,
            'Penetapan Saldo Awal Resmi Akun BCA (Rekonsiliasi Master)', 400000.00, '2026-08-27 08:00:00+07'
        );
    END IF;

    -- Cek rekonsiliasi Kasir
    SELECT COUNT(*) INTO v_kasir_exists 
    FROM public.arus_kas 
    WHERE akun_kas_id = v_kasir_id AND kategori = 'modal_awal' AND keterangan LIKE '%Penetapan Saldo Awal Resmi Laci Kasir%';

    IF v_kasir_exists = 0 THEN
        INSERT INTO public.arus_kas (
            akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
            keterangan, saldo_berjalan, dibuat_pada
        ) VALUES (
            v_kasir_id, '2026-08-27', 'masuk', 'modal_awal', 130000.00,
            'Penetapan Saldo Awal Resmi Laci Kasir (Rekonsiliasi Master)', 130000.00, '2026-08-27 08:00:00+07'
        );
    END IF;
END $$;

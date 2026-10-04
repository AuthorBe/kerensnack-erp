-- ============================================================================
-- MIGRATION 80: ADD HR MISSING COLUMNS & RBAC PERMISSIONS
-- Database: PostgreSQL Supabase (KEREN SNACK ERP)
-- Tanggal: 30 September 2026
-- ============================================================================

BEGIN;

-- ----------------------------------------------------------------------------
-- 1. Tabel public.absensi: Tambah flag ambil_uang untuk karyawan bulanan
-- ----------------------------------------------------------------------------
ALTER TABLE public.absensi
ADD COLUMN IF NOT EXISTS ambil_uang BOOLEAN NOT NULL DEFAULT FALSE;

COMMENT ON COLUMN public.absensi.ambil_uang IS
'Khusus karyawan bulanan: TRUE jika karyawan mengambil uang harian (uang_kehadiran_harian). Saat di-set TRUE via UI, sistem otomatis membuat record di public.penarikan_gaji.';

-- ----------------------------------------------------------------------------
-- 2. Tabel public.produksi_harian: Tambah lembur dalam satuan bal
-- ----------------------------------------------------------------------------
ALTER TABLE public.produksi_harian
ADD COLUMN IF NOT EXISTS lembur_bal INT NOT NULL DEFAULT 0
CHECK (lembur_bal >= 0);

COMMENT ON COLUMN public.produksi_harian.lembur_bal IS
'Jumlah unit lembur dalam satuan bal (kemasan besar/karton). Kalkulasi upah lembur: (lembur_pcs + lembur_bal * isi_bal) * upah_per_pcs_snapshot. Default konversi bal disamakan rate.';

-- ----------------------------------------------------------------------------
-- 3. Tabel public.penggajian: Tambah nama display dan konfigurasi periode mixed
-- ----------------------------------------------------------------------------
ALTER TABLE public.penggajian
ADD COLUMN IF NOT EXISTS nama_payroll VARCHAR(255) NULL;

COMMENT ON COLUMN public.penggajian.nama_payroll IS
'Label/nama deskriptif untuk payroll run. Contoh: "Payroll Mingguan Sep W4 2026 (Borongan)" atau "Payroll Bulanan Sep 2026".';

ALTER TABLE public.penggajian
ADD COLUMN IF NOT EXISTS options_json JSONB NULL;

COMMENT ON COLUMN public.penggajian.options_json IS
'Konfigurasi periode per tipe karyawan untuk mixed payroll. Format: {"borongan":{"start":"2026-09-22","end":"2026-09-28"},"bulanan":{"start":"2026-09-01","end":"2026-09-30"}}';

-- ----------------------------------------------------------------------------
-- 4. Performance Indexes untuk Optimasi Kueri Payroll Engine
-- ----------------------------------------------------------------------------
CREATE INDEX IF NOT EXISTS idx_absensi_karyawan_tanggal
  ON public.absensi(karyawan_id, tanggal);

CREATE INDEX IF NOT EXISTS idx_absensi_penggajian_id
  ON public.absensi(penggajian_id)
  WHERE penggajian_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_absensi_status_kehadiran
  ON public.absensi(tanggal, status_kehadiran);

CREATE INDEX IF NOT EXISTS idx_produksi_harian_karyawan_tanggal
  ON public.produksi_harian(karyawan_id, tanggal);

CREATE INDEX IF NOT EXISTS idx_produksi_harian_penggajian_id
  ON public.produksi_harian(penggajian_id)
  WHERE penggajian_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_penarikan_gaji_penggajian_id
  ON public.penarikan_gaji(penggajian_id)
  WHERE penggajian_id IS NOT NULL;

CREATE INDEX IF NOT EXISTS idx_penarikan_gaji_karyawan_tanggal
  ON public.penarikan_gaji(karyawan_id, tanggal);

-- ----------------------------------------------------------------------------
-- 5. Daftarkan 13 Izin Baru (RBAC) pada tabel public.izin
-- ----------------------------------------------------------------------------
INSERT INTO public.izin (kode_izin, nama_izin, grup_izin, deskripsi)
VALUES
    ('hr.absensi_view',     'Lihat Data Absensi',              'HR & Penggajian', 'Melihat rekapitulasi dan daftar kehadiran harian karyawan'),
    ('hr.absensi_manage',   'Input & Edit Absensi',            'HR & Penggajian', 'Menginput, mengubah, dan menyimpan kehadiran harian karyawan bulk'),
    ('hr.produksi_view',    'Lihat Data Produksi',             'HR & Penggajian', 'Melihat riwayat dan data produksi harian borongan'),
    ('hr.produksi_manage',  'Input & Edit Produksi',           'HR & Penggajian', 'Mencatat, memperbarui, dan menghapus data produksi borongan'),
    ('hr.penarikan_view',   'Lihat Penarikan Gaji',            'HR & Penggajian', 'Melihat riwayat penarikan gaji harian karyawan'),
    ('hr.penarikan_manage', 'Input Penarikan Gaji',            'HR & Penggajian', 'Mencatat penarikan gaji harian manual karyawan'),
    ('hr.kasbon_view',      'Lihat Data Kasbon',               'HR & Penggajian', 'Melihat riwayat pinjaman kasbon dan mutasi cicilan'),
    ('hr.kasbon_manage',    'Buat & Kelola Kasbon',            'HR & Penggajian', 'Membuat kasbon baru, mencatat pembayaran manual, atau membatalkan kasbon'),
    ('hr.tabungan_view',    'Lihat Saldo Tabungan',            'HR & Penggajian', 'Melihat saldo tabungan karyawan dan riwayat transaksi'),
    ('hr.tabungan_manage',  'Deposit & Tarik Tabungan',        'HR & Penggajian', 'Mencatat setoran dan penarikan tabungan karyawan manual'),
    ('hr.payroll_view',     'Lihat Data Penggajian',           'HR & Penggajian', 'Melihat riwayat penggajian, preview payroll, dan cetak slip'),
    ('hr.payroll_manage',   'Generate & Edit Penggajian',      'HR & Penggajian', 'Men-generate draf penggajian baru, menyesuaikan line item, dan regenerasi'),
    ('hr.payroll_approve',  'Approve & Cancel Approve Payroll','HR & Penggajian', 'Menyetujui payroll run, memicu transaksi arus kas, dan membatalkan approval (24 jam)')
ON CONFLICT (kode_izin) DO UPDATE SET
    nama_izin = EXCLUDED.nama_izin,
    grup_izin = EXCLUDED.grup_izin,
    deskripsi = EXCLUDED.deskripsi;

-- ----------------------------------------------------------------------------
-- 6. Pemetaan Hak Akses Izin ke Peran (Owner, Admin, Mandor)
-- ----------------------------------------------------------------------------
DO $$
DECLARE
    v_owner_id UUID;
    v_admin_id UUID;
    v_mandor_id UUID;
    v_perm RECORD;
BEGIN
    SELECT id INTO v_owner_id FROM public.peran WHERE nama_peran = 'owner';
    SELECT id INTO v_admin_id FROM public.peran WHERE nama_peran = 'admin';
    SELECT id INTO v_mandor_id FROM public.peran WHERE nama_peran = 'mandor';

    -- 6a. Owner: Mendapatkan seluruh 13 izin HR & Penggajian
    IF v_owner_id IS NOT NULL THEN
        FOR v_perm IN SELECT id FROM public.izin WHERE kode_izin LIKE 'hr.%' LOOP
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_owner_id, v_perm.id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END LOOP;
    END IF;

    -- 6b. Admin: Mendapatkan semua izin HR operasional kecuali approve payroll
    IF v_admin_id IS NOT NULL THEN
        FOR v_perm IN SELECT id FROM public.izin WHERE kode_izin LIKE 'hr.%' AND kode_izin != 'hr.payroll_approve' LOOP
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_admin_id, v_perm.id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END LOOP;
    END IF;

    -- 6c. Mandor: Mendapatkan hak akses operasional harian & view
    IF v_mandor_id IS NOT NULL THEN
        FOR v_perm IN SELECT id FROM public.izin WHERE kode_izin IN (
            'hr.absensi_view',
            'hr.absensi_manage',
            'hr.produksi_view',
            'hr.produksi_manage',
            'hr.penarikan_view',
            'hr.kasbon_view',
            'hr.tabungan_view',
            'hr.payroll_view'
        ) LOOP
            INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan)
            VALUES (v_mandor_id, v_perm.id, TRUE)
            ON CONFLICT (peran_id, izin_id) DO UPDATE SET diizinkan = TRUE;
        END LOOP;
    END IF;
END $$;

COMMIT;

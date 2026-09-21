-- ==============================================================================
-- MIGRATION 62: PENEGAKAN NIK ASLI 16 DIGIT (WAJIB) UNTUK MASTER KARYAWAN
-- ==============================================================================
-- Memastikan kolom NIK pada seluruh karyawan non-developer wajib diisi (NOT NULL)
-- dan mematuhi format standar Nomor Induk Kependudukan (NIK) 16 digit angka KTP.
-- ==============================================================================

BEGIN;

-- 1. Hapus constraint lama jika ada (idempotency)
ALTER TABLE public.pengguna 
    DROP CONSTRAINT IF EXISTS chk_pengguna_nik_16_digit;

-- 2. Tambahkan CHECK constraint baru pada tabel pengguna:
--    - Posisi 'developer' dikecualikan dari kewajiban NIK operasional
--    - Seluruh posisi operasional (admin, mandor, pengemasan, sales, driver, owner)
--      wajib memiliki NIK terisi dengan tepat 16 digit angka murni
ALTER TABLE public.pengguna 
    ADD CONSTRAINT chk_pengguna_nik_16_digit 
    CHECK (
        posisi = 'developer' 
        OR (nik IS NOT NULL AND nik ~ '^[0-9]{16}$')
    );

COMMIT;

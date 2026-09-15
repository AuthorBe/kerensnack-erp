-- ==============================================================================
-- MIGRATION 32: PENGAMAN INTEGRITAS & PEMISAHAN DEFINITIF SALES VS DRIVER
-- ==============================================================================
-- Aturan Bisnis:
-- 1. Toko Binaan (pelanggan.sales_driver_id) HANYA BOLEH dipegang oleh posisi 'sales'.
--    Driver DILARANG KERAS menjadi penanggung jawab toko binaan.
-- 2. Driver DILARANG KERAS memiliki persentase komisi (persentase_komisi_sales = 0.00).
-- 3. Opname fisik rak konsinyasi dikendalikan dinamis lewat izin RBAC (bukan hardcoded di trigger).
-- ==============================================================================

-- ------------------------------------------------------------------------------
-- 1. Trigger Integritas Toko Binaan pada public.pelanggan
-- ------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION public.fn_guard_pelanggan_sales_driver()
RETURNS TRIGGER AS $$
DECLARE
    v_posisi VARCHAR(50);
    v_nama   VARCHAR(150);
BEGIN
    IF NEW.sales_driver_id IS NOT NULL THEN
        -- Ambil posisi karyawan dari relasi karyawan -> pengguna
        SELECT p.posisi, p.nama_lengkap INTO v_posisi, v_nama
        FROM public.karyawan k
        JOIN public.pengguna p ON k.pengguna_id = p.id
        WHERE k.id = NEW.sales_driver_id;

        IF LOWER(COALESCE(v_posisi, '')) = 'driver' THEN
            RAISE EXCEPTION 'Driver (%) tidak dapat ditugaskan sebagai Penanggung Jawab Toko Binaan! Posisi karyawan harus Sales.', v_nama;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_guard_pelanggan_sales_driver ON public.pelanggan;
CREATE TRIGGER trg_guard_pelanggan_sales_driver
BEFORE INSERT OR UPDATE OF sales_driver_id ON public.pelanggan
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_pelanggan_sales_driver();

-- ------------------------------------------------------------------------------
-- 2. Trigger Integritas Komisi Karyawan pada public.karyawan
-- ------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION public.fn_guard_karyawan_driver_no_commission()
RETURNS TRIGGER AS $$
DECLARE
    v_posisi VARCHAR(50);
    v_nama   VARCHAR(150);
BEGIN
    IF NEW.persentase_komisi_sales > 0 THEN
        SELECT posisi, nama_lengkap INTO v_posisi, v_nama
        FROM public.pengguna
        WHERE id = NEW.pengguna_id;

        IF LOWER(COALESCE(v_posisi, '')) = 'driver' THEN
            RAISE EXCEPTION 'Driver (%) tidak berhak mendapatkan komisi penjualan! Nilai persentase komisi driver harus 0.00%%.', v_nama;
        END IF;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_guard_karyawan_driver_no_commission ON public.karyawan;
CREATE TRIGGER trg_guard_karyawan_driver_no_commission
BEFORE INSERT OR UPDATE OF persentase_komisi_sales, pengguna_id ON public.karyawan
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_karyawan_driver_no_commission();

-- ------------------------------------------------------------------------------
-- 3. Trigger Reset Komisi Otomatis Saat Posisi Pengguna Diubah Menjadi 'driver'
-- ------------------------------------------------------------------------------
CREATE OR REPLACE FUNCTION public.fn_guard_pengguna_driver_reset_commission()
RETURNS TRIGGER AS $$
BEGIN
    IF LOWER(COALESCE(NEW.posisi, '')) = 'driver' THEN
        -- Otomatis nolkan komisi sales di tabel karyawan jika sebelumnya ada komisi
        UPDATE public.karyawan
        SET persentase_komisi_sales = 0.00
        WHERE pengguna_id = NEW.id AND persentase_komisi_sales > 0;
    END IF;

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

DROP TRIGGER IF EXISTS trg_guard_pengguna_driver_reset_commission ON public.pengguna;
CREATE TRIGGER trg_guard_pengguna_driver_reset_commission
AFTER INSERT OR UPDATE OF posisi ON public.pengguna
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_pengguna_driver_reset_commission();

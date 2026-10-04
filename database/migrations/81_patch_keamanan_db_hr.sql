BEGIN;

-- ==============================================================================
-- 1. MENGATASI SILENT FAIL PADA TABUNGAN & KASBON
-- ==============================================================================

-- Menambahkan Strict Constraint agar tidak mungkin bernilai minus (Lapis 1)
ALTER TABLE public.tabungan ADD CONSTRAINT chk_tabungan_saldo_positif CHECK (saldo >= 0);
ALTER TABLE public.kasbon ADD CONSTRAINT chk_kasbon_sisa_positif CHECK (sisa_pinjaman >= 0);

-- Modifikasi Trigger Mutasi Tabungan (Lapis 2 - Exception Terukur)
CREATE OR REPLACE FUNCTION public.fn_trg_transaksi_tabungan_update_saldo()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
 SET search_path = public, pg_temp
AS $function$
DECLARE
    v_saldo_sekarang NUMERIC;
BEGIN
    IF NEW.tipe = 'deposit' THEN
        UPDATE public.tabungan 
        SET saldo = saldo + NEW.jumlah, diubah_pada = NOW()
        WHERE id = NEW.tabungan_id;
    ELSIF NEW.tipe = 'withdrawal' THEN
        -- Ambil saldo dengan FOR UPDATE untuk mencegah Race Condition tingkat Row
        SELECT saldo INTO v_saldo_sekarang FROM public.tabungan WHERE id = NEW.tabungan_id FOR UPDATE;
        
        IF (v_saldo_sekarang - NEW.jumlah) < 0 THEN
            RAISE EXCEPTION 'Saldo tabungan tidak mencukupi untuk penarikan sebesar Rp %. Saldo saat ini: Rp %', NEW.jumlah, v_saldo_sekarang;
        END IF;
        
        UPDATE public.tabungan 
        SET saldo = saldo - NEW.jumlah, diubah_pada = NOW()
        WHERE id = NEW.tabungan_id;
    END IF;

    RETURN NEW;
END;
$function$;

-- Modifikasi Trigger Potongan Kasbon (Lapis 2 - Exception Terukur)
CREATE OR REPLACE FUNCTION public.fn_trg_potongan_kasbon_update_saldo()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
 SET search_path = public, pg_temp
AS $function$
DECLARE
    v_sisa_lama NUMERIC;
    v_sisa_baru NUMERIC;
BEGIN
    -- Ambil saldo sisa kasbon dengan FOR UPDATE
    SELECT sisa_pinjaman INTO v_sisa_lama
    FROM public.kasbon 
    WHERE id = NEW.kasbon_id FOR UPDATE;

    v_sisa_baru := v_sisa_lama - NEW.nominal;

    IF v_sisa_baru < 0 THEN
        RAISE EXCEPTION 'Nominal potongan (Rp %) melebihi sisa pinjaman kasbon (Rp %). Proses ditolak.', NEW.nominal, v_sisa_lama;
    END IF;

    UPDATE public.kasbon 
    SET sisa_pinjaman = v_sisa_baru,
        status_kasbon = CASE WHEN v_sisa_baru = 0 THEN 'lunas' ELSE 'aktif' END,
        diubah_pada = NOW()
    WHERE id = NEW.kasbon_id;

    RETURN NEW;
END;
$function$;


-- ==============================================================================
-- 2. KUNCI IMMUTABLE UNTUK DOKUMEN YANG TELAH MASUK PAYROLL
-- ==============================================================================

-- Fungsi Guard untuk tabel dengan penggajian_id (produksi_harian, absensi, penarikan_gaji)
CREATE OR REPLACE FUNCTION public.fn_guard_locked_hr_transactions()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
 SET search_path = public, pg_temp
AS $function$
BEGIN
    IF OLD.penggajian_id IS NOT NULL THEN
        IF TG_OP = 'UPDATE' THEN
            IF NEW.penggajian_id IS NULL THEN
                RETURN NEW; -- Izinkan jika aplikasi sedang membuka gembok
            END IF;
            RAISE EXCEPTION 'Akses Ditolak (UPDATE): Transaksi ini telah terkunci secara permanen oleh Invoice Payroll (ID: %). Batal/drafkan payroll dari aplikasi terlebih dahulu.', OLD.penggajian_id;
        ELSIF TG_OP = 'DELETE' THEN
            RAISE EXCEPTION 'Akses Ditolak (DELETE): Transaksi ini telah terkunci secara permanen oleh Invoice Payroll (ID: %). Batal/drafkan payroll dari aplikasi terlebih dahulu.', OLD.penggajian_id;
        END IF;
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$function$;

-- Memasang Trigger Guard ke produksi_harian
DROP TRIGGER IF EXISTS trg_guard_locked_hr_produksi ON public.produksi_harian;
CREATE TRIGGER trg_guard_locked_hr_produksi
BEFORE UPDATE OR DELETE ON public.produksi_harian
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_locked_hr_transactions();

-- Memasang Trigger Guard ke absensi
DROP TRIGGER IF EXISTS trg_guard_locked_hr_absensi ON public.absensi;
CREATE TRIGGER trg_guard_locked_hr_absensi
BEFORE UPDATE OR DELETE ON public.absensi
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_locked_hr_transactions();

-- Memasang Trigger Guard ke penarikan_gaji
DROP TRIGGER IF EXISTS trg_guard_locked_hr_penarikan_gaji ON public.penarikan_gaji;
CREATE TRIGGER trg_guard_locked_hr_penarikan_gaji
BEFORE UPDATE OR DELETE ON public.penarikan_gaji
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_locked_hr_transactions();


-- Fungsi Guard untuk tabel dengan rincian_penggajian_id (potongan_kasbon, transaksi_tabungan)
CREATE OR REPLACE FUNCTION public.fn_guard_locked_hr_rincian_transactions()
 RETURNS trigger
 LANGUAGE plpgsql
 SECURITY DEFINER
 SET search_path = public, pg_temp
AS $function$
BEGIN
    IF OLD.rincian_penggajian_id IS NOT NULL THEN
        IF TG_OP = 'UPDATE' THEN
            IF NEW.rincian_penggajian_id IS NULL THEN
                RETURN NEW; -- Izinkan membuka gembok
            END IF;
            RAISE EXCEPTION 'Akses Ditolak (UPDATE): Transaksi ini telah dikunci oleh Rincian Payroll (ID: %).', OLD.rincian_penggajian_id;
        ELSIF TG_OP = 'DELETE' THEN
            RAISE EXCEPTION 'Akses Ditolak (DELETE): Transaksi ini telah dikunci oleh Rincian Payroll (ID: %).', OLD.rincian_penggajian_id;
        END IF;
    END IF;

    IF TG_OP = 'DELETE' THEN
        RETURN OLD;
    END IF;
    RETURN NEW;
END;
$function$;

-- Memasang Trigger Guard ke potongan_kasbon
DROP TRIGGER IF EXISTS trg_guard_locked_hr_potongan_kasbon ON public.potongan_kasbon;
CREATE TRIGGER trg_guard_locked_hr_potongan_kasbon
BEFORE UPDATE OR DELETE ON public.potongan_kasbon
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_locked_hr_rincian_transactions();

-- Memasang Trigger Guard ke transaksi_tabungan
DROP TRIGGER IF EXISTS trg_guard_locked_hr_transaksi_tabungan ON public.transaksi_tabungan;
CREATE TRIGGER trg_guard_locked_hr_transaksi_tabungan
BEFORE UPDATE OR DELETE ON public.transaksi_tabungan
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_locked_hr_rincian_transactions();


-- ==============================================================================
-- 3. CABUT BAHAYA ON DELETE CASCADE PADA BUKU TABUNGAN
-- ==============================================================================

ALTER TABLE public.tabungan DROP CONSTRAINT IF EXISTS tabungan_karyawan_id_fkey;

ALTER TABLE public.tabungan 
  ADD CONSTRAINT tabungan_karyawan_id_fkey 
  FOREIGN KEY (karyawan_id) 
  REFERENCES public.karyawan(id) 
  ON DELETE RESTRICT;
  
ALTER TABLE public.transaksi_tabungan DROP CONSTRAINT IF EXISTS transaksi_tabungan_tabungan_id_fkey;

ALTER TABLE public.transaksi_tabungan 
  ADD CONSTRAINT transaksi_tabungan_tabungan_id_fkey 
  FOREIGN KEY (tabungan_id) 
  REFERENCES public.tabungan(id) 
  ON DELETE RESTRICT;

COMMIT;

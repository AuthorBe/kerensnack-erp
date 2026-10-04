-- Migration 85: Penambahan Pelanggan Default Sistem 'Online Customer' (CUST-002),
-- Resequence Kode Pelanggan Existing (+1 dan Kontigu), dan Trigger Proteksi Sistem
BEGIN;

-- 1. Berikan prefix sementara TMP- pada seluruh pelanggan (selain CUST-001) agar terhindar dari konflik UNIQUE constraint
UPDATE public.pelanggan
SET kode_pelanggan = 'TMP-' || kode_pelanggan
WHERE kode_pelanggan != 'CUST-001';

-- 2. Resequence urutan kode pelanggan secara kontigu mulai dari CUST-003
WITH ranked AS (
    SELECT 
        id,
        ROW_NUMBER() OVER (ORDER BY CAST(SUBSTRING(kode_pelanggan FROM 10) AS INTEGER) ASC) + 2 as new_seq
    FROM public.pelanggan
    WHERE kode_pelanggan LIKE 'TMP-CUST-%'
)
UPDATE public.pelanggan p
SET 
    kode_pelanggan = 'CUST-' || CASE 
        WHEN r.new_seq < 1000 THEN LPAD(r.new_seq::TEXT, 3, '0') 
        ELSE r.new_seq::TEXT 
    END,
    diubah_pada = NOW()
FROM ranked r
WHERE p.id = r.id;

-- 3. Pastikan wilayah rute online tersedia di tabel master wilayah
INSERT INTO public.wilayah (kode_rute, nama_wilayah, provinsi, kota_kabupaten, sub_wilayah, status_aktif)
VALUES ('RTE-ONLINE', 'ONLINE', 'Indonesia', 'Nasional', 'Transaksi Penjualan Online', TRUE)
ON CONFLICT (kode_rute) DO UPDATE 
SET nama_wilayah = EXCLUDED.nama_wilayah;

-- 4. Tambahkan pelanggan default sistem 'Online Customer' dengan kode permanen CUST-002
INSERT INTO public.pelanggan (
    id,
    kode_pelanggan,
    nama_toko,
    nama_pemilik,
    grup_pelanggan_id,
    is_konsinyasi,
    tipe_konsinyasi,
    wilayah_id,
    alamat_lengkap,
    nomor_whatsapp,
    tipe_pembayaran_default,
    plafon_piutang,
    total_piutang_berjalan,
    sales_driver_id,
    status_aktif,
    dibuat_pada,
    diubah_pada
) VALUES (
    gen_random_uuid(),
    'CUST-002',
    'Online Customer',
    NULL,
    '44444444-4444-4444-4444-444444444401', -- GRP-UMUM
    FALSE,
    'rolling_nota',
    (SELECT id FROM public.wilayah WHERE kode_rute = 'RTE-ONLINE' LIMIT 1),
    'Transaksi Penjualan Online / Marketplace / E-Commerce',
    NULL,
    'transfer',
    0.00,
    0.00,
    NULL,
    TRUE,
    NOW(),
    NOW()
);

-- 5. Perbarui Trigger Proteksi Mutlak Pelanggan Default Sistem (CUST-001 & CUST-002)
CREATE OR REPLACE FUNCTION public.fn_guard_protect_default_customer()
RETURNS TRIGGER
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $$
BEGIN
    IF TG_OP = 'DELETE' THEN
        IF OLD.kode_pelanggan IN ('CUST-001', 'CUST-002') 
           OR UPPER(TRIM(OLD.nama_toko)) LIKE '%WALK-IN CASH%' 
           OR UPPER(TRIM(OLD.nama_toko)) LIKE '%ONLINE CUSTOMER%' THEN
            RAISE EXCEPTION 'Pelanggan default sistem (CUST-001 / Walk-in Cash dan CUST-002 / Online Customer) terkunci permanen dan tidak dapat dihapus.';
        END IF;
        RETURN OLD;
    END IF;

    IF TG_OP = 'UPDATE' THEN
        IF OLD.kode_pelanggan = 'CUST-001' THEN
            IF NEW.kode_pelanggan != 'CUST-001' THEN
                RAISE EXCEPTION 'Kode pelanggan default sistem (CUST-001) terkunci dan tidak boleh diubah.';
            END IF;
            IF NEW.status_aktif = FALSE THEN
                RAISE EXCEPTION 'Pelanggan default sistem (CUST-001) wajib tetap aktif untuk operasional kasir POS.';
            END IF;
        END IF;

        IF OLD.kode_pelanggan = 'CUST-002' THEN
            IF NEW.kode_pelanggan != 'CUST-002' THEN
                RAISE EXCEPTION 'Kode pelanggan default sistem (CUST-002) terkunci dan tidak boleh diubah.';
            END IF;
            IF NEW.status_aktif = FALSE THEN
                RAISE EXCEPTION 'Pelanggan default sistem (CUST-002) wajib tetap aktif untuk operasional penjualan online.';
            END IF;
        END IF;

        RETURN NEW;
    END IF;

    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_guard_protect_default_customer ON public.pelanggan;
CREATE TRIGGER trg_guard_protect_default_customer
BEFORE UPDATE OR DELETE ON public.pelanggan
FOR EACH ROW
EXECUTE FUNCTION public.fn_guard_protect_default_customer();

REVOKE EXECUTE ON FUNCTION public.fn_guard_protect_default_customer() FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_guard_protect_default_customer() TO postgres, service_role;

COMMIT;

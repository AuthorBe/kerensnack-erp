-- ==============================================================================
-- 24_enhance_pembelian_po_and_driver_delivery.sql
-- Menambahkan kapabilitas PO Pembelian, penugasan driver, dan alur dua tahap penerimaan
-- ==============================================================================

DO $$
BEGIN
    -- 1. Tambah jenis_dokumen
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'jenis_dokumen'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN jenis_dokumen VARCHAR(20) NOT NULL DEFAULT 'faktur';
        ALTER TABLE public.pembelian ADD CONSTRAINT pembelian_jenis_dokumen_check CHECK (jenis_dokumen IN ('faktur', 'po'));
    END IF;

    -- 2. Tambah metode_logistik
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'metode_logistik'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN metode_logistik VARCHAR(30) DEFAULT 'diantar_supplier';
        ALTER TABLE public.pembelian ADD CONSTRAINT pembelian_metode_logistik_check CHECK (metode_logistik IN ('diantar_supplier', 'diambil_driver'));
    END IF;

    -- 3. Tambah sales_driver_id
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'sales_driver_id'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN sales_driver_id UUID REFERENCES public.karyawan(id);
    END IF;

    -- 4. Tambah tanggal_jadwal_belanja
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'tanggal_jadwal_belanja'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN tanggal_jadwal_belanja DATE;
    END IF;

    -- 5. Tambah instruksi_driver
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'instruksi_driver'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN instruksi_driver TEXT;
    END IF;

    -- 6. Tambah metode_bayar_belanja
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'metode_bayar_belanja'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN metode_bayar_belanja VARCHAR(30) DEFAULT 'tempo_vendor';
        ALTER TABLE public.pembelian ADD CONSTRAINT pembelian_metode_bayar_belanja_check CHECK (metode_bayar_belanja IN ('tunai_driver', 'transfer_kantor', 'tempo_vendor'));
    END IF;

    -- 7. Tambah nominal_dibayar_driver
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'nominal_dibayar_driver'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN nominal_dibayar_driver NUMERIC(15, 2) DEFAULT 0.00;
    END IF;

    -- 8. Tambah nomor_nota_vendor
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'nomor_nota_vendor'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN nomor_nota_vendor VARCHAR(100);
    END IF;

    -- 9. Tambah foto_bukti_kendala
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'foto_bukti_kendala'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN foto_bukti_kendala TEXT;
    END IF;

    -- 10. Tambah alasan_kendala
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'alasan_kendala'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN alasan_kendala TEXT;
    END IF;

    -- 11. Tambah waktu_diambil & waktu_diterima_gudang
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'waktu_diambil'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN waktu_diambil TIMESTAMPTZ;
    END IF;

    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pembelian' AND column_name = 'waktu_diterima_gudang'
    ) THEN
        ALTER TABLE public.pembelian ADD COLUMN waktu_diterima_gudang TIMESTAMPTZ;
    END IF;

    -- 12. Update CHECK constraint status_penerimaan
    ALTER TABLE public.pembelian DROP CONSTRAINT IF EXISTS pembelian_status_penerimaan_check;
    ALTER TABLE public.pembelian ADD CONSTRAINT pembelian_status_penerimaan_check 
        CHECK (status_penerimaan IN ('diterima', 'menunggu_supplier', 'ditugaskan_driver', 'sudah_diambil', 'kendala_batal'));

END $$;

-- 13. Tambah Index Performa
CREATE INDEX IF NOT EXISTS idx_pembelian_driver_schedule 
    ON public.pembelian (sales_driver_id, tanggal_jadwal_belanja, status_penerimaan);
CREATE INDEX IF NOT EXISTS idx_pembelian_jenis_status 
    ON public.pembelian (jenis_dokumen, status_penerimaan);

-- ==============================================================================
-- 25_reconcile_and_fix_customer_receivables.sql
-- Menyelaraskan dan merekonsiliasi saldo piutang berjalan pelanggan (total_piutang_berjalan)
-- dengan sisa tagihan pesanan aktif (adalah_tagihan = TRUE) yang telah diserahterimakan.
-- ==============================================================================

-- 1. Buat Function Pemulihan & Rekonsiliasi Saldo Piutang Pelanggan
CREATE OR REPLACE FUNCTION public.fn_rekonsiliasi_piutang_pelanggan(p_pelanggan_id UUID DEFAULT NULL)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
AS $$
DECLARE
    v_updated_count INT := 0;
    v_total_piutang_baru NUMERIC(15, 2) := 0;
BEGIN
    IF p_pelanggan_id IS NOT NULL THEN
        -- Rekonsiliasi untuk 1 Pelanggan Tertentu
        UPDATE public.pelanggan p
        SET total_piutang_berjalan = COALESCE((
            SELECT SUM(pes.sisa_tagihan)
            FROM public.pesanan pes
            WHERE pes.pelanggan_id = p.id
              AND pes.adalah_tagihan = TRUE
              AND pes.status_pemrosesan IN ('selesai_dikirim', 'selesai', 'selesai_diterima')
              AND pes.status_pemrosesan != 'dibatalkan'
              AND pes.status_pembayaran != 'lunas'
              AND pes.sisa_tagihan > 0
        ), 0),
        diubah_pada = NOW()
        WHERE p.id = p_pelanggan_id;

        GET DIAGNOSTICS v_updated_count = ROW_COUNT;

        SELECT total_piutang_berjalan INTO v_total_piutang_baru
        FROM public.pelanggan WHERE id = p_pelanggan_id;

        RETURN jsonb_build_object(
            'success', true,
            'mode', 'single',
            'pelanggan_id', p_pelanggan_id,
            'total_piutang_berjalan', v_total_piutang_baru
        );
    ELSE
        -- Rekonsiliasi Menyeluruh untuk Seluruh Pelanggan Aktif Maupun Nonaktif
        UPDATE public.pelanggan p
        SET total_piutang_berjalan = COALESCE((
            SELECT SUM(pes.sisa_tagihan)
            FROM public.pesanan pes
            WHERE pes.pelanggan_id = p.id
              AND pes.adalah_tagihan = TRUE
              AND pes.status_pemrosesan IN ('selesai_dikirim', 'selesai', 'selesai_diterima')
              AND pes.status_pemrosesan != 'dibatalkan'
              AND pes.status_pembayaran != 'lunas'
              AND pes.sisa_tagihan > 0
        ), 0),
        diubah_pada = NOW();

        GET DIAGNOSTICS v_updated_count = ROW_COUNT;

        SELECT COALESCE(SUM(total_piutang_berjalan), 0) INTO v_total_piutang_baru
        FROM public.pelanggan;

        RETURN jsonb_build_object(
            'success', true,
            'mode', 'all',
            'pelanggan_terupdate', v_updated_count,
            'total_piutang_nasional', v_total_piutang_baru
        );
    END IF;
END;
$$;

-- 2. Jalankan Rekonsiliasi Langsung pada Seluruh Pelanggan
SELECT public.fn_rekonsiliasi_piutang_pelanggan(NULL);

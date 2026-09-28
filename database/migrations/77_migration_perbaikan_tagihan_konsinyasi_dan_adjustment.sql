-- ============================================================================
-- Migrasi 77: Perbaikan Modul Tagihan Konsinyasi, Potongan Adjustment & Kuitansi
-- 1. Upgrade public.fn_catat_pembayaran_konsinyasi dengan parameter potongan (retur susulan/adjustment)
-- 2. Dukungan mutasi saldo piutang & audit trail transaksi kas secara presisi
-- 3. Kepatuhan hak akses Supabase Security Definer & AGENTS.md
-- ============================================================================

BEGIN;

-- Drop varian overload lama jika ada untuk mencegah tabrakan signature parameter
DROP FUNCTION IF EXISTS public.fn_catat_pembayaran_konsinyasi(uuid, uuid, numeric, uuid, text, date);
DROP FUNCTION IF EXISTS public.fn_catat_pembayaran_konsinyasi(uuid, uuid, numeric, uuid, text, date, numeric, text);

CREATE OR REPLACE FUNCTION public.fn_catat_pembayaran_konsinyasi(
    p_pesanan_id uuid,
    p_akun_kas_id uuid,
    p_nominal_bayar numeric,
    p_dicatat_oleh uuid DEFAULT NULL::uuid,
    p_keterangan text DEFAULT NULL::text,
    p_tanggal_bayar date DEFAULT CURRENT_DATE,
    p_nominal_potongan numeric DEFAULT 0.00,
    p_alasan_potongan text DEFAULT NULL::text
)
RETURNS jsonb
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path = public, pg_temp
AS $function$
DECLARE
    v_pesanan RECORD;
    v_sisa_baru NUMERIC(15,2);
    v_status_baru VARCHAR(30);
    v_saldo_lama NUMERIC(15,2);
    v_saldo_baru NUMERIC(15,2);
    v_pengguna_id UUID := p_dicatat_oleh;
    v_tgl_transaksi DATE := COALESCE(p_tanggal_bayar, CURRENT_DATE);
    v_ket_kas TEXT;
    v_potongan NUMERIC(15,2) := GREATEST(0.00, COALESCE(p_nominal_potongan, 0.00));
    v_bayar NUMERIC(15,2) := GREATEST(0.00, COALESCE(p_nominal_bayar, 0.00));
    v_total_pengurang NUMERIC(15,2);
    v_transaksi_kas_id UUID := NULL;
BEGIN
    SELECT * INTO v_pesanan FROM public.pesanan WHERE id = p_pesanan_id FOR UPDATE;

    IF v_pesanan IS NULL THEN
        RAISE EXCEPTION 'Pesanan % tidak ditemukan', p_pesanan_id;
    END IF;

    IF v_pesanan.is_tagihan = FALSE THEN
        RAISE EXCEPTION 'Pesanan ini adalah dokumen pengiriman/titip, bukan tagihan. Tidak bisa dicatat pembayarannya.';
    END IF;

    IF v_pesanan.status_pembayaran = 'dibatalkan' THEN
        RAISE EXCEPTION 'Tagihan % telah dibatalkan. Pembayaran tidak dapat diproses.', v_pesanan.nomor_nota;
    END IF;

    IF v_pesanan.status_pembayaran = 'lunas' AND v_pesanan.sisa_tagihan <= 0 THEN
        RAISE EXCEPTION 'Tagihan % sudah lunas sepenuhnya.', v_pesanan.nomor_nota;
    END IF;

    v_total_pengurang := v_bayar + v_potongan;
    IF v_total_pengurang <= 0 THEN
        RAISE EXCEPTION 'Total pembayaran dan/atau potongan harus lebih dari 0';
    END IF;

    IF v_total_pengurang > v_pesanan.sisa_tagihan THEN
        RAISE EXCEPTION 'Total pembayaran + potongan (Rp %) melebihi sisa tagihan (Rp %)',
            TO_CHAR(v_total_pengurang, 'FM999,999,999,990D00'),
            TO_CHAR(v_pesanan.sisa_tagihan, 'FM999,999,999,990D00');
    END IF;

    IF v_bayar > 0 AND p_akun_kas_id IS NULL THEN
        RAISE EXCEPTION 'Akun kas penerima pembayaran wajib dipilih jika ada nominal bayar kas/transfer';
    END IF;

    IF v_pengguna_id IS NULL THEN
        SELECT id INTO v_pengguna_id FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
    END IF;

    v_sisa_baru := GREATEST(0.00, v_pesanan.sisa_tagihan - v_total_pengurang);
    IF v_sisa_baru <= 0 THEN
        v_status_baru := 'lunas';
    ELSE
        v_status_baru := 'sebagian';
    END IF;

    -- Update pesanan
    UPDATE public.pesanan
    SET total_dibayar = COALESCE(total_dibayar, 0) + v_bayar,
        total_diskon = COALESCE(total_diskon, 0) + v_potongan,
        sisa_tagihan = v_sisa_baru,
        status_pembayaran = v_status_baru,
        akun_kas_id = COALESCE(p_akun_kas_id, akun_kas_id),
        diubah_pada = NOW()
    WHERE id = p_pesanan_id;

    -- Update piutang berjalan pelanggan
    UPDATE public.pelanggan
    SET total_piutang_berjalan = GREATEST(0.00, COALESCE(total_piutang_berjalan, 0) - v_total_pengurang),
        diubah_pada = NOW()
    WHERE id = v_pesanan.pelanggan_id;

    -- Jika ada uang riil masuk kas (v_bayar > 0)
    IF v_bayar > 0 THEN
        SELECT saldo_saat_ini INTO v_saldo_lama FROM public.akun_kas WHERE id = p_akun_kas_id FOR UPDATE;
        v_saldo_baru := COALESCE(v_saldo_lama, 0) + v_bayar;

        UPDATE public.akun_kas
        SET saldo_saat_ini = v_saldo_baru,
            diubah_pada = NOW()
        WHERE id = p_akun_kas_id;

        v_ket_kas := COALESCE(p_keterangan, 'Pelunasan Tagihan Konsinyasi: ' || v_pesanan.nomor_nota);
        IF v_potongan > 0 THEN
            v_ket_kas := v_ket_kas || ' (Potongan/Adjustment: Rp ' || TO_CHAR(v_potongan, 'FM999,999,999,990D00') || COALESCE(' - ' || p_alasan_potongan, '') || ')';
        END IF;

        INSERT INTO public.arus_kas (
            akun_kas_id, tanggal_transaksi, jenis_kas, kategori,
            nominal, saldo_berjalan, referensi_tabel, referensi_id,
            keterangan, dicatat_oleh, dibuat_pada
        ) VALUES (
            p_akun_kas_id, v_tgl_transaksi, 'masuk', 'penjualan',
            v_bayar, v_saldo_baru, 'pesanan', p_pesanan_id,
            v_ket_kas, v_pengguna_id, NOW()
        ) RETURNING id INTO v_transaksi_kas_id;
    END IF;

    RETURN jsonb_build_object(
        'success', true,
        'pesanan_id', p_pesanan_id,
        'nomor_nota', v_pesanan.nomor_nota,
        'nominal_dibayar', v_bayar,
        'nominal_potongan', v_potongan,
        'total_pengurang', v_total_pengurang,
        'sisa_tagihan', v_sisa_baru,
        'status_pembayaran', v_status_baru,
        'transaksi_kas_id', v_transaksi_kas_id
    );
END;
$function$;

REVOKE EXECUTE ON FUNCTION public.fn_catat_pembayaran_konsinyasi(uuid, uuid, numeric, uuid, text, date, numeric, text) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_catat_pembayaran_konsinyasi(uuid, uuid, numeric, uuid, text, date, numeric, text) TO postgres, service_role;

COMMENT ON FUNCTION public.fn_catat_pembayaran_konsinyasi IS 'Mencatat pelunasan/cicilan tagihan konsinyasi dengan dukungan potongan adjustment retur susulan';

COMMIT;

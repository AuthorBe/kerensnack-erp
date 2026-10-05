-- ==============================================================================
-- KEREN ONE MIGRATION: 92_cleanup_obsolete_database_objects.sql
-- Pembersihan Aset Database Usang & Penyelarasan Skema:
-- 1. Lepas ketergantungan view v_karyawan_info terhadap karyawan_legacy_id
-- 2. Drop indeks duplikat idx_pengguna_nama_pengguna_lower pada tabel pengguna
-- 3. Drop fungsi usang/duplikat: fn_revisi_dan_rekonsiliasi_piutang_pelanggan & fn_buat_tagihan_kunjungan_konsinyasi
-- 4. Perbarui fn_catat_log_aktivitas untuk melepas parameter id_pesan_telegram
-- 5. Drop 13 kolom usang (10 legacy IDs & 3 dead features)
-- ==============================================================================

BEGIN;

-- 1. Lepas ketergantungan view v_karyawan_info dari kolom karyawan_legacy_id
DROP VIEW IF EXISTS public.v_karyawan_info;

CREATE VIEW public.v_karyawan_info AS
 SELECT k.id,
    k.pengguna_id,
    p.nama_lengkap AS nama_karyawan,
    p.nama_panggilan,
    p.jenis_kelamin,
    p.tanggal_lahir,
    p.nik,
    p.nik_pending,
    p.posisi,
    COALESCE(p.nomor_whatsapp, p.nomor_telepon) AS nomor_telepon,
    p.nomor_polisi_kendaraan,
    p.alamat,
    p.tanggal_bergabung,
    p.bank_nama,
    p.bank_nomor_rekening,
    p.bank_atas_nama,
    p.status_aktif,
    p.nama_pengguna,
    k.tipe_penggajian,
    k.gaji_pokok_bulanan,
    k.uang_kehadiran_harian,
    k.tunjangan_bulanan,
    k.dibuat_pada,
    k.diubah_pada,
    p.nomor_whatsapp,
    p.peran_id
   FROM public.karyawan k
     JOIN public.pengguna p ON p.id = k.pengguna_id;

GRANT SELECT ON public.v_karyawan_info TO authenticated, service_role;

-- 2. Hapus indeks duplikat identik pada tabel pengguna
DROP INDEX IF EXISTS public.idx_pengguna_nama_pengguna_lower;

-- 3. Hapus fungsi-fungsi PL/pgSQL usang & duplikat
DROP FUNCTION IF EXISTS public.fn_revisi_dan_rekonsiliasi_piutang_pelanggan(uuid);
DROP FUNCTION IF EXISTS public.fn_buat_tagihan_kunjungan_konsinyasi(uuid[], uuid);

-- 4. Perbarui fn_catat_log_aktivitas (drop signature lama yang membawa id_pesan_telegram)
DROP FUNCTION IF EXISTS public.fn_catat_log_aktivitas(uuid, character varying, character varying, character varying, character varying, character varying, character varying, uuid, text, jsonb, jsonb, bigint);

CREATE OR REPLACE FUNCTION public.fn_catat_log_aktivitas(
    p_pengguna_id uuid DEFAULT NULL::uuid,
    p_nama_aktor character varying DEFAULT 'Sistem Otomasi n8n'::character varying,
    p_peran_aktor character varying DEFAULT 'ai_n8n'::character varying,
    p_sumber_aksi character varying DEFAULT 'n8n_automation'::character varying,
    p_kategori_aktivitas character varying DEFAULT 'ai_interaction'::character varying,
    p_jenis_aksi character varying DEFAULT 'EXECUTE'::character varying,
    p_tabel_terdampak character varying DEFAULT NULL::character varying,
    p_id_referensi uuid DEFAULT NULL::uuid,
    p_deskripsi_aktivitas text DEFAULT ''::text,
    p_data_sebelum jsonb DEFAULT NULL::jsonb,
    p_data_sesudah jsonb DEFAULT NULL::jsonb
)
RETURNS uuid
LANGUAGE plpgsql
SECURITY DEFINER
SET search_path TO 'public', 'pg_temp'
AS $function$
DECLARE
    v_log_id UUID;
BEGIN
    INSERT INTO public.log_aktivitas (
        pengguna_id, nama_aktor, peran_aktor, sumber_aksi, kategori_aktivitas,
        jenis_aksi, tabel_terdampak, id_referensi, deskripsi_aktivitas,
        data_sebelum, data_sesudah, waktu_kejadian
    ) VALUES (
        p_pengguna_id, p_nama_aktor, p_peran_aktor, p_sumber_aksi, p_kategori_aktivitas,
        p_jenis_aksi, p_tabel_terdampak, p_id_referensi, p_deskripsi_aktivitas,
        p_data_sebelum, p_data_sesudah, NOW()
    ) RETURNING id INTO v_log_id;

    RETURN v_log_id;
END;
$function$;

REVOKE EXECUTE ON FUNCTION public.fn_catat_log_aktivitas(uuid, character varying, character varying, character varying, character varying, character varying, character varying, uuid, text, jsonb, jsonb) FROM PUBLIC, anon, authenticated;
GRANT EXECUTE ON FUNCTION public.fn_catat_log_aktivitas(uuid, character varying, character varying, character varying, character varying, character varying, character varying, uuid, text, jsonb, jsonb) TO postgres, service_role;

-- 5. Hapus 13 kolom usang (10 legacy IDs & 3 dead features)
ALTER TABLE public.absensi DROP COLUMN IF EXISTS id_legacy;
ALTER TABLE public.kasbon DROP COLUMN IF EXISTS id_legacy;
ALTER TABLE public.penarikan_gaji DROP COLUMN IF EXISTS id_legacy;
ALTER TABLE public.penggajian DROP COLUMN IF EXISTS id_legacy;
ALTER TABLE public.potongan_kasbon DROP COLUMN IF EXISTS id_legacy;
ALTER TABLE public.produksi_harian DROP COLUMN IF EXISTS id_legacy;
ALTER TABLE public.rincian_penggajian DROP COLUMN IF EXISTS id_legacy;
ALTER TABLE public.tabungan DROP COLUMN IF EXISTS id_legacy;
ALTER TABLE public.transaksi_tabungan DROP COLUMN IF EXISTS id_legacy;

ALTER TABLE public.pengguna DROP COLUMN IF EXISTS karyawan_legacy_id;

ALTER TABLE public.log_aktivitas DROP COLUMN IF EXISTS id_pesan_telegram;
ALTER TABLE public.surat_jalan DROP COLUMN IF EXISTS url_pdf_dokumen;
ALTER TABLE public.rincian_penggajian DROP COLUMN IF EXISTS total_tunjangan;

COMMIT;

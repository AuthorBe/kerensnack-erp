-- ==============================================================================
-- 45_cleanup_approvals_and_hardened_owner.sql
-- Pembersihan Total Seluruh Fitur & Kolom Approval di Database
-- Sesuai Arahan: Teknis approval ditiadakan, alur operasional langsung siap kirim.
-- ==============================================================================

BEGIN;

-- 1. DROP TRIGGER & FUNCTION DRAF PENGELUARAN APPROVAL
DROP TRIGGER IF EXISTS trg_draf_pengeluaran_approval ON public.draf_pengeluaran;
DROP FUNCTION IF EXISTS public.fn_trg_draf_pengeluaran_approval();

-- 2. DROP TABEL DRAF PENGELUARAN (Pencatatan pengeluaran sekarang 100% via public.arus_kas)
DROP TABLE IF EXISTS public.draf_pengeluaran CASCADE;

-- 3. MIGRASI STATUS SURAT JALAN & PERBARUI CONSTRAINT
-- Update semua surat jalan lama yang tertahan di status approval ke status 'siap_kirim'
UPDATE public.surat_jalan 
SET status_surat_jalan = 'siap_kirim' 
WHERE status_surat_jalan IN ('draf_n8n', 'disetujui_owner', 'menunggu_persetujuan');

-- Update surat jalan yang ditolak owner menjadi 'gagal_kembali' atau 'gagal_kirim'
UPDATE public.surat_jalan 
SET status_surat_jalan = 'gagal_kirim' 
WHERE status_surat_jalan = 'ditolak_owner';

-- Update check constraint surat_jalan: buang status approval, gunakan status operasional murni
ALTER TABLE public.surat_jalan DROP CONSTRAINT IF EXISTS surat_jalan_status_surat_jalan_check;
ALTER TABLE public.surat_jalan ADD CONSTRAINT surat_jalan_status_surat_jalan_check
CHECK (((status_surat_jalan)::text = ANY ((ARRAY[
    'siap_kirim'::character varying,
    'sedang_dikirim'::character varying,
    'selesai_diterima'::character varying,
    'gagal_kembali'::character varying,
    'gagal_kirim'::character varying
])::text[])));

-- Set default status_surat_jalan menjadi 'siap_kirim'
ALTER TABLE public.surat_jalan ALTER COLUMN status_surat_jalan SET DEFAULT 'siap_kirim';

-- 4. MIGRASI STATUS PEMROSESAN PESANAN & PERBARUI CONSTRAINT
-- Update pesanan lama yang berstatus 'menunggu_approval' atau 'disetujui' ke 'po'
UPDATE public.pesanan 
SET status_pemrosesan = 'po' 
WHERE status_pemrosesan IN ('menunggu_approval', 'disetujui');

-- Update check constraint pesanan_status_pemrosesan_check
ALTER TABLE public.pesanan DROP CONSTRAINT IF EXISTS pesanan_status_pemrosesan_check;
ALTER TABLE public.pesanan ADD CONSTRAINT pesanan_status_pemrosesan_check
CHECK (((status_pemrosesan)::text = ANY ((ARRAY[
    'po'::character varying,
    'siap_dikirim'::character varying,
    'siap_kirim'::character varying,
    'sedang_dikirim'::character varying,
    'selesai_dikirim'::character varying,
    'selesai_diterima'::character varying,
    'selesai'::character varying,
    'gagal_dikirim'::character varying,
    'dibatalkan'::character varying
])::text[])));

-- Set default status_pemrosesan menjadi 'po'
ALTER TABLE public.pesanan ALTER COLUMN status_pemrosesan SET DEFAULT 'po';

-- 5. BERSIHKAN PERMISSION APPROVAL DARI RBAC
DELETE FROM public.izin_peran 
WHERE izin_id IN (SELECT id FROM public.izin WHERE kode_izin IN ('owner.approval_cash', 'owner.approval_delivery'));

DELETE FROM public.izin_pengguna 
WHERE izin_id IN (SELECT id FROM public.izin WHERE kode_izin IN ('owner.approval_cash', 'owner.approval_delivery'));

DELETE FROM public.izin 
WHERE kode_izin IN ('owner.approval_cash', 'owner.approval_delivery');

COMMIT;

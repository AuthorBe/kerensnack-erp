-- ==============================================================================
-- Migration 35: Upgrade Master Pemasok (Vendor) Lengkap
-- Menambahkan Link Lokasi Google Maps, PIC Kontak, WhatsApp, Email, Termin Bayar, dan Catatan
-- ==============================================================================

ALTER TABLE public.pemasok 
    ADD COLUMN IF NOT EXISTS link_google_maps TEXT DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS nama_kontak VARCHAR(100) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS nomor_whatsapp VARCHAR(25) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS email VARCHAR(150) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS termin_bayar VARCHAR(30) DEFAULT 'cash',
    ADD COLUMN IF NOT EXISTS catatan TEXT DEFAULT NULL;

COMMENT ON COLUMN public.pemasok.link_google_maps IS 'Tautan Google Maps titik presisi gudang/toko vendor untuk navigasi armada belanja PO';
COMMENT ON COLUMN public.pemasok.nama_kontak IS 'Nama Person in Charge (PIC) / Kontak Sales dari pihak pemasok';
COMMENT ON COLUMN public.pemasok.nomor_whatsapp IS 'Nomor kontak WhatsApp PIC pemasok';
COMMENT ON COLUMN public.pemasok.email IS 'Alamat email resmi pemasok untuk pengiriman Purchase Order (PO)';
COMMENT ON COLUMN public.pemasok.termin_bayar IS 'Ketentuan pembayaran default: cash, transfer, tempo_7_hari, tempo_14_hari, tempo_30_hari';
COMMENT ON COLUMN public.pemasok.catatan IS 'Catatan operasional, jam buka pengiriman, MOQ atau ketentuan khusus vendor';

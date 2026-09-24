-- Migration 08: Tambah kolom link_google_maps ke tabel pelanggan
-- Digunakan untuk navigasi presisi driver pengiriman
ALTER TABLE public.pelanggan 
ADD COLUMN IF NOT EXISTS link_google_maps TEXT DEFAULT NULL;

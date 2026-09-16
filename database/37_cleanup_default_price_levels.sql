-- database/37_cleanup_default_price_levels.sql
-- ==============================================================================
-- MIGRASI 37: PEMBERSIHAN LEVEL HARGA DEFAULT DUMMY (HANYA LEVEL 1 SEBAGAI DEFAULT)
-- ==============================================================================
-- Grup produk yang baru dibuat secara default hanya memiliki 1 level harga:
-- Level 1 (Ritel Standar / POS). Level-level lainnya (Level 2 s/d 30) dikonfigurasi
-- manual melalui menu /pricing sesuai kebutuhan bisnis riil.
-- Script ini membersihkan seluruh baris dummy level 5, 8, dan 12 yang ter-generate otomatis.

DELETE FROM public.grup_produk_harga_level
WHERE level_harga IN (5, 8, 12);

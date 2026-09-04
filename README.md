# KEREN SNACK ERP — Enterprise Distribution & Manufacturing Management System

Sistem Enterprise Resource Planning (ERP), Point of Sales (POS), Manajemen Distribusi Konsinyasi, dan Logistik Terpadu untuk industri manufaktur repacking dan distribusi makanan ringan KEREN Snack.

---

## 🌟 Fitur Utama & Modul Sistem

- 📱 **Modul Konsinyasi & Sales Mobile:** Kunjungan toko lapangan, kalkulator opname fisik rak instan, otomasi penerbitan faktur piutang, dan transparansi komisi penjualan sales canvaser.
- 🚚 **Logistik & Driver Mobile Manifest:** Manajemen surat jalan pengiriman, proteksi kerahasiaan harga untuk supir, dan konfirmasi barang sampai di toko secara realtime.
- 👑 **Owner Command Center:** Gatekeeper persetujuan pengiriman satu pintu, komparasi omzet, analisis penuaan piutang (*aging*), dan valuasi beban kerugian barang rusak (*BS*).
- 🛒 **Kasir POS & Penjualan B2B:** Layar transaksi kasir retail cepat, pesanan grosir, dan integrasi matriks 28 tier level harga pelanggan.
- 📦 **Gudang & Produksi Packing:** Monitoring stok fisik multi-satuan, BOM (*Bill of Materials*), dan penggajian buruh packing borongan.
- 💰 **Keuangan & Buku Kas:** Manajemen multi-rekening kas/bank, jurnal arus kas masuk/keluar, dan laporan cash flow otomatis.

---

## 🏗️ Arsitektur & Spesifikasi Teknis

Dokumentasi arsitektur modular, alur data, pengendali (*controllers*), antarmuka (*views*), skema basis data, dan matriks hak akses (*RBAC*) terangkum lengkap pada:
👉 [**Dokumen Spesifikasi Arsitektur Sistem (`docs/ARCHITECTURE.md`)**](docs/ARCHITECTURE.md)

---

## 📂 Ringkasan Direktori Proyek

```text
kerensnack-erp/
├── app/                  # Application Core, Controllers & Helpers (MVC)
├── config/               # Database Singleton PDO Configuration
├── database/             # PostgreSQL Schema, Stored Procedures (RPC) & Seeds
├── docs/                 # System Architecture, PRD, & Technical Specifications
├── public/               # Public Web Root (CSS, JS, Fonts, Assets & Entry Point)
└── views/                # Server-Side Rendered Blade/PHP Views & Alpine Components
```

---

## 🚀 Instalasi & Setup Cepat

1. **Clone repositori:**
   ```bash
   git clone https://github.com/AuthorBe/kerensnack-erp.git
   cd kerensnack-erp
   ```

2. **Instal dependensi Composer (PhpSpreadsheet & Dompdf):**
   ```bash
   composer install
   ```

3. **Konfigurasi Environment:**
   Salin `.env.example` menjadi `.env` dan lengkapi kredensial koneksi basis data Supabase:
   ```bash
   cp .env.example .env
   ```

4. **Inisialisasi Database (PostgreSQL / Supabase):**
   Eksekusi berkas skema dan RPC yang terdapat pada folder `database/`:
   - `01_schema.sql` (Struktur tabel)
   - `02_triggers_and_rpc.sql` (Stored procedures & kalkulator harga dinamis)
   - `seeds/` (Master data awal)

5. **Validasi Konektivitas Database:**
   ```bash
   php test_db.php
   ```

---
*Copyright © 2026 KEREN SNACK ERP. All rights reserved.*


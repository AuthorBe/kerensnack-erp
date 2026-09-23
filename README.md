# KEREN ONE — Enterprise Distribution & Manufacturing Management System

Sistem Enterprise Resource Planning (ERP), Point of Sales (POS), Manajemen Distribusi Konsinyasi, dan Logistik Terpadu yang dirancang khusus untuk operasional manufaktur repacking dan rantai pasok distribusi makanan ringan **Keren One**.

Aplikasi ini mengintegrasikan seluruh lini bisnis end-to-end: mulai dari pengadaan bahan mentah dari pemasok, pemrosesan repacking borongan, pencatatan stok multi-satuan, penjualan ritel POS, pengiriman armada logistik, hingga audit konsinyasi rak toko dan pembukuan arus kas keuangan.

---

## 📖 Tentang Proyek

### Latar Belakang & Masalah Bisnis
Industri distribusi makanan ringan berbasis konsinyasi memiliki dinamika operasional yang sangat kompleks:
- **Toko Konsinyasi & Titip Jual:** Rekonsiliasi stok rak toko sering mengalami selisih, retur barang rusak (*BS*), barang hilang, serta tagihan yang tertunda.
- **Produksi Repacking & Upah Borongan:** Bahan curah (*bal-balan*) dikemas ulang menjadi ratusan varian kemasan kecil dengan skema upah pengemasan borongan per bungkus.
- **Logistik Lapangan:** Pengantaran barang oleh supir dan penagihan oleh sales memerlukan pemisahan wewenang (*separation of duty*) yang ketat serta bukti serah terima digital (*Proof of Delivery*).
- **Integritas Keuangan & Multi-Tier Pricing:** Matriks harga yang dinamis antar tipe pelanggan (ritel, grosir, toko rak) menuntut kalkulasi harga otomatis di level database agar mencegah kesalahan kasir maupun manipulasi data.

KEREN ONE hadir sebagai solusi terpadu (*single source of truth*) yang menggabungkan seluruh modul operasional ke dalam satu ekosistem tersentralisasi, andal, dan berkecepatan tinggi.

---

## 🏗️ Spesifikasi Teknis & Arsitektur Aplikasi

Sistem dibangun dengan arsitektur modern berorientasi performa tinggi, keamanan ketat, dan efisiensi sumber daya server:

### 1. Backend & Pola Desain (MVC)
- **Bahasa & Runtime:** PHP 8.1+ (*Strict Types enabled*).
- **Pola Arsitektur:** Native MVC (*Model-View-Controller*) modular dan terstruktur tanpa overhead framework berat, dirancang untuk eksekusi ultra-cepat di server lokal maupun cloud.
- **Routing & Keamanan:** Router terpusat dengan deteksi otomatis rute web/API, proteksi **CSRF Token** pada setiap mutasi data (`POST`, `PUT`, `DELETE`), serta filter sesi berbasis peran (*Role-Based Access Control*).
- **Audit Trail:** Modul terintegrasi `ActivityLog` untuk mencatat rekam jejak aktivitas krusial pengguna secara transparan.

### 2. Basis Data & Logic Engine (PostgreSQL / Supabase)
- **Engine Database:** PostgreSQL 15+ (kompatibel penuh dengan Supabase Cloud & PostgreSQL on-premise).
- **Single Source of Truth (SSOT):** Skema kanonikal terpadu (`01_schema.sql`) yang memuat 45+ tabel relasional, view kanonikal, indeks performa, dan integritas *foreign keys* ketat.
- **Stored Procedures & RPC (PL/pgSQL):** Logika bisnis esensial (seperti dynamic pricing matrix, kalkulasi opname rak konsinyasi, mutasi kartu stok, dan rekonsiliasi piutang) dieksekusi langsung di level database (`02_triggers_and_rpc.sql`) guna menjamin konsistensi data secara atomik (*ACID*).
- **Database Triggers:** Otomasi mutasi arus kas dari persetujuan draf beban, pembaruan saldo tabungan karyawan, dan proteksi akun pengembang sistem.

### 3. Antarmuka Pengguna (Frontend UI/UX)
- **Templating:** Blade-style Server-Side Rendered (SSR) PHP Views yang ringan, cepat, dan SEO-friendly.
- **Desain & Responsivitas:** Antarmuka responsif (*Mobile-First*) yang nyaman diakses melalui smartphone oleh sales canvaser dan supir armada di lapangan, maupun desktop oleh admin dan owner.
- **Interaktivitas:** Alpine.js dan Tailwind/Modern CSS untuk komponen interaktif (modal kasir, kalkulator kembalian, scanner barcode, autocomplete pencarian).
- **Export Dokumen:** Integrasi PhpSpreadsheet untuk rekap laporan Excel serta Dompdf untuk cetak faktur, nota kasir, surat jalan, dan barcode.

---

## 🌟 Modul Utama & Alur Bisnis Sistem

| Modul | Deskripsi Alur Bisnis |
| :--- | :--- |
| 📱 **Distribusi Konsinyasi (Rak Toko)** | Pencatatan kunjungan toko lapangan, kalkulator opname fisik rak instan, pemisahan faktur tagihan dari kunjungan (*decoupled consignment*), valuasi finansial beban kerugian barang rusak (*BS*), dan monitoring akumulasi barang hilang per toko. |
| 🛒 **POS Kasir & Penjualan Grosir** | Layar kasir ritel cepat dengan scanner barcode, kalkulator pembayaran & kembalian tunai/non-tunai, integrasi matriks level harga otomatis (Level 1 s/d 30), dan penerbitan nota kontan/tempo. |
| 🚚 **Logistik & Pengiriman Armada** | Penerbitan surat jalan pengiriman, penugasan supir, rute harian, pencatatan *Proof of Delivery* (POD) foto serah terima toko, serta pencatatan alasan gagal kirim. |
| 📦 **Gudang, Pembelian (PO) & Repacking** | Manajemen inventaris bahan baku curah & kemasan, alur PO pembelian vendor, penugasan belanja kurir, *Bill of Materials* (BOM) repacking, dan sesi bulk opname fisik gudang. |
| 👥 **SDM & Penggajian (HR Payroll)** | Pencatatan upah borongan pengemasan per bungkus, absensi harian, potongan kasbon otomatis, tabungan karyawan, dan penutupan buku gaji mingguan/bulanan. |
| 💰 **Keuangan & Rekening Kas** | Manajemen multi-rekening kas/bank, alur persetujuan (*approval*) draf pengeluaran operasional satu pintu, serta buku besar arus kas masuk/keluar otomatis. |
| 👑 **Owner Command Center & RBAC** | Persetujuan pesanan dan nota tempo, dashboard omzet & profitabilitas, analisis piutang berjalan (*aging*), dan manajemen peran granular (Owner, Admin, Mandor, Sales, Driver). |

---

## 📂 Struktur Direktori Proyek

```text
kerensnack-erp/
├── app/                  # Application Core, Controllers, & Helpers (MVC)
│   ├── Controllers/      # Controller setiap modul bisnis (POS, Konsinyasi, HR, dll.)
│   ├── Core/             # Router, Database Singleton, Auth Engine, & Core Model
│   └── Helpers/          # Utility: CSRF, ActivityLog, Formatter, Upload, Export
├── config/               # Database PDO Configuration & Konfigurasi Sistem
├── database/             # Skema Kanonikal, Stored Procedures, Migrasi & Seeds
│   ├── 01_schema.sql     # Skema DDL kanonikal (SSOT) tabel relasional
│   ├── 02_triggers_and_rpc.sql # Stored Procedures & Triggers PL/pgSQL
│   ├── seeds/            # Data seed awal (RBAC, staf, master produk, pelanggan)
│   └── ARCHITECTURE_ROLES.md # Dokumentasi hak akses dan pemisahan peran
├── docs/                 # Dokumentasi Arsitektur Teknis Sistem
├── public/               # Web Document Root (index.php, CSS, JS, Assets)
├── tests/                # Automated Test Suite (Verifikasi Integritas & Bisnis)
│   └── run_all.php       # Test suite runner terpadu
├── test_db.php           # Skrip diagnostik koneksi DB & kesehatan sistem
└── views/                # Antarmuka Tampilan Server-Side (Blade-style PHP Views)
```

---

## 🚀 Panduan Instalasi & Setup Cepat

### 1. Prasyarat Sistem
- **PHP >= 8.1** dengan ekstensi aktif: `pdo_pgsql`, `pgsql`, `mbstring`, `openssl`, `curl`, `gd`.
- **Composer** (Package Manager PHP).
- **PostgreSQL 15+** atau akun cloud **Supabase**.
- Web server lokal (Laragon / Apache / Nginx) atau PHP Built-in Server.

### 2. Instalasi & Konfigurasi
```bash
# 1. Clone repositori & masuk ke direktori proyek
git clone https://github.com/AuthorBe/kerensnack-erp.git
cd kerensnack-erp

# 2. Instal dependensi library PHP
composer install

# 3. Buat file konfigurasi lingkungan
cp .env.example .env
```
> Buka berkas `.env`, kemudian sesuaikan kredensial database PostgreSQL / Supabase Anda (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE`).

### 3. Inisialisasi Database
Eksekusi berkas SQL berikut secara berurutan pada database PostgreSQL / Supabase Anda (via SQL Editor Supabase, psql, atau GUI database pilihan Anda):
1. **Skema Tabel:** `database/01_schema.sql`
2. **RPC & Trigger:** `database/02_triggers_and_rpc.sql`
3. **Data Master Awal (Seeder):** Eksekusi berkas di folder `database/seeds/`:
   - `01_seed_rbac.sql` *(Peran, akun default, rekening kas)*
   - `02_seed_master_karyawan.sql` *(Tarif borongan & data staf)*
   - `03_seed_master_produk.sql` *(Master produk & tier harga)*
   - `04_seed_master_pelanggan_pemasok.sql` *(Pelanggan, rute toko, vendor)*

### 4. Menjalankan Aplikasi
Pilih salah satu cara menjalankan server lokal:
- **Menggunakan Laragon / Apache:**  
  Arahkan Virtual Host atau buka langsung via browser: `http://localhost/kerensnack-erp/public` (atau `http://localhost/kerensnack-erp`).
- **Menggunakan PHP Built-in Server:**
  ```bash
  php -S localhost:8000 -t public
  ```
  Lalu buka `http://localhost:8000` pada browser Anda.

### 5. Verifikasi & Testing
Jalankan diagnostik dan tes otomatis melalui terminal:
```bash
# Uji koneksi database & integritas RPC
php developer/test_db.php

# Jalankan seluruh rangkaian tes otomatis
php tests/run_all.php
```

---

## 🔒 Kebijakan Keamanan & Privasi Proyek

1. **Kerahasiaan Kredensial:** Berkas `.env` bersifat rahasia dan sudah terdaftar di `.gitignore` agar **tidak pernah** terunggah ke repositori publik. Selalu gunakan `.env.example` sebagai referensi.
2. **Sanitasi Data:** Seluruh data pada direktori `seeds/` menggunakan data sintetis yang aman untuk lingkungan pengembangan bersama tim.
3. **Proteksi Akun Root Developer:** Skema basis data dilengkapi trigger `trg_guard_developer_account` untuk melindungi akun pengembang dari modifikasi atau penghapusan tak disengaja.
4. **Proteksi Mutasi Web:** Seluruh form dan operasi mutasi data wajib divalidasi dengan token CSRF guna mencegah serangan *Cross-Site Request Forgery*.

---

*Copyright © 2026 KEREN SNACK ERP. Seluruh hak cipta dilindungi undang-undang.*

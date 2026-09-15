# KEREN SNACK ERP — Enterprise Distribution & Manufacturing Management System

Sistem Enterprise Resource Planning (ERP), Point of Sales (POS), Manajemen Distribusi Konsinyasi, dan Logistik Terpadu yang dirancang khusus untuk operasional manufaktur repacking dan rantai pasok distribusi makanan ringan **KEREN Snack**.

Aplikasi ini mengintegrasikan seluruh lini bisnis: mulai dari pengadaan bahan mentah dari pemasok, pemrosesan repacking borongan, pencatatan stok multi-satuan, penjualan ritel POS, pengiriman armada logistik, hingga audit konsinyasi rak toko dan buku besar keuangan.

---

## 🏗️ Spesifikasi Teknis & Arsitektur Aplikasi

Sistem dibangun dengan arsitektur modern berorientasi performa tinggi, keamanan ketat, dan kemudahan pemeliharaan:

### 1. Backend & Pola Desain (MVC)
- **Bahasa & Runtime:** PHP 8.1+ (*Strict Types enabled*).
- **Pola Arsitektur:** Native MVC (*Model-View-Controller*) modular, terstruktur tanpa overhead framework berat, dirancang untuk eksekusi ultra-cepat di lingkungan server lokal maupun cloud.
- **Routing & Middleware:** Router terpusat dengan deteksi otomatis rute web/API, aktivasi proteksi **CSRF Token** pada semua request mutasi data (`POST`, `PUT`, `DELETE`), serta filter autentikasi berbasis sesi aman.
- **Audit & Logging:** Modul terintegrasi `ActivityLog` untuk mencatat rekam jejak (*audit trail*) setiap aktivitas krusial pengguna secara komprehensif.

### 2. Basis Data & Logic Engine (PostgreSQL / Supabase)
- **Engine Database:** PostgreSQL (kompatibel penuh dengan Supabase Cloud & PostgreSQL on-premise).
- **Single Source of Truth (SSOT):** Skema kanonikal terpadu (`01_schema.sql`) yang memuat 45 tabel relasional, 1 view kanonikal, indeks performa tinggi, dan integritas foreign keys ketat.
- **Stored Procedures & RPC (PL/pgSQL):** Logika bisnis esensial (seperti pricing engine, kalkulasi opname rak, mutasi kartu stok, dan rekonsiliasi piutang) dijalankan langsung di level database (`02_triggers_and_rpc.sql`) guna menjamin konsistensi data secara atomik (*ACID*) dan mencegah manipulasi dari sisi klien.
- **Model Pengguna & Pegawai Terpadu:** Penggabungan entitas identitas, kredensial, dan jabatan pada tabel `pengguna`, yang direlasikan 1-to-1 dengan parameter penggajian di tabel `karyawan` serta view `v_karyawan_info`.
- **Database Triggers:** Otomasi mutasi arus kas dari persetujuan draf beban, pembaruan saldo tabungan, dan proteksi akun pengembang sistem.

### 3. Antarmuka Pengguna (Frontend UI/UX)
- **Templating:** Blade-style Server-Side Rendered (SSR) PHP Views yang ringan dan cepat.
- **Desain & Responsivitas:** Tata letak responsif (*Mobile-First*) yang nyaman diakses melalui smartphone oleh sales canvaser dan supir armada di lapangan, maupun desktop oleh admin dan owner.
- **Interaktivitas:** Alpine.js dan Tailwind/Custom CSS untuk komponen interaktif (modal, kalkulator kasir, autocomplete).
- **Export Dokumen:** Integrasi PhpSpreadsheet untuk rekap laporan Excel serta Dompdf untuk cetak faktur, surat jalan, dan barcode.

---

## 🌟 Modul Utama & Alur Bisnis Sistem

- 📱 **Modul Distribusi Konsinyasi (Rak Toko):**
  Pencatatan kunjungan toko lapangan, kalkulator opname fisik rak instan, pemisahan faktur tagihan dari kunjungan (*decoupled consignment*), valuasi finansial beban kerugian barang rusak (*BS*), dan monitoring akumulasi barang hilang per toko.
- 🛒 **Point of Sales (POS) & Penjualan Grosir:**
  Layar kasir ritel cepat dengan scanner barcode, kalkulator uang diterima & kembalian, integrasi matriks level harga otomatis (Level 1 s/d 28+ sesuai grup pelanggan), dan penerbitan nota kontan/tempo.
- 🚚 **Logistik & Pengiriman Armada:**
  Penerbitan surat jalan pengiriman, penugasan driver, rute harian, pencatatan *Proof of Delivery* (POD) foto penerimaan toko, serta manajemen gagal kirim.
- 📦 **Gudang, Pembelian (PO) & Repacking:**
  Manajemen inventaris bahan baku curah & kemasan, alur PO pembelian vendor, penugasan belanja kurir, BOM (*Bill of Materials*) repacking, dan sesi bulk opname fisik gudang.
- 👥 **SDM & Penggajian (HR Payroll):**
  Pencatatan upah borongan buruh packing per bungkus, absensi harian, potongan kasbon otomatis, saldo tabungan, dan penutupan buku gaji mingguan/bulanan.
- 💰 **Keuangan & Rekening Kas:**
  Manajemen multi-rekening kas/bank, alur persetujuan (*approval*) draf pengeluaran operasional satu pintu, serta buku besar arus kas masuk/keluar otomatis.
- 👑 **Owner Command Center & Hak Akses (RBAC):**
  Persetujuan pesanan dan nota tempo, dashboard omzet & profitabilitas, analisis piutang berjalan (*aging*), dan manajemen peran granular (Owner, Admin, Mandor, Sales, Driver).

---

## 📂 Struktur Direktori Proyek

```text
kerensnack-erp/
├── app/                  # Application Core, Controllers, & Helpers (MVC)
│   ├── Controllers/      # Controller setiap modul (POS, Konsinyasi, Produk, dll.)
│   ├── Core/             # Router, Auth Engine, & Core Abstractions
│   └── Helpers/          # Utility: CSRF, ActivityLog, Formatter, Upload, Export
├── config/               # Database Singleton PDO Configuration
├── database/             # Skema Kanonikal, Stored Procedures, Migrasi & Seeds
│   ├── 01_schema.sql     # Skema DDL kanonikal (SSOT) 45 tabel relasional
│   ├── 02_triggers_and_rpc.sql # Stored Procedures & Triggers PL/pgSQL
│   ├── seeds/            # Data seed inisialisasi awal (RBAC, staf, produk, toko)
│   └── README.md         # Dokumentasi riwayat migrasi dan arsitektur DB
├── docs/                 # Dokumentasi Arsitektur Teknis Sistem
├── public/               # Web Document Root (Entry Point index.php, CSS, JS, Assets)
├── tests/                # Automated Extreme Test Suites (Verifikasi Integritas)
└── views/                # Antarmuka Tampilan Server-Side (Blade/PHP Views)
```

---

## 🚀 Panduan Instalasi & Setup Cepat

Bagi pengembang atau tim yang baru meng-clone repositori:

### 1. Clone Repositori
```bash
git clone https://github.com/AuthorBe/kerensnack-erp.git
cd kerensnack-erp
```

### 2. Instal Dependensi
Pastikan Composer telah terpasang pada komputer Anda:
```bash
composer install
```

### 3. Konfigurasi Lingkungan (.env)
Salin berkas template lingkungan `.env.example` menjadi `.env`, lalu lengkapi kredensial koneksi basis data Anda:
```bash
cp .env.example .env
```
Sesuaikan parameter database di file `.env` (Host, Port, Database, Username, Password, SSL mode).

### 4. Inisialisasi Basis Data (PostgreSQL / Supabase)
Untuk database baru, Anda cukup mengeksekusi berkas kanonikal secara berurutan:
1. **Struktur Tabel:** Eksekusi `database/01_schema.sql`
2. **Fungsi RPC & Trigger:** Eksekusi `database/02_triggers_and_rpc.sql`
3. **Data Master Awal (Seeding):** Eksekusi berkas pada `database/seeds/` secara berurutan:
   - `seeds/01_seed_rbac.sql` (Peran, hak akses, rekening kas default)
   - `seeds/02_seed_master_karyawan.sql` (Tarif borongan, profil pengguna staf, payroll)
   - `seeds/03_seed_master_produk.sql` (Grup produk, 145 SKU varian rasa, tier harga)
   - `seeds/04_seed_master_pelanggan_pemasok.sql` (Wilayah rute, grup pelanggan, toko contoh, vendor)

### 5. Validasi Kesehatan Sistem & Database
Jalankan skrip pemeriksaan mandiri via terminal:
```bash
php test_db.php
```
*Skrip ini akan memvalidasi konektivitas SSL PDO, ketersediaan 45 tabel, integritas master data, kalkulator dynamic pricing RPC, dan lookup barcode universal.*

### 6. Menjalankan Automated Tests (Opsional)
Untuk memastikan seluruh modul dan validasi data beroperasi 100%:
```bash
php tests/audit_tahap1_tahap2_extreme.php
php tests/audit_tahap3_tahap4_extreme.php
php tests/audit_customer_integrity_extreme.php
```

---

## 🔒 Kebijakan Keamanan & Privasi Proyek

Untuk menjaga kerahasiaan dan integritas operasional:
1. **Kerahasiaan Kredensial:** Berkas `.env` bersifat rahasia dan telah dikonfigurasi pada `.gitignore` agar **tidak pernah** terunggah ke repositori publik. Selalu gunakan `.env.example` sebagai referensi aman tanpa mencantumkan password riil.
2. **Sanitasi Data:** Seluruh data pada direktori `seeds/` menggunakan informasi contoh / sintetis yang aman dibagikan ke seluruh tim pengembang tanpa memuat data pribadi sensitif.
3. **Proteksi Akun Root Developer:** Skema database dilengkapi trigger `trg_guard_developer_account` untuk melindungi akun pengembang dari modifikasi atau penghapusan yang tidak disengaja.
4. **Proteksi Transaksi Web:** Seluruh form dan operasi mutasi data wajib divalidasi dengan token CSRF guna mencegah serangan *Cross-Site Request Forgery*.

---

*Copyright © 2026 KEREN SNACK ERP. Seluruh hak cipta dilindungi undang-undang.*

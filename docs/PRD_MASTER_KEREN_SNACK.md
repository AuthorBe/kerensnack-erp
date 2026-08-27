# Product Requirements Document (PRD) Master v2.2
## Sistem Operasional Terpadu & Karyawan AI (KEREN Snack)
**Single Source of Truth (SSOT), Dynamic Pricing Engine, Consignment Ledger, AI Workforce, Full HR Payroll & Enterprise Security**

---

## 1. Ringkasan Eksekutif & Visi Sistem

### 1.1 Latar Belakang & Masalah Riil Bisnis
**KEREN Snack** adalah perusahaan manufaktur repacking dan distribusi makanan ringan (snack curah, kerupuk, berondong beras, opak, singkong, makaroni, dll.) skala besar yang melayani ratusan toko di wilayah Jabodetabek dan sekitarnya. Saat ini operasional menghadapi beberapa tantangan krusial:
1. **Model Bisnis Repacking Fleksibel**: Membeli bahan baku dalam bentuk **Bal / Curah Besar (kg/bal)**, lalu dipecah (*repack*) menjadi **Pcs / Bungkus**. Tiap jenis produk memiliki rasio konversi (*yield*) berbeda-beda (misal: 1 bal curah 5kg bisa menghasilkan 37 bungkus 135gr + sisa gramasi).
2. **Matriks Harga Super Kompleks (Unlimited Level Harga &ge; 1)**: 1 item produk memiliki puluhan variasi harga jual berdasarkan tier pelanggan (ritel, grosir kecil, grosir besar, agen pasar, mitra warung, konsinyasi), metode pembayaran (cash vs tempo 7/14/30 hari), dan volume pembelian. IPOS 5 yang hanya mendukung 4 level harga kaku (`tbl_itemhj`) sudah tidak mampu menampung kebutuhan ini.
3. **Barcode Kemasan Universal (Multi-Rasa 1 Label)**: Kemasan plastik luar *pre-printed* memiliki 1 barcode universal dari pabrik yang digunakan bersama oleh beberapa varian rasa (contoh: Kemasan Singkong 250gr ber-barcode `899123456001` dipakai untuk rasa Asin Gurih, Manis Pedas, dan Opak Balado).
4. **Model Konsinyasi (Titip Jual Rak Toko)**: Banyak toko langganan menggunakan skema titip jual di mana tagihan hanya terbit saat barang laku berdasarkan hitung fisik rak toko (*Opname Rak* saat kunjungan mingguan Sales-Driver).
5. **Sales adalah Driver (Sales-Driver / Canvaser Terpadu)**: Staf lapangan yang menyetir mobil/motor rute logistik adalah orang yang sama yang memuat barang ke kendaraan (*loading*), mengantar pesanan, menawarkan snack tambahan di toko (*canvas order*), menagih piutang jatuh tempo, menerima pembayaran tunai, dan melaporkan biaya bensin/tol.
6. **Pencatatan Fragmented & Rawan Selisih**: Data penggajian borongan terpisah di aplikasi PHP/MySQL (`salaryapp`), stok di IPOS 5 & Excel, kasbon dicatat di buku manual, dan pelaporan biaya bensin via chat WhatsApp tanpa validasi otomatis.

### 1.2 Visi Solusi (Next-Gen Autonomous AI ERP)
Membangun **Single Source of Truth (SSOT)** berbasis **PostgreSQL 15+ (Supabase Cloud)** yang terintegrasi penuh dengan **n8n Workflow Automation**, **Google Gemini 2.0 AI**, **Bot Telegram Lapangan**, **Aplikasi Salary PHP**, dan **Web App POS Kasir & Owner Command Center (PHP MVC + Tailwind CSS + Alpine.js)**.

Sistem ini mentransformasikan operasional menjadi **Karyawan AI Mandiri**:
* 80% pekerjaan mekanis/berulang (susun rute manifest pengiriman besok, hitung upah borongan bungkus, parsing biaya bensin, validasi limit piutang, draf surat jalan) diambil alih oleh AI dan Database Triggers.
* Manusia (Owner & Admin Toko) berfokus pada fungsi verifikasi, supervisi, dan persetujuan (*Approval*).

---

## 2. Arsitektur Teknologi & Tech Stack Resmi

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                       SPESIFIKASI TECH STACK KEREN SNACK                                │
├───────────────────────────────┬────────────────────────────────────────────────────────────────────────┤
│ 1. Database & Cloud Backend   │ • PostgreSQL 15+ Hosted di Supabase (Free Tier Rp 0/bln)               │
│                               │ • Row Level Security (RLS) 100% Aktif pada Seluruh 40 Tabel            │
│                               │ • Primary Key UUIDv4 Universal (`gen_random_uuid()`)                   │
│                               │ • Stored Procedures (RPC) & Atomik Triggers (Zero Frontend Math)       │
├───────────────────────────────┼────────────────────────────────────────────────────────────────────────┤
│ 2. Web App POS & Admin ERP    │ • Arsitektur: Native PHP MVC (Sama persis dengan pola `salaryapp`)     │
│                               │ • Database Driver: `pdo_pgsql` (Koneksi Langsung ke Supabase via SSL) │
│                               │ • UI Styling: Tailwind CSS (Modern Minimalist Executive Dark Theme)    │
│                               │ • Reactivity: Alpine.js (Kasir cepat, scan barcode & modal dialog)     │
│                               │ • PDF Engine: DomPDF (Cetak Struk, Nota, Faktur & Slip Gaji)           │
│                               │ • Web Server: Laragon Apache / Nginx (`http://localhost/karyawan_ai`)  │
├───────────────────────────────┼────────────────────────────────────────────────────────────────────────┤
│ 3. Workflow & AI Assistant    │ • Workflow Orchestrator: n8n Automation Engine                         │
│                               │ • Large Language Model: Google Gemini 2.0 Flash (Ekstraksi Chat/Suara) │
│                               │ • Messaging Channel: Telegram Bot API (Sales-Driver, Mandor & Owner)   │
└───────────────────────────────┴────────────────────────────────────────────────────────────────────────┘
```

---

## 3. Enam Prinsip Inti Arsitektur Sistem

1. **Universal Primary Key UUIDv4 (`gen_random_uuid()`):** Menjamin keamanan dari *enumeration attack* dan mencegah bentrok ID saat n8n dan Web App melakukan penulisan data bersamaan.
2. **Zero Frontend Math (Database-Level Calculation):** Perhitungan harga jual diskon, stok fisik, saldo kas, upah borongan, sisa kasbon, saldo tabungan, dan laku konsinyasi **DILARANG DIHITUNG DI FRONTEND**. Seluruh kalkulasi dijalankan oleh **PostgreSQL Triggers & Stored Procedures (RPC)** secara atomik (ACID).
3. **Immutable Audit Trail & Double-Entry Ledger:**
   - Stok fisik hanya bertambah/berkurang lewat tabel buku besar `riwayat_stok`.
   - Kas hanya bertambah/berkurang lewat tabel buku besar `arus_kas`.
   - Setiap aksi sistem/pengguna tercatat di tabel `log_aktivitas` lengkap dengan snapshot forensik `data_sebelum` dan `data_sesudah`.
4. **Soft Delete Pattern:** Master data menggunakan flag `status_aktif = true/false` agar histori transaksi dan laporan keuangan masa lalu tidak pernah rusak.
5. **Row Level Security (RLS) 100% Active:** Akses data dibatasi ketat berdasarkan peran pengguna (`owner`, `admin`, `mandor`, `sales_driver`).
6. **Separation of Concerns (Telegram vs Web App):**
   - *Telegram Bot*: Khusus operasional cepat lapangan (Manifest rute, canvas order, POD foto, catat biaya bensin, tagih uang).
   - *Web App*: Master data, ubah harga master, void nota, penutupan payroll, dan monitoring audit log.

---

## 4. Peta Modul Database (Total 40 Tabel Relasional)

```
[ Modul 1: Autentikasi & RBAC (5 Tabel) ] ───> [ Modul 2: Master Entitas & Grup Pelanggan (5 Tabel) ]
                   │                                                     │
                   ▼                                                     ▼
[ Modul 3: Katalog, BoM & Pricing Matrix (5 Tabel) ] ─> [ Modul 6: Modul Konsinyasi Rak Toko (3 Tabel) ]
                   │                                                     │
                   ▼                                                     ▼
[ Modul 4: Gudang & Ledger Stok (4 Tabel) ] <── [ Modul 7: HR, Absensi & Payroll Borongan (10 Tabel) ]
                   │                                                     │
                   ▼                                                     ▼
[ Modul 5: Penjualan & Surat Jalan (3 Tabel) ] ───────> [ Modul 8: Arus Kas & Audit Trail Master (5 Tabel) ]
```

---

## 5. Rincian Super Lengkap Kamus Data 40 Tabel Database

### MODUL 1: AUTENTIKASI, PENGGUNA & HAK AKSES (RBAC) (5 Tabel)

#### 1. `peran` (Master Roles)
Menyimpan peran pengguna sistem dengan hierarki wewenang.
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`nama_peran`** `VARCHAR(50)` NOT NULL UNIQUE (Nilai: `'owner'`, `'admin'`, `'mandor'`, `'sales_driver'`)
* **`deskripsi`** `TEXT` NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 2. `izin` (Master Permissions)
Daftar izin akses granular untuk seluruh aksi sistem.
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`kode_izin`** `VARCHAR(100)` NOT NULL UNIQUE (Contoh: `'pesanan.create'`, `'kas.approve'`, `'surat_jalan.manifest'`, `'stok.opname'`, `'piutang.collect'`)
* **`nama_izin`** `VARCHAR(100)` NOT NULL
* **`grup_izin`** `VARCHAR(50)` NOT NULL (Nilai: `'Penjualan'`, `'Keuangan'`, `'Gudang'`, `'HR'`, `'Logistik'`)
* **`deskripsi`** `TEXT` NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 3. `izin_peran` (Role-Permission Mapping)
Matriks pemetaan izin default untuk tiap peran.
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`peran_id`** `UUID` NOT NULL REFERENCES `peran(id)` ON DELETE CASCADE
* **`izin_id`** `UUID` NOT NULL REFERENCES `izin(id)` ON DELETE CASCADE
* **`diizinkan`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* *Constraint*: `UNIQUE(peran_id, izin_id)`

#### 4. `pengguna` (App Users & Bot Identity)
Menghubungkan akun Supabase Auth dengan profil karyawan & Telegram Bot.
* **`id`** `UUID` PRIMARY KEY REFERENCES `auth.users(id)` ON DELETE CASCADE
* **`karyawan_id`** `UUID` NULL REFERENCES `karyawan(id)` ON DELETE SET NULL
* **`peran_id`** `UUID` NOT NULL REFERENCES `peran(id)`
* **`nama_lengkap`** `VARCHAR(150)` NOT NULL
* **`nama_pengguna`** `VARCHAR(100)` NOT NULL UNIQUE
* **`id_telegram`** `BIGINT` NULL UNIQUE (Kunci integrasi webhook Telegram Bot)
* **`nomor_whatsapp`** `VARCHAR(25)` NULL UNIQUE
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 5. `izin_pengguna` (User Permission Override)
Pengecualian hak akses khusus untuk pengguna tertentu.
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`pengguna_id`** `UUID` NOT NULL REFERENCES `pengguna(id)` ON DELETE CASCADE
* **`izin_id`** `UUID` NOT NULL REFERENCES `izin(id)` ON DELETE CASCADE
* **`diizinkan`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* *Constraint*: `UNIQUE(pengguna_id, izin_id)`

---

### MODUL 2: MASTER ENTITAS, GRUP PELANGGAN & RELASI BISNIS (5 Tabel)

#### 6. `wilayah` (Rute Logistik Distribusi)
Menyimpan wilayah rute pengiriman Sales-Driver.
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`kode_rute`** `VARCHAR(50)` NOT NULL UNIQUE (Contoh: `'RUTE-TNG-TIMUR'`, `'RUTE-JAKBAR'`, `'RUTE-JABODETABEK'`)
* **`nama_wilayah`** `VARCHAR(100)` NOT NULL
* **`provinsi`** `VARCHAR(100)` NOT NULL DEFAULT `'Banten'`
* **`kota_kabupaten`** `VARCHAR(100)` NOT NULL DEFAULT `'Kota Tangerang'`
* **`sub_wilayah`** `TEXT` NULL
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 7. `karyawan` (Master Pegawai: Borongan, Bulanan & Sales-Driver)
Menyimpan data seluruh karyawan operasional KEREN Snack.
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE (ID referensi migrasi SalaryApp)
* **`nik`** `VARCHAR(50)` NULL UNIQUE
* **`nama_karyawan`** `VARCHAR(150)` NOT NULL
* **`posisi`** `VARCHAR(50)` NOT NULL (Nilai: `'pengemasan'`, `'sales_driver'`, `'admin'`, `'mandor'`)
* **`tipe_penggajian`** `VARCHAR(30)` NOT NULL CHECK (tipe_penggajian IN ('borongan', 'harian', 'bulanan'))
* **`gaji_pokok_bulanan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`uang_kehadiran_harian`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`tunjangan_bulanan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`persentase_komisi_sales`** `NUMERIC(5, 2)` NOT NULL DEFAULT 0.00 (Default: `2.50`% untuk Sales-Driver)
* **`nomor_telepon`** `VARCHAR(25)` NULL
* **`alamat`** `TEXT` NULL
* **`tanggal_bergabung`** `DATE` DEFAULT `CURRENT_DATE`
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 8. `pemasok` (Vendor Bahan Curah & Kemasan)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`kode_pemasok`** `VARCHAR(50)` NOT NULL UNIQUE (Contoh: `'SUP-001'`)
* **`nama_pemasok`** `VARCHAR(150)` NOT NULL
* **`wilayah_id`** `UUID` NULL REFERENCES `wilayah(id)`
* **`alamat_lengkap`** `TEXT` NULL
* **`nomor_telepon`** `VARCHAR(25)` NULL
* **`detail_bank`** `JSONB` NOT NULL DEFAULT `'[]'::jsonb`
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 9. `grup_pelanggan` (Master Kategori Toko & Default Level Harga)
Pemegang aturan level harga default (Level 1 s/d unlimited) dan diskon grup otomatis.
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`kode_grup`** `VARCHAR(50)` NOT NULL UNIQUE (Contoh: `'GRP-A'`, `'GRP-GROSIR-TNG'`, `'GRP-KONSINYASI'`)
* **`nama_grup`** `VARCHAR(100)` NOT NULL
* **`default_level_harga`** `INT` NOT NULL DEFAULT 1 CHECK (default_level_harga >= 1)
* **`diskon_persen_default`** `NUMERIC(5, 2)` NOT NULL DEFAULT 0.00
* **`diskon_nominal_default`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 10. `pelanggan` (Master Toko Langganan & Toko Konsinyasi)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`kode_pelanggan`** `VARCHAR(50)` NOT NULL UNIQUE (Contoh: `'CUST-001'`)
* **`nama_toko`** `VARCHAR(150)` NOT NULL
* **`nama_pemilik`** `VARCHAR(100)` NULL
* **`grup_pelanggan_id`** `UUID` NOT NULL REFERENCES `grup_pelanggan(id)`
* **`is_konsinyasi`** `BOOLEAN` NOT NULL DEFAULT `FALSE` (True jika toko titip jual)
* **`wilayah_id`** `UUID` NULL REFERENCES `wilayah(id)`
* **`alamat_lengkap`** `TEXT` NOT NULL
* **`nomor_telepon`** `VARCHAR(25)` NULL
* **`nomor_whatsapp`** `VARCHAR(25)` NULL
* **`tipe_pembayaran_default`** `VARCHAR(30)` NOT NULL DEFAULT `'cash'` CHECK (tipe_pembayaran_default IN ('cash', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'konsinyasi'))
* **`plafon_piutang`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`total_piutang_berjalan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`override_level_harga`** `INT` NULL CHECK (override_level_harga >= 1)
* **`override_diskon_persen`** `NUMERIC(5, 2)` DEFAULT 0.00
* **`override_diskon_nominal`** `NUMERIC(15, 2)` DEFAULT 0.00
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

---

### MODUL 3: GRUP PRODUK, ITEM (VARIAN RASA) & PRICING MATRIX DINAMIS (5 Tabel)

#### 11. `grup_produk` (Pemegang Barcode Kemasan Universal & Level Harga)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`kode_grup`** `VARCHAR(50)` NOT NULL UNIQUE (Contoh: `'GRP-001'`, `'GRP-SINGKONG-250'`, `'GRP-BRND-135'`)
* **`nama_grup`** `VARCHAR(150)` NOT NULL
* **`barcode_universal`** `VARCHAR(100)` NULL (Barcode fisik yang tercetak pada plastik kemasan luar)
* **`merek`** `VARCHAR(100)` NOT NULL DEFAULT `'KEREN SNACK'`
* **`satuan_dasar`** `VARCHAR(30)` NOT NULL DEFAULT `'pcs'`
* **`satuan_distribusi`** `VARCHAR(30)` NOT NULL DEFAULT `'bal'`
* **`konversi_bal_ke_pcs`** `INT` NOT NULL DEFAULT 20
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 12. `grup_produk_harga_level` (Matriks Harga Jual Dinamis Level 1 s/d Unlimited)
Menyimpan harga jual per bungkus (pcs) dan per bal untuk masing-masing tingkatan level harga.
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`grup_produk_id`** `UUID` NOT NULL REFERENCES `grup_produk(id)` ON DELETE CASCADE
* **`level_harga`** `INT` NOT NULL CHECK (level_harga >= 1)
* **`nama_level`** `VARCHAR(50)` NOT NULL (Contoh: `'Level 1 - Ritel'`, `'Level 5 - Konsinyasi'`, `'Level 8 - Grosir Mitra'`)
* **`harga_jual_pcs`** `NUMERIC(15, 2)` NOT NULL
* **`harga_jual_bal`** `NUMERIC(15, 2)` NOT NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* *Constraint*: `UNIQUE(grup_produk_id, level_harga)`

#### 13. `kelompok_upah_borongan` (Kamus Tarif Ongkos Bungkus)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE
* **`nama_kelompok`** `VARCHAR(100)` NOT NULL UNIQUE (Contoh: `'Kelompok 600'`, `'Kelompok 500'`, `'Kelompok 300'`)
* **`upah_per_bungkus`** `NUMERIC(15, 2)` NOT NULL
* **`keterangan`** `TEXT` NULL
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 14. `item` (Master SKU Tunggal / Varian Rasa / Bahan Baku)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy_produk`** `INT` NULL UNIQUE
* **`grup_id`** `UUID` NULL REFERENCES `grup_produk(id)`
* **`kode_sku`** `VARCHAR(50)` NOT NULL UNIQUE (Contoh: `'SUB-0001'`, `'KS-SK-ASIN-250'`)
* **`barcode`** `VARCHAR(100)` NULL
* **`nama_item`** `VARCHAR(200)` NOT NULL
* **`varian_rasa`** `VARCHAR(100)` NULL (Contoh: `'Asin Gurih'`, `'Manis Pedas'`, `'Opak'`)
* **`tipe_item`** `VARCHAR(30)` NOT NULL CHECK (tipe_item IN ('barang_jadi', 'bahan_mentah', 'bahan_kemas'))
* **`satuan_dasar`** `VARCHAR(30)` NOT NULL
* **`satuan_distribusi`** `VARCHAR(30)` NOT NULL DEFAULT `'bal'`
* **`konversi_distribusi_ke_dasar`** `INT` NOT NULL DEFAULT 1
* **`kelompok_borongan_id`** `UUID` NULL REFERENCES `kelompok_upah_borongan(id)`
* **`pemasok_utama_id`** `UUID` NULL REFERENCES `pemasok(id)`
* **`harga_pokok_pembelian`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`stok_minimum_peringatan`** `INT` NOT NULL DEFAULT 10
* **`stok_fisik_saat_ini`** `INT` NOT NULL DEFAULT 0
* **`status_jual`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 15. `komposisi_item` (Bill of Materials / Resep Repacking Bal Curah)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`item_jadi_id`** `UUID` NOT NULL REFERENCES `item(id)` ON DELETE CASCADE
* **`item_bahan_id`** `UUID` NOT NULL REFERENCES `item(id)` ON DELETE RESTRICT
* **`jumlah_kebutuhan`** `NUMERIC(12, 4)` NOT NULL (Contoh: 0.1350 kg curah singkong + 1 lbr plastik)
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* *Constraint*: `UNIQUE(item_jadi_id, item_bahan_id)`

---

### MODUL 4: GUDANG, PEMBELIAN & MUTASI STOK IMMUTABLE (4 Tabel)

#### 16. `pembelian` (PO / Faktur Pembelian Bahan Vendor)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`nomor_faktur_pembelian`** `VARCHAR(100)` NOT NULL UNIQUE
* **`pemasok_id`** `UUID` NOT NULL REFERENCES `pemasok(id)`
* **`tanggal_pembelian`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`total_biaya`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`status_pembayaran`** `VARCHAR(30)` NOT NULL DEFAULT `'belum_lunas'` CHECK (status_pembayaran IN ('belum_lunas', 'lunas', 'batal'))
* **`status_penerimaan`** `VARCHAR(30)` NOT NULL DEFAULT `'diterima'` CHECK (status_penerimaan IN ('menunggu', 'diterima'))
* **`url_foto_nota`** `TEXT` NULL
* **`catatan`** `TEXT` NULL
* **`dibuat_oleh`** `UUID` NULL REFERENCES `pengguna(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 17. `rincian_pembelian`
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`pembelian_id`** `UUID` NOT NULL REFERENCES `pembelian(id)` ON DELETE CASCADE
* **`item_id`** `UUID` NOT NULL REFERENCES `item(id)`
* **`kuantitas`** `INT` NOT NULL CHECK (kuantitas > 0)
* **`satuan`** `VARCHAR(30)` NOT NULL
* **`harga_satuan`** `NUMERIC(15, 2)` NOT NULL
* **`subtotal`** `NUMERIC(15, 2)` NOT NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 18. `penyesuaian_stok` (Stock Opname Fisik & Barang Rusak)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`nomor_dokumen`** `VARCHAR(100)` NOT NULL UNIQUE
* **`item_id`** `UUID` NOT NULL REFERENCES `item(id)`
* **`tanggal`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`tipe_penyesuaian`** `VARCHAR(50)` NOT NULL CHECK (tipe_penyesuaian IN ('opname_hilang', 'opname_lebih', 'barang_rusak', 'retur_masuk_manual'))
* **`kuantitas`** `INT` NOT NULL CHECK (kuantitas > 0)
* **`alasan_keterangan`** `TEXT` NOT NULL
* **`dicatat_oleh`** `UUID` NOT NULL REFERENCES `pengguna(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 19. `riwayat_stok` (Buku Besar Mutasi Stok Immutable)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`item_id`** `UUID` NOT NULL REFERENCES `item(id)`
* **`tipe_mutasi`** `VARCHAR(50)` NOT NULL CHECK (tipe_mutasi IN (
    'produksi_masuk', 'bahan_terpakai_produksi', 'penjualan_keluar',
    'pembelian_masuk', 'penyesuaian_opname_tambah', 'penyesuaian_opname_kurang',
    'retur_pelanggan_masuk', 'konsinyasi_keluar', 'konsinyasi_retur_masuk'
  ))
* **`jumlah_perubahan`** `INT` NOT NULL
* **`stok_sebelum`** `INT` NOT NULL
* **`stok_sesudah`** `INT` NOT NULL
* **`referensi_tabel`** `VARCHAR(50)` NOT NULL
* **`referensi_id`** `UUID` NOT NULL
* **`keterangan`** `TEXT` NULL
* **`dibuat_oleh`** `UUID` NULL REFERENCES `pengguna(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

---

### MODUL 5: PENJUALAN & LOGISTIK SURAT JALAN (3 Tabel)

#### 20. `pesanan` (Sales Order & Invoice Penjualan)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`nomor_nota`** `VARCHAR(100)` NOT NULL UNIQUE
* **`pelanggan_id`** `UUID` NOT NULL REFERENCES `pelanggan(id)`
* **`sales_driver_id`** `UUID` NULL REFERENCES `karyawan(id)`
* **`tanggal_pesanan`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`total_bruto`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`total_diskon`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`total_netto`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`tipe_pembayaran`** `VARCHAR(30)` NOT NULL CHECK (tipe_pembayaran IN ('cash', 'tempo_7_hari', 'tempo_14_hari', 'tempo_30_hari', 'konsinyasi'))
* **`tanggal_jatuh_tempo`** `DATE` NULL
* **`status_pembayaran`** `VARCHAR(30)` NOT NULL DEFAULT `'belum_lunas'` CHECK (status_pembayaran IN ('belum_lunas', 'tempo', 'lunas', 'dibatalkan'))
* **`status_pemrosesan`** `VARCHAR(30)` NOT NULL DEFAULT `'menunggu_approval'` CHECK (status_pemrosesan IN ('menunggu_approval', 'disetujui', 'siap_kirim', 'dalam_pengiriman', 'selesai', 'dibatalkan'))
* **`catatan`** `TEXT` NULL
* **`dibuat_oleh`** `UUID` NULL REFERENCES `pengguna(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 21. `item_pesanan` (Rincian Item Nota)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`pesanan_id`** `UUID` NOT NULL REFERENCES `pesanan(id)` ON DELETE CASCADE
* **`item_id`** `UUID` NOT NULL REFERENCES `item(id)`
* **`kuantitas_satuan_dasar`** `INT` NOT NULL CHECK (kuantitas_satuan_dasar > 0)
* **`kuantitas_satuan_distribusi`** `INT` NOT NULL DEFAULT 0
* **`harga_satuan_deal`** `NUMERIC(15, 2)` NOT NULL
* **`diskon_item_persen`** `NUMERIC(5, 2)` DEFAULT 0.00
* **`diskon_item_nominal`** `NUMERIC(15, 2)` DEFAULT 0.00
* **`is_bonus`** `BOOLEAN` NOT NULL DEFAULT `FALSE`
* **`subtotal`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 22. `surat_jalan` (Dokumen Pengiriman & Live POD Foto)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`nomor_surat_jalan`** `VARCHAR(100)` NOT NULL UNIQUE
* **`pesanan_id`** `UUID` NOT NULL REFERENCES `pesanan(id)`
* **`sales_driver_id`** `UUID` NULL REFERENCES `karyawan(id)`
* **`rute_wilayah_id`** `UUID` NULL REFERENCES `wilayah(id)`
* **`url_pdf_dokumen`** `TEXT` NULL
* **`status_surat_jalan`** `VARCHAR(30)` NOT NULL DEFAULT `'draf_n8n'` CHECK (status_surat_jalan IN ('draf_n8n', 'disetujui_owner', 'sedang_dikirim', 'selesai_diterima', 'gagal_kembali'))
* **`bukti_terima_foto`** `TEXT` NULL (Foto bukti serah terima toko yang diupload Sales-Driver via Telegram)
* **`nama_penerima_toko`** `VARCHAR(100)` NULL
* **`waktu_berangkat`** `TIMESTAMPTZ` NULL
* **`waktu_sampai`** `TIMESTAMPTZ` NULL
* **`disetujui_oleh`** `UUID` NULL REFERENCES `pengguna(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

---

### MODUL 6: KONSINYASI KHUSUS (CONSIGNMENT SHELF LEDGER) (3 Tabel)

#### 23. `stok_konsinyasi_toko` (Saldo Fisik di Rak Toko Titip Jual)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`pelanggan_id`** `UUID` NOT NULL REFERENCES `pelanggan(id)` ON DELETE CASCADE
* **`item_id`** `UUID` NOT NULL REFERENCES `item(id)` ON DELETE RESTRICT
* **`stok_titip_saat_ini`** `INT` NOT NULL DEFAULT 0 CHECK (stok_titip_saat_ini >= 0)
* **`terakhir_opname_pada`** `TIMESTAMPTZ` NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* *Constraint*: `UNIQUE(pelanggan_id, item_id)`

#### 24. `kunjungan_konsinyasi` (Header Opname Mingguan Sales-Driver)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`nomor_kunjungan`** `VARCHAR(100)` NOT NULL UNIQUE
* **`pelanggan_id`** `UUID` NOT NULL REFERENCES `pelanggan(id)`
* **`sales_driver_id`** `UUID` NOT NULL REFERENCES `karyawan(id)`
* **`tanggal_kunjungan`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`pesanan_id`** `UUID` NULL REFERENCES `pesanan(id)` (Invoice laku yang terbit otomatis)
* **`total_laku_nominal`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`catatan`** `TEXT` NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 25. `rincian_kunjungan_konsinyasi` (Detail Hitung Laku & Retur Rusak)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`kunjungan_id`** `UUID` NOT NULL REFERENCES `kunjungan_konsinyasi(id)` ON DELETE CASCADE
* **`item_id`** `UUID` NOT NULL REFERENCES `item(id)`
* **`stok_titip_awal`** `INT` NOT NULL
* **`tambah_titip_baru`** `INT` NOT NULL DEFAULT 0
* **`sisa_fisik_di_rak`** `INT` NOT NULL
* **`retur_rusak`** `INT` NOT NULL DEFAULT 0
* **`jumlah_laku_terjual`** `INT` NOT NULL
* **`harga_satuan_deal`** `NUMERIC(15, 2)` NOT NULL
* **`subtotal_laku`** `NUMERIC(15, 2)` NOT NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

---

### MODUL 7: HR, ABSENSI, TABUNGAN & PAYROLL BORONGAN (10 Tabel - 1000% Sinkron SalaryApp)

#### 26. `penggajian` (Header Tutup Buku Payroll)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE (Sinkron `penggajian.id` SalaryApp)
* **`nomor_referensi`** `VARCHAR(100)` NOT NULL UNIQUE
* **`periode_awal`** `DATE` NOT NULL
* **`periode_akhir`** `DATE` NOT NULL
* **`tipe_penggajian`** `VARCHAR(30)` NOT NULL CHECK (tipe_penggajian IN ('mingguan', 'bulanan', 'gabungan'))
* **`total_gaji_dikeluarkan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`status`** `VARCHAR(30)` NOT NULL DEFAULT `'draf'` CHECK (status IN ('draf', 'disetujui', 'dibayarkan'))
* **`disetujui_oleh`** `UUID` NULL REFERENCES `pengguna(id)`
* **`disetujui_pada`** `TIMESTAMPTZ` NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 27. `produksi_harian` (Hasil Bungkus Pekerja Borongan)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE
* **`karyawan_id`** `UUID` NOT NULL REFERENCES `karyawan(id)`
* **`tanggal`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`item_id`** `UUID` NOT NULL REFERENCES `item(id)`
* **`kuantitas_pcs`** `INT` NOT NULL CHECK (kuantitas_pcs >= 0)
* **`kuantitas_bal`** `INT` NOT NULL DEFAULT 0
* **`lembur_pcs`** `INT` NOT NULL DEFAULT 0
* **`upah_per_pcs_snapshot`** `NUMERIC(15, 2)` NOT NULL
* **`total_upah_didapat`** `NUMERIC(15, 2)` NOT NULL
* **`penggajian_id`** `UUID` NULL REFERENCES `penggajian(id)`
* **`dicatat_oleh`** `UUID` NULL REFERENCES `pengguna(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* *Constraint*: `UNIQUE(karyawan_id, tanggal, item_id)`

#### 28. `target_produksi` (Target KPI)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`tanggal`** `DATE` NOT NULL
* **`item_id`** `UUID` NOT NULL REFERENCES `item(id)`
* **`target_pcs`** `INT` NOT NULL CHECK (target_pcs > 0)
* **`keterangan`** `TEXT` NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 29. `absensi` (Presensi Harian Pegawai)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE
* **`karyawan_id`** `UUID` NOT NULL REFERENCES `karyawan(id)`
* **`tanggal`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`status_kehadiran`** `VARCHAR(30)` NOT NULL DEFAULT `'hadir'` CHECK (status_kehadiran IN ('hadir', 'izin', 'sakit', 'libur', 'alpa'))
* **`telat`** `BOOLEAN` NOT NULL DEFAULT `FALSE`
* **`lembur_nominal`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`catatan`** `TEXT` NULL
* **`penggajian_id`** `UUID` NULL REFERENCES `penggajian(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* *Constraint*: `UNIQUE(karyawan_id, tanggal)`

#### 30. `kasbon` (Master Pinjaman Karyawan)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE
* **`karyawan_id`** `UUID` NOT NULL REFERENCES `karyawan(id)`
* **`tanggal_pengajuan`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`total_pinjaman`** `NUMERIC(15, 2)` NOT NULL CHECK (total_pinjaman > 0)
* **`potongan_per_periode`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`sisa_pinjaman`** `NUMERIC(15, 2)` NOT NULL
* **`status_kasbon`** `VARCHAR(30)` NOT NULL DEFAULT `'aktif'` CHECK (status_kasbon IN ('aktif', 'lunas', 'dibatalkan'))
* **`keterangan`** `TEXT` NULL
* **`catatan`** `TEXT` NULL
* **`disetujui_oleh`** `UUID` NULL REFERENCES `pengguna(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 31. `potongan_kasbon` (Transaksi Cicilan Kasbon)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE
* **`kasbon_id`** `UUID` NOT NULL REFERENCES `kasbon(id)` ON DELETE CASCADE
* **`rincian_penggajian_id`** `UUID` NULL REFERENCES `rincian_penggajian(id)` ON DELETE SET NULL
* **`tanggal`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`nominal`** `NUMERIC(15, 2)` NOT NULL CHECK (nominal > 0)
* **`tipe_potongan`** `VARCHAR(30)` NOT NULL DEFAULT `'payroll'` CHECK (tipe_potongan IN ('payroll', 'manual'))
* **`keterangan`** `TEXT` NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 32. `tabungan` (Master Saldo Tabungan Pegawai)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE
* **`karyawan_id`** `UUID` NOT NULL UNIQUE REFERENCES `karyawan(id)` ON DELETE CASCADE
* **`saldo`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 33. `transaksi_tabungan` (Buku Mutasi Tabungan)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE
* **`tabungan_id`** `UUID` NOT NULL REFERENCES `tabungan(id)` ON DELETE CASCADE
* **`karyawan_id`** `UUID` NOT NULL REFERENCES `karyawan(id)`
* **`rincian_penggajian_id`** `UUID` NULL REFERENCES `rincian_penggajian(id)`
* **`tanggal`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`tipe`** `VARCHAR(30)` NOT NULL CHECK (tipe IN ('deposit', 'withdrawal'))
* **`jumlah`** `NUMERIC(15, 2)` NOT NULL CHECK (jumlah > 0)
* **`sumber`** `VARCHAR(30)` NOT NULL DEFAULT `'payroll'` CHECK (sumber IN ('payroll', 'manual'))
* **`keterangan`** `TEXT` NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 34. `penarikan_gaji` (Kasbon Panjar Harian Karyawan Bulanan)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE
* **`karyawan_id`** `UUID` NOT NULL REFERENCES `karyawan(id)`
* **`tanggal`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`nominal`** `NUMERIC(15, 2)` NOT NULL CHECK (nominal > 0)
* **`keterangan`** `TEXT` NULL
* **`penggajian_id`** `UUID` NULL REFERENCES `penggajian(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 35. `rincian_penggajian` (Slip Gaji Final - 1000% Sinkron SalaryApp)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`id_legacy`** `INT` NULL UNIQUE
* **`penggajian_id`** `UUID` NOT NULL REFERENCES `penggajian(id)` ON DELETE CASCADE
* **`karyawan_id`** `UUID` NOT NULL REFERENCES `karyawan(id)`
* **`gaji_pokok`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`hari_hadir`** `INT` NOT NULL DEFAULT 0
* **`total_uang_kehadiran`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`total_tunjangan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`tunjangan_bulanan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`tunjangan_lain`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`catatan_tunjangan_lain`** `VARCHAR(255)` NULL
* **`total_upah_borongan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00 (Upah hasil produksi bungkus)
* **`total_upah_lembur`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`total_komisi_sales`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`total_potongan_kasbon`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`potongan_lain`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`catatan_potongan_lain`** `VARCHAR(255)` NULL
* **`nominal_pembulatan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`total_potongan_tabungan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`penarikan_tabungan`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`total_penarikan_gaji`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`is_excluded`** `BOOLEAN` NOT NULL DEFAULT `FALSE`
* **`catatan_pengecualian`** `TEXT` NULL
* **`gaji_bersih_diterima`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`rincian_json`** `JSONB` NOT NULL DEFAULT `'{}'::jsonb`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

---

### MODUL 8: ARUS KAS, DRAF AI & MASTER AUDIT TRAIL (5 Tabel)

#### 36. `akun_kas` (Multi-Rekening Kas & Bank)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`nama_akun`** `VARCHAR(100)` NOT NULL (Contoh: `'Kasir Utama (Tunai)'`, `'BCA Bisnis KEREN'`)
* **`nomor_rekening`** `VARCHAR(50)` NULL
* **`atas_nama`** `VARCHAR(100)` NULL
* **`saldo_saat_ini`** `NUMERIC(15, 2)` NOT NULL DEFAULT 0.00
* **`status_aktif`** `BOOLEAN` NOT NULL DEFAULT `TRUE`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 37. `draf_pengeluaran` (n8n + AI Telegram Assistant & Approval)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`diajukan_oleh_pengguna_id`** `UUID` NOT NULL REFERENCES `pengguna(id)`
* **`nominal`** `NUMERIC(15, 2)` NOT NULL CHECK (nominal > 0)
* **`kategori_beban`** `VARCHAR(50)` NOT NULL CHECK (kategori_beban IN ('bensin', 'konsumsi', 'parkir_tol', 'maintenance', 'pembelian_bahan', 'lainnya'))
* **`keterangan_mentah`** `TEXT` NOT NULL
* **`keterangan_ai`** `TEXT` NULL
* **`url_foto_nota`** `TEXT` NULL
* **`status_approval`** `VARCHAR(30)` NOT NULL DEFAULT `'menunggu'` CHECK (status_approval IN ('menunggu', 'disetujui', 'ditolak'))
* **`akun_kas_id`** `UUID` NULL REFERENCES `akun_kas(id)`
* **`id_pesan_telegram_owner`** `VARCHAR(100)` NULL
* **`alasan_penolakan`** `TEXT` NULL
* **`disetujui_pada`** `TIMESTAMPTZ` NULL
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 38. `arus_kas` (Buku Kas Besar Toko - Ledger Keuangan Immutable)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`akun_kas_id`** `UUID` NOT NULL REFERENCES `akun_kas(id)`
* **`tanggal_transaksi`** `DATE` NOT NULL DEFAULT `CURRENT_DATE`
* **`jenis_kas`** `VARCHAR(20)` NOT NULL CHECK (jenis_kas IN ('masuk', 'keluar'))
* **`kategori`** `VARCHAR(50)` NOT NULL CHECK (kategori IN ('penjualan', 'beban_operasional', 'pembayaran_payroll', 'kasbon', 'pembelian_bahan', 'transfer_antar_kas'))
* **`nominal`** `NUMERIC(15, 2)` NOT NULL CHECK (nominal > 0)
* **`keterangan`** `TEXT` NOT NULL
* **`referensi_tabel`** `VARCHAR(50)` NULL
* **`referensi_id`** `UUID` NULL
* **`saldo_berjalan`** `NUMERIC(15, 2)` NOT NULL
* **`dicatat_oleh`** `UUID` NULL REFERENCES `pengguna(id)`
* **`dibuat_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 39. `pengaturan_sistem` (Konfigurasi Global Sistem)
* **`kunci`** `VARCHAR(100)` PRIMARY KEY
* **`nilai`** `TEXT` NOT NULL
* **`deskripsi`** `TEXT` NULL
* **`diubah_pada`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

#### 40. `log_aktivitas` (Master Audit Trail Universal)
* **`id`** `UUID` PRIMARY KEY DEFAULT `gen_random_uuid()`
* **`pengguna_id`** `UUID` NULL REFERENCES `pengguna(id)` ON DELETE SET NULL
* **`nama_aktor`** `VARCHAR(150)` NOT NULL
* **`peran_aktor`** `VARCHAR(50)` NOT NULL
* **`sumber_aksi`** `VARCHAR(50)` NOT NULL CHECK (sumber_aksi IN ('telegram_bot', 'whatsapp_bot', 'web_app', 'n8n_automation', 'database_trigger', 'system_cron'))
* **`kategori_aktivitas`** `VARCHAR(50)` NOT NULL CHECK (kategori_aktivitas IN ('keuangan', 'penjualan', 'logistik', 'gudang_stok', 'produksi_bom', 'hr_payroll', 'master_data', 'keamanan_auth', 'ai_interaction'))
* **`jenis_aksi`** `VARCHAR(50)` NOT NULL
* **`tabel_terdampak`** `VARCHAR(100)` NULL
* **`id_referensi`** `UUID` NULL
* **`deskripsi_aktivitas`** `TEXT` NOT NULL
* **`data_sebelum`** `JSONB` NULL (Snapshot nilai data lama sebelum diubah)
* **`data_sesudah`** `JSONB` NULL (Snapshot nilai data baru setelah diubah)
* **`ip_address`** `VARCHAR(50)` NULL
* **`user_agent`** `TEXT` NULL
* **`id_pesan_telegram`** `BIGINT` NULL
* **`waktu_kejadian`** `TIMESTAMPTZ` NOT NULL DEFAULT `NOW()`

---

## 6. Mesin Otomasi Database (Triggers & Stored Procedures RPC)

1. **`fn_hitung_harga_jual_item(item_id, pelanggan_id)`**:
   - Membaca grup produk ➔ mencari harga base di `grup_produk_harga_level` berdasarkan level harga toko ➔ memotong diskon grup (%) dan diskon toko (nominal/%) ➔ mengembalikan harga deal per pcs dan per bal secara instan.
2. **`fn_cari_item_by_barcode(barcode)`**:
   - Mendeteksi apakah barcode merupakan barcode universal grup kemasan luar atau barcode spesifik ➔ mengembalikan daftar seluruh varian rasa di grup tersebut.
3. **`fn_proses_kunjungan_konsinyasi(pelanggan_id, sales_driver_id, rincian)`**:
   - Menghitung `jumlah_laku = (stok_awal + titipan_baru) - (sisa_rak + retur_rusak)` ➔ auto terbit invoice laku ➔ update saldo rak konsinyasi ➔ catat retur barang rusak ke gudang pusat.
4. **`fn_trg_produksi_harian_after_insert`**:
   - Tambah stok barang jadi ➔ potong bahan mentah & kemas sesuai `komposisi_item` ➔ catat riwayat mutasi stok.
5. **`fn_trg_surat_jalan_status_update`**:
   - Saat status SJ berubah jadi `'sedang_dikirim'`, otomatis potong stok fisik barang jadi dan catat `riwayat_stok`.
6. **`fn_trg_draf_pengeluaran_approval`**:
   - Saat Owner klik **[SETUJU]** di Telegram, otomatis potong saldo `akun_kas` dan catat uang keluar di `arus_kas`.
7. **`fn_catat_log_aktivitas(...)`**:
   - Helper pencatatan audit trail forensik universal yang dipanggil oleh seluruh triggers, n8n webhook, dan Web App.

---

## 7. Alur Kerja Lapangan Bot Telegram (Sales-Driver, Mandor, Owner)

1. **Manifest & Loading List Rute Besok**: Dikirim otomatis jam 18:00 sore (Daftar toko berurutan, total bal muatan mobil, download PDF Surat Jalan).
2. **Direct Canvas Order di Toko**: Sales-Driver kirim voice/text order toko ➔ AI cek limit plafon kredit toko ➔ Invoice & SJ baru langsung terbit.
3. **Live Proof of Delivery (POD)**: Sales-Driver klik tombol **[📦 BARANG DITERIMA]** dan kirim foto nota bertanda tangan toko.
4. **Penagihan Piutang & Setor Tunai**: Terima uang cash pelunasan toko ➔ Nota lunas, piutang berkurang, uang tercatat di kas mobil Sales-Driver.
5. **Cek Stok & Sisa Plafon Live**: Ketik `/stok` atau `/cekpiutang` langsung dibalas real-time oleh bot.
6. **Rekap Tutup Rute Sore Hari**: Jam 17:00 bot menyajikan rekap total toko sukses, sisa barang di mobil, dan total uang tunai yang harus disetor ke kasir.

---

## 8. Arsitektur Tampilan Web App (3 Persona View)

1. **Developer View**:
   - Schema inspector, API PostgREST monitor, n8n webhook log, Supabase connection status.
2. **Admin Toko View (POS Kasir & Inventory)**:
   - Kasir POS cepat (barcode scanner ready + Alpine.js), input penerimaan PO vendor, stock opname fisik gudang, master produk.
3. **Owner Command Center (Executive Dashboard)**:
   - **Live AI Activity Stream**: Menampilkan real-time log aktivitas staff dan AI dari tabel `log_aktivitas`.
   - **One-Click Approval Hub**: Daftar pengeluaran bensin, izin kasbon, atau order limit yang butuh persetujuan.
   - **AI Conversational Assistant**: Chatbox pintar untuk memberi perintah rute (*"Besok rute full Jakarta Barat"*) atau tanya analisis omzet bisnis.

---

## 9. Standar Desain Antarmuka Material Design 3 (M3 & Material You)

Seluruh antarmuka Web App (POS Kasir, Master Stok, Pricing Matrix, Consignment, HR Payroll, dan Owner Command Center) wajib mengikuti sistem desain **Google Material Design 3 (M3)** dengan prinsip:
$$\text{User Goal} \rightarrow \text{Information Architecture} \rightarrow \text{Hierarchy} \rightarrow \text{Adaptive Layout} \rightarrow \text{Semantic Tokens} \rightarrow \text{Components} \rightarrow \text{States} \rightarrow \text{Accessibility}$$

### 9.1 Token Warna Semantik (Semantic Color Roles)
Dilarang menggunakan *hardcoded hex colors* secara acak di komponen UI. Seluruh elemen harus merujuk pada peran semantik M3:
* **`surface` / `onSurface`**: Latar belakang aplikasi utama dan teks primer dengan kontras tinggi.
* **`surfaceContainer` (Lowest, Low, Default, High, Highest)**: Hierarki penampung informasi (kartu stok, panel kasir, tabel order) untuk membedakan kedalaman tanpa bayangan berat.
* **`primary` (`#f97316` / `#ea580c`) & `onPrimary` (`#ffffff`)**: Aksi utama dengan penekanan tinggi (Tombol "Bayar Sekarang", "Terbitkan Nota", "Approve Kasbon").
* **`primaryContainer` & `onPrimaryContainer`**: Penekanan aksi medium atau status aktif terpilih (misal: baris produk yang sedang diedit).
* **`secondary` / `secondaryContainer`**: Tombol alternatif (Cetak Ulang, Filter Kategori).
* **`tertiary` / `tertiaryContainer`** (Aksen Teal/Cyan): Status pengiriman logistik & indikator bot AI.
* **`error` / `errorContainer` / `onErrorContainer`**: Peringatan stok kritis, limit piutang terlampaui, dan validasi form gagal.
* **`outline` / `outlineVariant`**: Garis pemisah tabel dan border input field yang halus (`border-outline-variant`).

### 9.2 Kelas Ukuran Jendela & Tata Letak Adaptif (Adaptive Window Classes)
Desain Web App harus beradaptasi secara dinamis terhadap perangkat kasir/admin:
1. **Compact ($<600\text{dp}$ / Layar HP / Tablet Kasir Mini)**:
   * Menggunakan *Bottom Navigation Bar* dan *Modal Bottom Sheet* untuk detail item.
2. **Medium ($600\text{dp} - 839\text{dp}$ / Tablet Mandor Gudang)**:
   * Menggunakan *Navigation Rail* vertikal dan *Supporting Pane* untuk opname stok.
3. **Expanded ($\ge 840\text{dp}$ / Layar PC Desktop Kasir POS & Command Center)**:
   * Menggunakan *Permanent Navigation Drawer*.
   * Menerapkan **Canonical List-Detail Layout**: Kolom kiri daftar transaksi/stok, kolom kanan preview detail nota & aksi cepat tanpa perlu berpindah halaman.

### 9.3 Aturan Pemilihan Komponen Semantik (Component Selection)
* **Tombol (Buttons)**:
  * *Filled Button*: Hanya untuk 1 Aksi Utama per layar (*High Emphasis*).
  * *Tonal Button*: Aksi sekunder penting (misal: "Tambah Item Baru").
  * *Outlined Button*: Aksi alternatif (misal: "Batal", "Download PDF").
  * *Extended FAB (Floating Action Button)*: Tombol aksi mengambang cepat (misal: `[+ Transaksi Baru F2]`).
* **Input Form (Text Fields)**:
  * Menggunakan *Outlined Text Field* dengan *Floating Label* dan *Helper Text* yang jelas.
  * Setiap *Error State* wajib memberikan instruksi pemulihan yang jelas (misal: *"Jumlah pcs melebihi stok fisik (Tersedia: 45 pcs)"*).
* **Chips**:
  * *Filter Chips*: Memilih kategori grup snack (Berondong, Kerupuk, Singkong).
  * *Input Chips*: Menampilkan diskon atau varian rasa terpilih.

### 9.4 Aksesibilitas & Ergonomi Kasir POS (A11y & Ergonomics)
* **Target Sentuh Minimum**: Semua tombol layar sentuh kasir berukuran minimal **$48 \times 48\text{dp}$** untuk mencegah salah pencet saat antrean toko ramai.
* **Standar Kontras WCAG 2.1**: Rasio kontras minimal **4.5:1** untuk seluruh teks transaksi dan angka nominal rupiah.
* **Komunikasi Non-Warna**: Status pesanan atau stok tidak boleh hanya mengandalkan warna hijau/merah, wajib disertai ikon dan label teks (Contoh: `[✓ LUNAS]`, `[⚠️ TEMPO 3 HARI]`, `[❌ KOSONG]`).
* **Keyboard Hotkeys & Clear Focus Ring**: Kasir POS mendukung penuh tombol shortcut keyboard fisik (`F1` Cari Item, `F2` Input Diskon, `F4` Bayar Cash, `F8` Cetak Nota, `ESC` Batal) dengan *Focus Ring* yang kontras.

### 9.5 M3 Expressive & Motion
* Transisi antar layar menggunakan kurva *easing* standar M3 (*Emphasized Accelerate/Decelerate* 200-300ms) untuk memberi umpan balik instan saat scan barcode berhasil atau saat popup konfirmasi pembayaran muncul.

---

## 10. Urutan Eksekusi SQL di Supabase SQL Editor

Untuk menginisialisasi database di project Supabase baru, jalankan file-file SQL berikut secara berurutan:

1. 📄 **`database/01_schema.sql`** $\rightarrow$ Membuat ekstensi UUID, 40 tabel relasional, indeks performa, dan mengaktifkan RLS 100%.
2. 📄 **`database/02_triggers_and_rpc.sql`** $\rightarrow$ Menginstal mesin hitung harga dinamis, konsinyasi processor, barcode resolver, dan seluruh triggers otomasi.
3. 📄 **`database/seeds/01_seed_rbac.sql`** $\rightarrow$ Memasukkan peran default (`owner`, `admin`, `mandor`, `sales_driver`), izin granular, akun kas, dan konfigurasi sistem.
4. 📄 **`database/seeds/02_seed_master_karyawan.sql`** $\rightarrow$ Memasukkan tarif borongan bungkus (Kelompok 300-600) dan 19 karyawan operasional.
5. 📄 **`database/seeds/03_seed_master_produk.sql`** $\rightarrow$ Memasukkan 30 Grup Produk, matriks harga level, dan 137 SKU varian rasa (sesuai Excel asli).
6. 📄 **`database/seeds/04_seed_master_pelanggan_pemasok.sql`** $\rightarrow$ Memasukkan rute wilayah, grup pelanggan, 544 toko pelanggan (termasuk toko konsinyasi), dan supplier.

---
*Dokumen ini merupakan PRD Induk Resmi v2.3 untuk pengembangan sistem KEREN Snack.*

# KEREN SNACK ERP v2.0
**Enterprise Autonomous AI Workforce & POS/ERP System**

Sistem operasional dan ERP terpadu untuk manufaktur repacking, distribusi, dan kasir toko KEREN Snack.

---

## 📂 Struktur Direktori Proyek:
- `config/`
  - `database.php` : Koneksi Singleton PDO PostgreSQL ke Supabase (SSL Enabled).
  - `env.php` : Loader variabel lingkungan `.env`.
- `database/`
  - `01_schema.sql` : Skema DDL 40 Tabel PostgreSQL 15+ (Supabase) dengan UUIDv4 & RLS 100%.
  - `02_triggers_and_rpc.sql` : Stored Procedures (RPC), Pricing Engine, Consignment Opname, & Triggers.
  - `seeds/` : 4 File Seed SQL (RBAC, 19 Karyawan Borongan/Sales-Driver, 137 SKU & Barcode Universal, 544 Pelanggan & Toko Konsinyasi).
  - `legacy_reference/` : Ekstraksi bersih 104 tabel IPOS 5.
- `docs/`
  - `PRD_MASTER_KEREN_SNACK.md` : Master PRD Resmi v2.3 (Lengkap & Terkunci).
  - `ARSITEKTUR_SISTEM.md` : Panduan Topologi, n8n, AI Gemini & Bot Telegram.
  - `REFERENSI_IPOS5_MAPPING.md` : Pemetaan 104 tabel IPOS 5 ke Supabase.
  - `data-stok-kerensnack.xlsx` : Master Excel Asli Data Stok & Katalog Keren Snack.
- `test_db.php` : Script CLI penguji kesehatan koneksi database dan RPC.
- `index.html` : Dashboard visual blueprint sistem (Material Design 3).

---

## 🚀 Pengujian Kesehatan Sistem:
Jalankan di terminal Laragon:
```bash
php test_db.php
```

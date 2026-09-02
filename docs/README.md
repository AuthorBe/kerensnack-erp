# 📚 Dokumentasi Resmi KEREN SNACK ERP

Selamat datang di pusat dokumentasi dan spesifikasi teknis arsitektur **KEREN SNACK ERP** (*Next-Gen Autonomous Manufacturing & B2B Distribution Enterprise Resource Planning*).

---

## 🗂️ Direktori Dokumen & Panduan Teknis

| Dokumen | Deskripsi | Topik Utama |
|---|---|---|
| 🏛️ **[ARCHITECTURE.md](./ARCHITECTURE.md)** | **Peta Arsitektur Sistem & Manual Teknis** | Struktur direktori MVC, Stack PHP 8.1+ & Supabase, alur request, kamus database, & standar keamanan. |
| 📋 **[PRD_MASTER_KEREN_SNACK.md](./PRD_MASTER_KEREN_SNACK.md)** | **Product Requirement Document (Master PRD)** | Spesifikasi lengkap 10 modul utama: POS, Orders B2B, Gudang, Pembelian, Keuangan Kas, Master Data, & RBAC. |
| 🏪 **[PRD-Konsinyasi-PENYEMPURNAAN.md](./PRD-Konsinyasi-PENYEMPURNAAN.md)** | **Spesifikasi Modul Konsinyasi & Titip Rak** | Alur titip jual toko mitra, opname fisik barcode/manual, kalkulasi komisi otomatis, tukar guling rusak, & early warning. |
| 🤖 **[ARSITEKTUR_SISTEM.md](./ARSITEKTUR_SISTEM.md)** | **Panduan Integrasi AI Bot & Canvaser Lapangan** | Model operasional Sales-Driver (Canvaser), integrasi Bot Telegram, Loading Sheet, Proof of Delivery (POD), & n8n webhook. |
| 🗺️ **[REFERENSI_IPOS5_MAPPING.md](./REFERENSI_IPOS5_MAPPING.md)** | **Kamus Pemetaan Migrasi Database** | Relasi tabel, transformasi skema, dan migrasi master produk/pelanggan dari format legacy iPos 5 ke Postgres Supabase. |

---

## 🔒 Kebijakan Keamanan & Privasi Data
* Seluruh berkas kredensial (`.env`), dump database lokal, catatan prompt internal, dan spreadsheet data bisnis telah diisolasi melalui `.gitignore` untuk menjamin keamanan repositori pada platform publik/GitHub.
* Arsitektur menggunakan standar perlindungan **Multi-Layer RBAC (Role-Based Access Control)** dengan validasi *server-side* pada seluruh endpoint controller.

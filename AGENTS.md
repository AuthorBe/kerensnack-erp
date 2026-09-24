# Engineering Guidelines & Agent Operational Protocols

## Overview
Dokumen ini menetapkan standar rekayasa perangkat lunak (*software engineering standards*), protokol pengujian otomatis, tata kelola migrasi, dan pedoman integritas basis data yang **wajib dipatuhi tanpa pengecualian** oleh seluruh asisten pengembang berbasis AI (*Agentic AI*) dan kontributor teknis pada repositori **KEREN ONE**.

---

## 1. Status Lingkungan & Integritas Data Produksi
- **Lingkungan Aktif (*Production Environment*)**: Repositori ini terhubung langsung ke basis data produksi aktif (Supabase PostgreSQL) yang mengelola transaksi operasional, keuangan, dan inventori riil.
- **Prinsip Utama**: Setiap interaksi basis data wajib menjamin keamanan data (*data safety*), keandalan transaksi (*transactional reliability*), dan konsistensi relasional (*relational integrity*).
- **Zero Contamination Policy**: Basis data produksi tidak boleh dikotori oleh entitas dummy (produk tiruan, grup produk uji coba, toko fiktif, pesanan percobaan, dsb.) yang tampil di antarmuka pengguna (*user-facing UI*) maupun merusak agregasi keuangan.

---

## 2. Kebijakan Mutlak: Zero Persistent Mock Data
Untuk menjaga kebersihan, keandalan analitik, dan akurasi laporan rekonsiliasi keuangan:
1. **Larangan Persistensi Data Tiruan**: Dilarang keras menyisipkan dan membiarkan data uji coba (*mock / dummy data*) tersimpan permanen di dalam tabel basis data.
2. **Larangan Eksekusi Seeder Non-Isolasi**: Berkas seeder pada direktori `database/seeds/` hanya diperuntukkan bagi lingkungan *isolated local sandbox* dan dilarang dijalankan langsung pada basis data produksi aktif.
3. **Pembersihan Bersih Saat Error/Interupsi**: Setiap proses pengujian atau simulasi tidak boleh meninggalkan *orphan records* meskipun terjadi kegagalan asersi, eksepsi fatal, atau penghentian proses di tengah jalan.
4. **Larangan Pembuatan Fixture Global Tanpa Rollback**: Dilarang membuat fungsi *helper* pengujian yang melakukan `INSERT` ke database publik tanpa pembungkusan transaksi rollback.

---

## 3. Standar Pengujian & Verifikasi Fitur (*Testing Protocol*)
Dalam melaksanakan audit kode, *unit testing*, maupun *integration testing*:

### A. Pengujian Berbasis Data Riil (*Read-Only - Prioritas Utama*)
Prioritaskan pengujian dengan metode *read-only* memanfaatkan rekaman master data yang telah tersedia di basis data (`SELECT` query) tanpa memanipulasi status data yang ada:
```php
$item = Database::fetchOne("SELECT id, grup_id FROM public.item WHERE status_aktif = TRUE LIMIT 1");
if (!$item) {
    throw new RuntimeException("Master item aktif tidak ditemukan di database riil.");
}
```

### B. Transaksi Terisolasi dengan Rollback Otomatis (*Mandatory Pattern*)
Apabila pengujian mewajibkan simulasi penulisan atau manipulasi data (`INSERT`, `UPDATE`, `DELETE`), seluruh alur wajib diisolasi di dalam blok transaksi basis data dan di-rollback sepenuhnya pada blok `finally`:
```php
$pdo->beginTransaction();
try {
    // Eksekusi simulasi dan asersi pengujian
} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack(); // Menjamin basis data 100% utuh dan bersih
    }
}
```

### C. Protokol Pembersihan Tertutup (*Guaranteed Teardown with try-finally*)
Jika pengujian memanggil metode Controller yang secara internal mengeksekusi `$pdo->beginTransaction()` dan `$pdo->commit()` sehingga transaksi level tes ter-commit:
1. **Gunakan Blok `try ... finally` Lokal**: Seluruh *fixture* yang dibuat sebelum pemanggilan controller wajib dibersihkan di blok `finally` dari tes tersebut.
2. **Urutan Penghapusan Anak ke Induk (*Child-to-Parent Deletion*)**: Penghapusan wajib mengikuti urutan relasi dependensi Foreign Key (contoh: hapus `item_pesanan` -> `pesanan` -> `pelanggan`, atau `grup_produk_harga_level` -> `item` -> `grup_produk`).
3. **Larangan Bergantung Hanya pada Shutdown Hook**: `register_shutdown_function` hanya berfungsi sebagai jaring pengaman lapis kedua (*fallback safeguard*), bukan pengganti blok `finally` langsung pada setiap unit tes.
4. **Verifikasi Pasca-Uji (*Post-Test Hygiene Scan*)**: Setelah pengujian selesai, pastikan tidak ada record residu (`TEST-`, `TMP-`, `FXTR-`, `Dummy`) yang tersisa di tabel manapun.

---

## 4. Manajemen Skema Basis Data (*Database Migration Standard*)
1. Setiap modifikasi struktur tabel, indeks, view, fungsi RPC, atau kendala (*constraints*) wajib didokumentasikan dalam berkas migrasi SQL bernomor urut resmi pada direktori `database/migrations/` (format: `XX_deskripsi_migrasi.sql`).
2. Setiap berkas migrasi wajib menggunakan blok transaksi atomik (`BEGIN; ... COMMIT;`).
3. Berkas skema kanonikal `database/01_schema.sql` (*Single Source of Truth*) wajib selalu disinkronkan sesuai kondisi riil skema termutakhir.

---

## 5. Keamanan Kredensial & Kepatuhan Konfigurasi
- Kredensial sensitif, kunci API, kata sandi, dan token koneksi wajib dikelola secara ketat melalui berkas `.env` dan tidak boleh di-hardcode ke dalam kode sumber maupun diekspos ke log publik.
- Seluruh endpoint dan formulir mutasi data wajib dilindungi oleh verifikasi CSRF dan otorisasi RBAC berbasis izin spesifik.

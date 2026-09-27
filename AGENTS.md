# Engineering Guidelines & Agent Operational Protocols

## Overview
Dokumen ini menetapkan standar rekayasa perangkat lunak (*software engineering standards*), protokol pengujian otomatis, tata kelola migrasi, kebijakan hak akses Supabase Data API, dan pedoman integritas basis data yang **wajib dipatuhi tanpa pengecualian** oleh seluruh asisten pengembang berbasis AI (*Agentic AI*) dan kontributor teknis pada repositori **KEREN ONE**.

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

### D. Tata Kelola Registry Berkas Pengujian (*Single Source of Truth*)
1. Setiap berkas pengujian di `tests/` wajib terdaftar secara resmi di `App\Services\TestRunnerService::SUITES`.
2. Dilarang membuat berkas tes pecahan ad-hoc yang tumpang tindih (*orphan test files*).
3. Akses runner pengujian via peramban web (`/developer/tests`) wajib diproteksi secara eksklusif hanya untuk peran Developer (`Auth::requireDeveloper()`).

---

## 4. Manajemen Skema Basis Data & Kebijakan Hak Akses Supabase
1. **Pemberian Nomor Migrasi Resmi**: Setiap modifikasi struktur tabel, indeks, view, fungsi RPC, atau kendala (*constraints*) wajib didokumentasikan dalam berkas migrasi SQL bernomor urut resmi pada direktori `database/migrations/` (format: `XX_deskripsi_migrasi.sql`).
2. **Transaksi Atomik Wajib**: Setiap berkas migrasi wajib menggunakan blok transaksi atomik (`BEGIN; ... COMMIT;`).
3. **Sinkronisasi Skema Kanonikal**: Berkas skema kanonikal `database/01_schema.sql` dan `database/02_triggers_and_rpc.sql` (*Single Source of Truth*) wajib selalu disinkronkan sesuai kondisi riil skema termutakhir.
4. **Protokol Hak Akses Supabase PostgREST & Security Definer (Post-30 Okt 2026 Ready)**:
   - **Fungsi `SECURITY DEFINER` (Wajib Dikunci)**:
     Setiap fungsi `SECURITY DEFINER` wajib mencabut izin eksekusi publik dan hanya memberikan hak akses ke `postgres` dan `service_role` agar tidak bocor ke endpoint RPC Data API publik:
     ```sql
     REVOKE EXECUTE ON FUNCTION public.fn_nama_fungsi(...) FROM PUBLIC, anon, authenticated;
     GRANT EXECUTE ON FUNCTION public.fn_nama_fungsi(...) TO postgres, service_role;
     ```
   - **Tabel Baru di Skema Public (Explicit Grants Standard)**:
     Sesuai kebijakan Supabase (mulai 30 Oktober 2026), jika tabel baru dibuat untuk diakses langsung oleh SDK/API publik (misal Mobile App / Client JS), sertakan pernyataan `GRANT` eksplisit dan pasang *Row Level Security (RLS)*:
     ```sql
     GRANT SELECT, INSERT ON public.nama_tabel_baru TO authenticated;
     GRANT ALL ON public.nama_tabel_baru TO service_role;
     ALTER TABLE public.nama_tabel_baru ENABLE ROW LEVEL SECURITY;
     ```
   - **Tabel Sensitif & Keuangan (Zero Public Exposure)**:
     Tabel keuangan, kas, pengguna, dan pengaturan sistem **dilarang keras** diberi `GRANT` ke role `anon` / `authenticated`. Akses database murni melalui backend PHP PDO sebagai database owner.

---

## 5. Standar UI/UX, DNA Ekosistem Keren One & PWA
1. **Keselarasan DNA Warna & Notch**:
   - Warna tema status bar / *notch* wajib menggunakan standar `#881337` (*Deep Rose / Maroon*).
   - Warna aksen primer light mode: `#881337`, dark mode: `#fb7185`.
2. **Performa Render Bebas Lag (*Zero Delay Transition*)**:
   - Hindari menerapkan `filter: blur(...)` langsung pada elemen kontainer DOM besar (`<header>`, `<main>`).
   - Gunakan `backdrop-filter: blur(6px)` pada elemen overlay backdrop yang diakselerasi langsung oleh GPU (*hardware-accelerated*).
3. **Navigasi Cerdas PWA Standalone**:
   - Tombol tutup/kembali pada portal mandiri (seperti `/guide`) wajib menggunakan *Smart Navigation* (`window.close()` -> `window.history.back()` -> fallback `/dashboard`) agar pengguna tidak kehilangan konteks transaksi kerjaan saat membuka panduan dari PWA.

---

## 6. Keamanan Kredensial, CSRF & RBAC Guard
- Kredensial sensitif, kunci API, kata sandi, dan token koneksi wajib dikelola secara ketat melalui berkas `.env` dan tidak boleh di-hardcode ke dalam kode sumber maupun diekspos ke log publik.
- Seluruh endpoint mutasi data (`POST`, `PUT`, `DELETE`) dan formulir wajib dilindungi oleh verifikasi Anti-CSRF (`App\Helpers\CSRF`).
- Pembatasan hak akses wajib menggunakan otorisasi RBAC berlapis (`Auth::requirePermission()`, `Auth::requireRole()`, dan `Auth::requireDeveloper()`).

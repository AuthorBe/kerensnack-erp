# Rencana Rombak Modul Konsinyasi — 3 Perubahan Utama

> **Status:** Selesai / Terimplementasi Penuh  
> **Tanggal Dibuat:** 2026-09-05  
> **Scope:** `consignment/laporan-penjualan` + `consignment/piutang → tagihan` + Decoupling tagihan dari opname

---

## Ringkasan Perubahan

Tiga perubahan utama yang akan dilakukan terhadap modul konsinyasi:

1. **`/consignment/laporan-penjualan`** → Diubah total menjadi **Dashboard Grafik Performa Toko** (bukan lagi list transaksi)
2. **`/consignment/piutang`** → Dihapus total, diganti dengan **`/consignment/tagihan`** yang punya fitur buat tagihan manual
3. **Decoupling tagihan dari opname** → Opname tidak lagi auto-buat faktur. Tagihan dibuat manual oleh admin di halaman `/consignment/tagihan`

---

## Perubahan 1: Laporan Penjualan → Dashboard Grafik Performa Toko

### Konsep Halaman Baru

Halaman `/consignment/laporan-penjualan` menjadi **Sales Performance Dashboard** — tempat owner & admin bisa melihat performa penjualan setiap toko secara visual dan komprehensif.

### Komponen UI

```
┌─────────────────────────────────────────────────────────┐
│  Filter Bar: [Periode ▼] [Toko ▼] [Sales ▼] [Apply]    │
├─────────────────────────────────────────────────────────┤
│  4 KPI Cards: Total Omzet | Kunjungan | Avg/Kunjungan   │
│               | Total Toko Aktif                         │
├────────────────────┬────────────────────────────────────┤
│  Bar Chart         │  Donut Chart                        │
│  (Ranking Omzet    │  (Kontribusi % setiap toko          │
│   per Toko)        │   dari total omzet periode)         │
├─────────────────────────────────────────────────────────┤
│  Line Chart (Tren Omzet Harian/Mingguan per Periode)    │
├─────────────────────────────────────────────────────────┤
│  Tabel Ringkasan KPI per Toko                           │
│  Kolom: Toko | Sales | Omzet | Kunjungan | Avg/Visit    │
│         | Last Visit | % Kontribusi | Detail ↗           │
└─────────────────────────────────────────────────────────┘
```

### Filter Options
- **Periode:** Presets (Hari Ini, 7 Hari, Bulan Ini, Bulan Lalu) + Custom date range
- **Toko:** Dropdown pilih semua atau spesifik 1 toko (untuk owner/admin)
- **Sales:** Dropdown pilih semua atau spesifik 1 sales (untuk owner/admin)
- Untuk role **Sales**: auto-scoped ke toko binaan mereka

### Library Chart
- **Chart.js** via CDN (lightweight, no npm needed)

### Data yang Dibutuhkan (Query Baru)

**1. Data per toko (untuk bar chart + donut + tabel KPI):**
```sql
SELECT 
    p.id, p.nama_toko, p.kode_pelanggan,
    k.nama_karyawan as nama_sales,
    COUNT(kk.id) as total_kunjungan,
    COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet,
    ROUND(COALESCE(AVG(kk.total_laku_nominal), 0), 0) as avg_per_kunjungan,
    MAX(kk.tanggal_kunjungan) as last_visit
FROM public.pelanggan p
LEFT JOIN public.karyawan k ON p.sales_driver_id = k.id
LEFT JOIN public.kunjungan_konsinyasi kk ON kk.pelanggan_id = p.id 
    AND kk.tanggal_kunjungan BETWEEN :start AND :end
WHERE p.is_konsinyasi = TRUE AND p.status_aktif = TRUE
GROUP BY p.id, p.nama_toko, p.kode_pelanggan, k.nama_karyawan
ORDER BY total_omzet DESC
```

**2. Data tren harian (untuk line chart):**
```sql
SELECT 
    kk.tanggal_kunjungan,
    COALESCE(SUM(kk.total_laku_nominal), 0) as total_omzet_hari,
    COUNT(kk.id) as jumlah_kunjungan
FROM public.kunjungan_konsinyasi kk
JOIN public.pelanggan p ON kk.pelanggan_id = p.id
WHERE kk.tanggal_kunjungan BETWEEN :start AND :end
GROUP BY kk.tanggal_kunjungan
ORDER BY kk.tanggal_kunjungan ASC
```

### File yang Diubah

#### [MODIFY] [laporan_penjualan.php](file:///d:/laragon/www/kerensnack-erp/views/consignment/laporan_penjualan.php)
- **Hapus total:** Semua kode view lama (1187 baris list transaksi)
- **Buat baru:** Dashboard grafik dengan Chart.js (bar, line, donut) + tabel KPI per toko

#### [MODIFY] [ConsignmentController.php](file:///d:/laragon/www/kerensnack-erp/app/Controllers/ConsignmentController.php)
- Method `laporanPenjualan()`: Query diubah total — dari per-kunjungan ke agregasi per-toko + tren harian
- Method `exportSalesExcel()`: Update kolom export sesuai format baru (export tabel KPI per toko)

---

## Perubahan 2: Piutang → Tagihan (URL + File + Logika Baru)

### Apa yang DIHAPUS

| Yang dihapus | Lokasi |
|---|---|
| Route `GET /consignment/piutang` | `public/index.php` line 195 |
| Route `GET /consignment/piutang/export-excel` | `public/index.php` line 196 |
| Route `POST /consignment/piutang/bayar` | `public/index.php` line 197 |
| Method `piutang()` | `ConsignmentController.php` line 662 |
| Method `catatPembayaran()` | `ConsignmentController.php` line 713 |
| Method `exportPiutangExcel()` | `ConsignmentController.php` line 1192 |
| View file `piutang.php` | `views/consignment/piutang.php` |
| Kartu "Piutang Konsinyasi" di portal | `views/consignment/index.php` |

### Apa yang DIBUAT BARU

#### Routes Baru (di `public/index.php`)
```php
Router::get('/consignment/tagihan', [ConsignmentController::class, 'tagihanIndex']);
Router::post('/consignment/tagihan/generate', [ConsignmentController::class, 'tagihanGenerate']);
Router::post('/consignment/tagihan/bayar', [ConsignmentController::class, 'tagihanBayar']);
Router::get('/consignment/tagihan/export-excel', [ConsignmentController::class, 'tagihanExportExcel']);
```

#### View Baru: `tagihan.php` (2 Tab)

**Tab 1: "Buat Tagihan"**

```
┌─────────────────────────────────────────────────────────┐
│  Filter: [Pilih Toko ▼] [Dari Tanggal] [Sampai] [Cari] │
├─────────────────────────────────────────────────────────┤
│  Daftar Kunjungan BELUM Ditagih (multi-select checkbox) │
│  ☐ KONSIN-20260901 | Toko A | 01/09/2026               │
│    Sales: Budi     | Omzet: Rp 1.250.000                │
│  ☐ KONSIN-20260903 | Toko A | 03/09/2026               │
│    Sales: Budi     | Omzet: Rp 980.000                  │
│                                                         │
│  Preview Total: Rp 2.230.000 (2 kunjungan terpilih)    │
│  [Generate Tagihan →]                                   │
└─────────────────────────────────────────────────────────┘
```

> **Note:** Kunjungan yang bisa ditagih = `kunjungan_konsinyasi` yang `total_laku_nominal > 0` AND belum punya tagihan (tidak ada di `tagihan_kunjungan`)

**Tab 2: "Daftar Tagihan"**

```
┌─────────────────────────────────────────────────────────┐
│  Filter Status: [Semua] [Belum Lunas] [Sebagian][Lunas] │
│  Outstanding Total: Rp X.XXX.XXX                        │
├─────────────────────────────────────────────────────────┤
│  Tabel: Toko | No. Tagihan | Tanggal | Total |          │
│         Dibayar | Sisa | Status | Aksi (PDF + Bayar)    │
└─────────────────────────────────────────────────────────┘
```

#### Methods Baru di Controller

- **`tagihanIndex()`** — Ambil kunjungan belum ditagih + daftar semua tagihan, render `consignment.tagihan`
- **`tagihanGenerate()`** — Terima `kunjungan_ids[]`, validasi, call `fn_buat_tagihan_konsinyasi()`
- **`tagihanBayar()`** — Catat pembayaran via `fn_catat_pembayaran_konsinyasi()` (reuse existing)
- **`tagihanExportExcel()`** — Export daftar tagihan ke Excel

---

## Perubahan 3: DB Schema + Functions

### 3a. Migration SQL Baru: `09_migration_tagihan_manual.sql`

**Tabel baru `tagihan_kunjungan` (Junction Table):**
```sql
CREATE TABLE public.tagihan_kunjungan (
    id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    pesanan_id UUID NOT NULL REFERENCES public.pesanan(id) ON DELETE CASCADE,
    kunjungan_id UUID NOT NULL REFERENCES public.kunjungan_konsinyasi(id) ON DELETE RESTRICT,
    dibuat_pada TIMESTAMPTZ DEFAULT NOW() NOT NULL,
    UNIQUE(kunjungan_id)  -- 1 kunjungan hanya bisa masuk 1 tagihan
);

CREATE INDEX idx_tagihan_kunjungan_pesanan ON public.tagihan_kunjungan(pesanan_id);
CREATE INDEX idx_tagihan_kunjungan_kunjungan ON public.tagihan_kunjungan(kunjungan_id);
```

> **Kenapa `UNIQUE(kunjungan_id)`?**  
> Safeguard penting: 1 kunjungan tidak bisa di-billing 2 kali ke tagihan berbeda. DB-level constraint ini lebih aman daripada cek di aplikasi.

**Update `fn_proses_kunjungan_konsinyasi()`:**
- Hapus seluruh blok `IF v_total_laku_netto > 0 THEN ... INSERT INTO pesanan ...`
- Tetap kembalikan JSON response (tapi `pesanan_id` dan `nomor_nota` jadi `NULL`)
- Kunjungan selesai disimpan dengan `pesanan_id = NULL`

**Function baru `fn_buat_tagihan_konsinyasi(p_kunjungan_ids UUID[], p_pengguna_id UUID)`:**
- Validasi semua kunjungan dari 1 pelanggan yang sama
- Validasi tidak ada yang sudah di-billing (cek `tagihan_kunjungan`)
- Hitung total dari semua kunjungan terpilih
- INSERT `pesanan` (tagihan baru)
- INSERT `item_pesanan` (agregasi item dari semua kunjungan terpilih — GROUP BY item)
- INSERT ke `tagihan_kunjungan` (junction)
- UPDATE `kunjungan_konsinyasi.pesanan_id` untuk backward compat
- UPDATE `pelanggan.total_piutang_berjalan`

### 3b. Update `opname_hasil.php` — Banner Invoice

| Kondisi | Banner Lama | Banner Baru |
|---------|-------------|------------|
| `laku > 0, pesanan_id = NULL` | — (tidak ada state ini sebelumnya) | Biru: "Kunjungan dicatat. Buat tagihan di menu **Tagihan Konsinyasi**" |
| `pesanan_id IS NOT NULL` | Hijau: "FAKTUR OTOMATIS TERBIT" | Hijau: "Sudah masuk Tagihan **[nomor_nota]**" |
| `laku = 0` | Biru: "NIHIL PENJUALAN" | Abu: "Nihil penjualan" |

### 3c. Update Portal `index.php`
- Hapus kartu "Piutang Konsinyasi" (`/consignment/piutang`)
- Tambah kartu "Tagihan Konsinyasi" (`/consignment/tagihan`) dengan icon & deskripsi baru

### 3d. Update `header.php` — Breadcrumb untuk `/consignment/tagihan`

---

## Ringkasan Semua File yang Berubah

| File | Aksi | Detail |
|------|------|--------|
| `views/consignment/laporan_penjualan.php` | **REWRITE** | Ganti total → dashboard grafik Chart.js |
| `views/consignment/piutang.php` | **DELETE** | Dihapus, diganti tagihan.php |
| `views/consignment/tagihan.php` | **NEW** | Halaman tagihan (2 tab) |
| `views/consignment/opname_hasil.php` | **MODIFY** | Update banner invoice |
| `views/consignment/index.php` | **MODIFY** | Ganti kartu piutang → tagihan |
| `app/Controllers/ConsignmentController.php` | **MODIFY** | Hapus 3 method piutang, tambah 4 method tagihan, rewrite laporanPenjualan() |
| `public/index.php` | **MODIFY** | Hapus 3 route piutang, tambah 4 route tagihan |
| `database/09_migration_tagihan_manual.sql` | **NEW** | Create table + update functions |
| `views/layouts/header.php` | **MODIFY** | Breadcrumb untuk /consignment/tagihan |

---

## Rencana Verifikasi

1. **Opname flow** → pastikan TIDAK ada faktur otomatis → `kunjungan_konsinyasi.pesanan_id = NULL`
2. **Generate tagihan** → pilih 2 kunjungan 1 toko → generate → cek 1 `pesanan`, 2 baris `tagihan_kunjungan`, `pesanan_id` di kunjungan terisi
3. **Duplicate prevention** → coba generate untuk kunjungan yg sudah ditagih → harus error
4. **Catat bayar** → nominal berkurang di `pesanan.sisa_tagihan`, `arus_kas` terbuat
5. **Laporan grafik** → bar, line, donut, tabel KPI tampil dan update saat filter berubah
6. **Portal menu** → kartu Tagihan Konsinyasi muncul dan link ke `/consignment/tagihan`
7. **Old routes** → `/consignment/piutang` → 404 (route dihapus)
8. **Data historis** → Tagihan lama (yang dibuat otomatis) tetap muncul di Tab Daftar Tagihan

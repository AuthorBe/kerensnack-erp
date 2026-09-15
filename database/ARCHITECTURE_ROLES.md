# Panduan Arsitektur & Aturan Baku: Pemisahan Sales vs Driver

Dokumen ini merupakan **kontrak arsitektur resmi** bagi seluruh pengembang manusia dan asisten AI yang memodifikasi basis data, logika controller, serta modul antarmuka KEREN Snack ERP.

---

## 1. Filosofi & Prinsip Utama

Sistem membedakan secara tegas antara posisi **Sales** dan **Driver**:
1. **Sales**:
   - Memegang pembinaan dan tanggung jawab toko mitra konsinyasi (**Toko Binaan**).
   - Berhak mendapatkan **komisi penjualan/konsinyasi** (`persentase_komisi_sales > 0`).
   - Dapat ditugaskan melakukan pengantaran barang (`surat_jalan`) dan belanja/ambil bahan baku vendor (`pembelian`).
   - Berwenang membuat pesanan pelanggan (`orders.create`), melihat katalog & matriks harga, serta mengunjungi toko konsinyasi.

2. **Driver**:
   - Murni bertugas pada **operasional armada logistik** (pengantaran pesanan & penjemputan barang supplier).
   - **MUTLAK DILARANG** ditetapkan sebagai penanggung jawab / pembina toko binaan (`pelanggan.sales_driver_id`).
   - **MUTLAK DILARANG** memiliki komisi penjualan (`persentase_komisi_sales = 0.00`).
   - Mengakses tugas harian melalui modul **Surat Jalan Pengiriman** (`deliveries.*`) dan serah terima dokumen POD.

---

## 2. Fleksibilitas Operasional Berbasis RBAC ("Tiket Izin Akses")

Terkait aktivitas **Opname Fisik Rak Konsinyasi** (`kunjungan_konsinyasi`):
- Driver **TIDAK DIBLOKIR KAKU** oleh trigger database untuk mencatat kunjungan opname rak.
- Siapa yang berhak melakukan opname fisik rak **dikendalikan secara dinamis melalui tiket izin akses RBAC** (`consignment.opname_all` atau `consignment.opname_assigned`).
- **Ketentuan Owner**: Jika Owner memberikan izin opname kepada Driver (misal supir saat mengantar barang diminta sekalian menghitung sisa stok rak toko), Driver dapat mencatat opname rak toko.
- **Proteksi Komisi**: Aktivitas opname oleh Driver **murni pencatatan fisik stok**. Nilai omset laku konsinyasi dari toko tersebut **TETAP dialokasikan kepada Sales Pembina Toko**, dan Driver **tetap tidak menerima komisi** (komisi driver tetap 0.00%).

---

## 3. Pengaman Integritas Berlapis (Multi-Layer Safeguards)

### A. Level Basis Data (PostgreSQL Triggers)
Tercantum pada `database/32_add_sales_driver_integrity_guards.sql` dan disinkronkan ke `02_triggers_and_rpc.sql`:
1. **`trg_guard_pelanggan_sales_driver` pada `public.pelanggan`**:
   - Mencegah penetapan karyawan dengan posisi `driver` pada kolom `sales_driver_id`. Jika dicoba, database langsung menolak dengan pengecualian:
     `Driver (%) tidak dapat ditugaskan sebagai Penanggung Jawab Toko Binaan! Posisi karyawan harus Sales.`
2. **`trg_guard_karyawan_driver_no_commission` pada `public.karyawan`**:
   - Mencegah pemberian komisi `persentase_komisi_sales > 0` pada karyawan berposisi `driver`. Database menolak dengan pengecualian:
     `Driver (%) tidak berhak mendapatkan komisi penjualan! Nilai persentase komisi driver harus 0.00%.`
3. **`trg_guard_pengguna_driver_reset_commission` pada `public.pengguna`**:
   - Jika posisi karyawan dialihkan menjadi `driver`, sistem secara otomatis mereset komisi ke `0.00%` di tabel `karyawan`.

### B. Level Backend (PHP Controllers)
1. **`CustomerController.php`**:
   - Metode `store()` dan `update()` memvalidasi bahwa `sales_driver_id` toko binaan yang dipilih berposisi `sales`.
2. **`EmployeeController.php`**:
   - Metode `store()` dan `update()` mengunci nilai komisi menjadi `0.00` apabila posisi karyawan bukan `sales`.
3. **`UserController.php`**:
   - Sinkronisasi otomatis posisi karyawan sesuai peran (`driver` -> `driver`, `sales` -> `sales`).

---

## 4. Konvensi Penamaan Kolom Fisik Database

Secara historis dari fase awal, beberapa tabel relasional PostgreSQL menggunakan nama kolom berakhiran `sales_driver_id`. Kolom-kolom ini memiliki arti fungsional yang berbeda:
- `public.pelanggan.sales_driver_id` $\rightarrow$ **Sales Pembina Toko** (Khusus posisi Sales).
- `public.kunjungan_konsinyasi.sales_driver_id` $\rightarrow$ **Petugas Opname Fisik Rak** (Sales, atau Driver bertiket RBAC).
- `public.pesanan.sales_driver_id` $\rightarrow$ **Petugas yang Menangani Order / Pengiriman** (Driver atau Sales).
- `public.surat_jalan.sales_driver_id` $\rightarrow$ **Driver / Kurir Pengantar Logistik** (Driver atau Sales).
- `public.pembelian.sales_driver_id` $\rightarrow$ **Driver / Petugas Jemput Belanjaan Vendor** (Driver atau Sales).

Pengembang dan AI masa depan **TIDAK BOLEH** menyatukan logika Sales dan Driver hanya karena kesamaan nama kolom fisik tersebut.

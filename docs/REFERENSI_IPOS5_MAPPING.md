# Mapping Struktur Database IPOS 5 (`db kerensnack.i5bu`) ke Skema Supabase Modern

Dokumen ini merupakan hasil reverse-engineering dari file backup database **IPOS 5 Profesional (`db kerensnack.i5bu`)** milik KEREN Snack. File tersebut adalah format **PostgreSQL Custom Dump (`PGDMP`)** yang berisi 104 tabel sistem POS & Akuntansi.

File DDL schema lengkap hasil ekstraksi telah disimpan di: [`database/legacy_reference/ipos5_clean_schema.sql`](../database/legacy_reference/ipos5_clean_schema.sql).

---

## 1. Perbandingan & Pemetaan Modul Utama (IPOS 5 vs Supabase Baru)

| Modul Bisnis | Tabel Asli IPOS 5 (`.i5bu`) | Tabel Target Supabase Modern | Keterangan & Keunggulan Skema Baru |
| :--- | :--- | :--- | :--- |
| **Master Produk & SKU** | `tbl_item`<br>`tbl_itemsatuan`<br>`tbl_itemsatuanjml` | `item`<br>`grup_produk` | Menggunakan UUIDv4, penamaan standar bahasa Indonesia, serta relasi kelompok upah borongan. |
| **Multi-Tier Harga Jual** | `tbl_itemhj` | `item_harga_level` | Menyederhanakan level harga (ritel, grosir kecil, agen, distributor) per satuan dasar (pcs) & distribusi (bal). |
| **Resep / Bill of Materials** | `tbl_itemrakitan` | `komposisi_item` | Resep perakitan snack curah ke pack dengan trigger auto potong bahan mentah saat mandor catat hasil packing. |
| **Pelanggan (Customer)** | `tbl_supel` *(tipe = 'P')* | `pelanggan` | Pemisahan entitas pelanggan & supplier, tracking limit kredit (`plafon_piutang`), dan nomor WhatsApp bot. |
| **Pemasok (Vendor)** | `tbl_supel` *(tipe = 'S')* | `pemasok` | Menyimpan rekening perbankan dalam format JSONB terstruktur. |
| **Rute & Wilayah** | `tbl_supel_wil`<br>`tbl_supel_subwil` | `wilayah` | Mengatur master rute ekspedisi/driver per sub-wilayah logistik. |
| **Stok & Ledger Mutasi** | `tbl_itemstok`<br>`tbl_itemopname` | `riwayat_stok`<br>`penyesuaian_stok` | **Upgrade ke Double-Entry Ledger Immutable**: Setiap pergerakan stok dicatat di buku besar anti-selisih. |
| **Penjualan / Invoice** | `tbl_ikhd`<br>`tbl_ikdt` | `pesanan`<br>`item_pesanan` | Terhubung langsung dengan nomor nota, status invoice, dan otomatisasi PDF Surat Jalan oleh n8n. |
| **Pembelian / PO** | `tbl_imhd`<br>`tbl_imdt` | `pembelian`<br>`rincian_pembelian` | Pencatatan faktur masuk barang dari vendor/pemasok. |
| **Pelunasan Piutang & Hutang** | `tbl_byrpiutanghd`<br>`tbl_byrhutanghd` | `arus_kas`<br>`pesanan` *(status_pembayaran)* | Otomatis menambah saldo kas toko dan mengubah status pesanan menjadi `lunas`. |
| **Kas & Bank Toko** | `tbl_acckashd`<br>`tbl_acckasdt`<br>`tbl_bank` | `akun_kas`<br>`draf_pengeluaran`<br>`arus_kas` | Mendukung pelaporan biaya via bot Telegram + sistem persetujuan (approval) interaktif Owner. |

---

## 2. Detail Kolom Kunci IPOS 5 yang Diadaptasi

### A. Master Barang (`tbl_item` & `tbl_itemhj` ➔ `item` & `item_harga_level`)
* `kodeitem` ➔ `kode_sku` (Unique SKU)
* `namaitem` ➔ `nama_item`
* `jenis` / `merek` ➔ `merek` & `grup_id`
* `satuan` ➔ `satuan_dasar` (pcs/kg) & `satuan_distribusi` (bal)
* `hargapokok` ➔ `harga_pokok_pembelian` (HPP)
* `stokmin` ➔ `stok_minimum_peringatan` (Alert low-stock n8n)
* `tbl_itemhj.level` & `tbl_itemhj.hargajual` ➔ `item_harga_level.level_harga` & `harga_jual_per_satuan_dasar`

### B. Master BoM Perakitan (`tbl_itemrakitan` ➔ `komposisi_item`)
* `kodeitem` (Barang Jadi) ➔ `item_jadi_id`
* `kodeitemrakitan` (Bahan Baku Curah / Plastik) ➔ `item_bahan_id`
* `jumlah` ➔ `jumlah_kebutuhan`

### C. Master Pelanggan & Supplier (`tbl_supel` ➔ `pelanggan` & `pemasok`)
* `kode` ➔ `kode_pelanggan` / `kode_pemasok`
* `nama` ➔ `nama_toko` / `nama_pemasok`
* `alamat`, `telepon` ➔ `alamat_lengkap`, `nomor_telepon`
* `limitjmlhupi` ➔ `plafon_piutang` (Limit maksimal piutang toko)
* `limitharihupi` / `harijt` ➔ `tipe_pembayaran` (cash / tempo 7 hari / 14 hari)
* `kdwilayah` / `kdsubwil` ➔ `wilayah_id`

### D. Transaksi Penjualan (`tbl_ikhd` + `tbl_ikdt` ➔ `pesanan` + `item_pesanan`)
* `notransaksi` ➔ `nomor_nota`
* `kodesupel` ➔ `pelanggan_id`
* `tanggal` ➔ `tanggal_pesanan`
* `subtotal` ➔ `total_bruto`
* `potnomfaktur` ➔ `total_diskon`
* `totalakhir` ➔ `total_netto`
* `jmlkredit` > 0 ➔ `status_pembayaran = 'tempo'`, `jmltunai` = `totalakhir` ➔ `status_pembayaran = 'lunas'`
* `tbl_ikdt.kodeitem` ➔ `item_pesanan.item_id`
* `tbl_ikdt.jumlah` ➔ `item_pesanan.kuantitas_satuan_dasar`
* `tbl_ikdt.harga` ➔ `item_pesanan.harga_satuan_deal`

---

## 3. Rencana Migrasi Data Langsung dari `.i5bu`
Karena file `.i5bu` adalah database PostgreSQL standar, kita bisa mengekstrak seluruh data master riil milik KEREN Snack (seperti seluruh daftar pelanggan aktif, histori harga jual, dan seluruh SKU item barang) langsung ke database Supabase baru menggunakan skrip migrasi otomatis.

# ADDENDUM & STATUS VALIDASI DATABASE NYATA (LIVE DB & DUMP 2026-08-28)
## Modul Konsinyasi — KEREN SNACK ERP

**Status:** Terverifikasi terhadap Live Database Supabase & `docs/dump-postgres-202608281832.sql`  
**Tanggal Validasi:** 31 Agustus 2026  
**Rujukan:** `docs/PRD-Konsinyasi-PENYEMPURNAAN.md`  

---

## 1. Hasil Audit & Konfirmasi Poin 1, 2, 3 terhadap Live DB

Setelah dilakukan pengecekan langsung ke **Live Database PostgreSQL Supabase** dan file dump resmi `docs/dump-postgres-202608281832.sql`, berikut status validasi sebenarnya:

| Poin Temuan Awal | Status di Live DB | Keterangan & Bukti di Database Nyata |
|:---|:---:|:---|
| **Poin 1: Kolom Pembayaran di `pesanan`**<br>(`sisa_tagihan`, `total_dibayar`, `akun_kas_id`) | 🟢 **SUDAH AMAN** | Kolom `akun_kas_id (uuid)`, `total_dibayar (numeric)`, dan `sisa_tagihan (numeric)` **sudah ada** di tabel `pesanan`. Hanya tinggal menambah kolom `adalah_tagihan (boolean)` sesuai PRD §2.3. |
| **Poin 2: Kolom & Mutasi `retur_bagus`** | 🟢 **SUDAH AMAN** | Kolom `retur_bagus (integer)` dan `selisih_qty (integer)` **sudah ada** di `rincian_kunjungan_konsinyasi`. Mutasi penambahan stok gudang (`konsinyasi_retur_masuk`) juga sudah terpasang di `fn_proses_kunjungan_konsinyasi`. |
| **Poin 3: Pembersihan `tambah_titip_baru` di fungsi opname** | 🟢 **SUDAH AMAN** | Fungsi `fn_proses_kunjungan_konsinyasi` di live DB **sudah bersih** (sudah tidak memotong stok gudang langsung via `tambah_titip_baru`, melainkan mengoper nilai 0 ke detail). |

> [!NOTE]
> **Mengapa sempat muncul di analisa awal?**
> Karena file `database/01_schema.sql` dan `database/02_triggers_and_rpc.sql` di repository lokal merupakan skema awal (*baseline*), sedangkan di **Live Database Supabase & file dump `dump-postgres-202608281832.sql`** ketiga poin di atas sudah di-patch sebelumnya.

---

## 2. Status Riil Checklist Database & Gap yang Masih Harus Dikerjakan

Berikut hasil pengecekan langsung pada tabel dan constraint di Live Database saat ini:

### A. Yang SUDAH ADA di Live DB (Siap Pakai):
1. ✅ Tabel `pesanan` sudah memiliki kolom `akun_kas_id`, `total_dibayar`, dan `sisa_tagihan`.
2. ✅ Tabel `rincian_kunjungan_konsinyasi` sudah memiliki kolom `retur_bagus` dan `selisih_qty`.
3. ✅ Rumus hitung laku terjual di `fn_proses_kunjungan_konsinyasi` sudah menggunakan:
   $$\text{Laku} = \text{Stok Awal} - (\text{Sisa Fisik} + \text{Retur Bagus} + \text{Retur Rusak})$$
4. ✅ Logic pengembalian stok barang bagus (`retur_bagus > 0`) ke stok gudang pusat via `konsinyasi_retur_masuk` sudah berjalan di RPC.

---

### B. Yang MASIH BELUM ADA / WAJIB DIKERJAKAN di Live DB (Sesuai PRD Final):

```sql
-- ==============================================================================
-- DELTA MIGRASI RESMI YANG WAJIB DIJALANKAN DI LIVE DATABASE SUPABASE
-- ==============================================================================

-- 1. 🐞 FIX BUG AKTIF UTAMA: Tambah 'konsinyasi_retur_rusak' ke CHECK constraint riwayat_stok
-- Status di Live DB saat ini: BELUM ADA 'konsinyasi_retur_rusak' sehingga opname yg ada barang rusak akan ROLLBACK!
ALTER TABLE public.riwayat_stok DROP CONSTRAINT IF EXISTS riwayat_stok_tipe_mutasi_check;
ALTER TABLE public.riwayat_stok ADD CONSTRAINT riwayat_stok_tipe_mutasi_check
CHECK (((tipe_mutasi)::text = ANY ((ARRAY[
    'produksi_masuk','bahan_terpakai_produksi','penjualan_keluar','pembelian_masuk',
    'penyesuaian_opname_tambah','penyesuaian_opname_kurang','retur_pelanggan_masuk',
    'konsinyasi_keluar','konsinyasi_retur_masuk','konsinyasi_retur_rusak'
])::text[])));

-- 2. Tambah kolom sales_driver_id di pelanggan (PRD §2.2)
-- Status di Live DB: BELUM ADA
ALTER TABLE public.pelanggan
    ADD COLUMN IF NOT EXISTS sales_driver_id UUID REFERENCES public.karyawan(id);
CREATE INDEX IF NOT EXISTS idx_pelanggan_sales_driver ON public.pelanggan(sales_driver_id);

-- 3. Tambah kolom adalah_tagihan di pesanan (PRD §2.3)
-- Status di Live DB: BELUM ADA
ALTER TABLE public.pesanan
    ADD COLUMN IF NOT EXISTS adalah_tagihan BOOLEAN DEFAULT TRUE NOT NULL;

-- 4. Tambah status 'sebagian' di constraint pesanan_status_pembayaran_check (PRD §2.4)
-- Status di Live DB saat ini: HANYA ['belum_lunas', 'tempo', 'lunas', 'dibatalkan']
ALTER TABLE public.pesanan DROP CONSTRAINT IF EXISTS pesanan_status_pembayaran_check;
ALTER TABLE public.pesanan ADD CONSTRAINT pesanan_status_pembayaran_check
CHECK (((status_pembayaran)::text = ANY ((ARRAY[
    'belum_lunas','sebagian','tempo','lunas','dibatalkan'
])::text[])));

-- 5. Tambah status 'ditolak_owner' di constraint surat_jalan (Penyempurnaan Alur Reject C2)
ALTER TABLE public.surat_jalan DROP CONSTRAINT IF EXISTS surat_jalan_status_surat_jalan_check;
ALTER TABLE public.surat_jalan ADD CONSTRAINT surat_jalan_status_surat_jalan_check
CHECK (((status_surat_jalan)::text = ANY ((ARRAY[
    'draf_n8n','disetujui_owner','sedang_dikirim','selesai_diterima','gagal_kembali','ditolak_owner'
])::text[])));

-- 6. Tambah kolom valuasi kerugian rusak di rincian_kunjungan_konsinyasi (PRD §2.5)
-- Status di Live DB: BELUM ADA
ALTER TABLE public.rincian_kunjungan_konsinyasi
    ADD COLUMN IF NOT EXISTS harga_pokok_satuan NUMERIC(15, 2) DEFAULT 0.00 NOT NULL,
    ADD COLUMN IF NOT EXISTS nilai_kerugian_rusak NUMERIC(15, 2) DEFAULT 0.00 NOT NULL;

-- 7. Buat fungsi fn_catat_pembayaran_konsinyasi (PRD §2.7)
-- Status di Live DB: BELUM ADA

-- 8. Buat trigger fn_trg_proses_pengiriman_konsinyasi pada surat_jalan (PRD §2.8)
-- Status di Live DB: BELUM ADA

-- 9. Update fungsi fn_proses_kunjungan_konsinyasi:
-- Tambahkan snapshot HPP & hitung nilai_kerugian_rusak saat retur_rusak > 0
```

---

## 3. Catatan Penting Penyelarasan Alur Operasional (UI / UX)

1. **Konfirmasi Penerimaan Kiriman (Sales A1.1):**
   * Di aplikasi Sales Mobile, card toko atau tab pengiriman harus menyediakan tombol bagi sales untuk mengubah status surat jalan menjadi `selesai_diterima`, sehingga trigger database otomatis memotong stok gudang dan menambah stok titip rak.
2. **Kondisi Penolakan Pengiriman (Owner C2):**
   * Jika Owner mengklik tombol "Tolak", status surat jalan berubah menjadi `ditolak_owner` dan pesanan draf menjadi `dibatalkan` dengan mencantumkan alasan penolakan.
3. **Filter Form Opname A2:**
   * Menampilkan semua SKU yang pernah dititipkan di toko tersebut (walaupun `stok_titip_saat_ini = 0`) agar sales dapat mengonfirmasi fisik 0 dan meminta restock di layar A4.

# Buku Panduan Arsitektur Sistem, Karyawan AI & Integrasi n8n
## Toko KEREN Snack (Next-Gen Autonomous ERP)

---

## 1. Model Bisnis Kunci: Sales adalah Driver (Canvaser Terpadu)

Di operasional harian **KEREN Snack**, staf lapangan bertindak sebagai **Sales-Driver (Canvaser)**:
1. Menyetir mobil/motor pengiriman rute logistik.
2. Memuat barang sesuai jadwal rute (Loading Sheet).
3. Mengantar barang pesanan dan menyerahkan nota ke toko customer.
4. Menawarkan produk tambahan / input order baru langsung di tempat (Canvas Order).
5. Menerima pembayaran tunai atau menagih piutang jatuh tempo di toko langganan.
6. Melaporkan biaya operasional rute harian (bensin, tol, parkir) via Bot Telegram.
7. Menyetorkan uang fisik hasil penagihan rute ke Kasir/Owner saat pulang sore.

---

## 2. Superpower & Kemampuan Lengkap Bot Telegram Karyawan AI

Bot Telegram bukan sekadar pencatat pengeluaran kas, melainkan **Pusat Kendali Operasional Lapangan**:

```
                       ┌──────────────────────────────────────────────────┐
                       │          SALES-DRIVER & MANDOR LAPANGAN          │
                       │           (Aplikasi Telegram di HP)              │
                       └────────────────────────┬─────────────────────────┘
                                                │
                 ┌──────────────────────────────┼──────────────────────────────┐
                 │                              │                              │
                 ▼                              ▼                              ▼
    ┌───────────────────────────┐  ┌───────────────────────────┐  ┌───────────────────────────┐
    │ 1. MANIFEST RUTE & LOADING│  │ 2. DIRECT CANVAS ORDER    │  │ 3. LIVE PROOF OF DELIVERY │
    │ Auto-send list toko besok,│  │ Input pesanan baru via    │  │ Tombol [📦 DITERIMA] &    │
    │ total bal muatan mobil,   │  │ voice/chat, auto cek      │  │ upload foto bukti terima  │
    │ & download PDF Surat Jalan│  │ plafon kredit toko.       │  │ nota/toko.                │
    └───────────────────────────┘  └───────────────────────────┘  └───────────────────────────┘
                 │                              │                              │
                 ▼                              ▼                              ▼
    ┌───────────────────────────┐  ┌───────────────────────────┐  ┌───────────────────────────┐
    │ 4. TAGIH PIUTANG & SETORAN│  │ 5. CEK STOK & SISA PIUTANG│  │ 6. LAPOR BIAYA & SETTLE   │
    │ Terima uang cash tagihan  │  │ Chat tanya sisa stok live │  │ Foto struk bensin / tol & │
    │ toko, nota auto lunas.    │  │ atau status piutang toko. │  │ rekap kas tutup rute sore.│
    └───────────────────────────┘  └───────────────────────────┘  └───────────────────────────┘
```

### Rincian 6 Fitur Utama Bot Telegram:

#### A. Manifest Pengiriman & Rute Harian (Kirim Otomatis H-1 / Pagi)
* **Jadwal**: Dikirim otomatis oleh n8n jam 18:00 (sore) atau 07:00 (pagi).
* **Format Pesan**:
  > 🚚 **MANIFEST PENGIRIMAN HARI INI**
  > - **Driver**: Pak Slamet
  > - **Rute**: Tangerang Timur (Ciledug & Cipondoh)
  > - **Total Muatan**: 45 Bal Berondong Beras, 20 Bal Kerupuk Jengkol
  > - **Daftar Kunjungan**:
  >   1. Toko Berkah (10 Bal) - [Nota: INV-001]
  >   2. Toko Maju Jaya (15 Bal) - [Nota: INV-002]
  >   3. Toko Sumber Rejeki (20 Bal) - [Nota: INV-003]
  > 
  > 📄 [Download PDF Semua Surat Jalan]

#### B. Direct Canvas Order di Toko (Input Order di Tempat)
* Sales-Driver cukup kirim pesan suara: *"Toko Berkah minta tambah 5 bal Berondong Beras bayar tempo 7 hari"*.
* Gemini mengekstrak data ➔ n8n mengecek sisa plafon kredit toko via RPC Supabase ➔ Surat Jalan & Invoice baru terbit.

#### C. Bukti Terima Barang (POD - Proof of Delivery)
* Saat menurunkan barang di toko, Sales-Driver klik tombol inline di Telegram: **[📦 BARANG DITERIMA]** dan kirim foto nota bertanda tangan.
* Status Surat Jalan otomatis berubah menjadi `selesai_diterima`, waktu sampai tercatat, dan foto tersimpan di Supabase Storage.

#### D. Penagihan Piutang & Setor Tunai di Rute
* Sales-Driver menagih toko: *"Terima uang pelunasan Toko Maju Jaya Rp 1.500.000 cash"*.
* Bot memvalidasi nota toko, mengubah status menjadi `lunas`, memotong saldo piutang toko, dan mencatat uang fisik tersebut di kas berjalan yang dipegang Sales-Driver.

#### E. Cek Stok & Cek Piutang Live
* Perintah instan: `/stok berondong` atau chat *"Sisa stok kerupuk tenggiri di gudang berapa bal?"*.
* Perintah piutang: `/cekpiutang Toko Majestik` ➔ Bot menampilkan sisa batas utang dan daftar nota yang belum lunas.

#### F. Rekap Tutup Rute Harian (Settlement Sore)
* Saat pulang ke toko jam 17:00, bot mengirimkan ringkasan kas & muatan:
  > 🏁 **REKAP TUTUP RUTE - PAK SLAMET**
  > - Toko Sukses: 8 / 8 Toko
  > - Total Uang Tunai Diterima (Tagihan + Cash): **Rp 4.250.000**
  > - Pengeluaran Bensin & Tol: **Rp 120.000** (Disetujui)
  > - **Uang Fisik yang Harus Disetor ke Kasir**: **Rp 4.130.000**
  > - Sisa Muatan Barang di Mobil: 0 Bal

---

## 3. Matriks Otorisasi RBAC & RLS (Row Level Security)

| Peran | Modul Master | Modul Stok & BoM | Penjualan & Surat Jalan | HR & Payroll | Arus Kas & Draf |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Owner** | Full Access | Full Access | Full Access | Full Access | Full Access + Approval |
| **Admin Toko** | Read/Write | Read/Write | Full Access | Read Only | Read Arus Kas, Kasir Transaksi |
| **Mandor** | Read Only | Read Item & BoM | Read Surat Jalan | Full Access (Absensi & Produksi) | Insert Draf Pengeluaran |
| **Sales-Driver** | Read Rute & Toko | Read Stok Live | Read Assigned SJ, POD Foto, Order Rute | Read Own Komisi & Slip Gaji | Insert Draf Kas, Terima Tagihan |
| **Service Role (n8n)** | Full Access | Full Access | Full Access | Full Access | Full Access |

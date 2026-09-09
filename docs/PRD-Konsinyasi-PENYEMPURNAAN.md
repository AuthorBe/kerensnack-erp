# PRD FINAL (DISEMPURNAKAN) — Fitur Konsinyasi Rak Toko (KEREN SNACK)

**Status:** Final & Disempurnakan — siap eksekusi  
**Tanggal:** 1 September 2026 (Revisi UI/UX — Portal Konsinyasi)  
**Menggantikan:** Semua draf sebelumnya (`PRD-Penyempurnaan-Konsinyasi.md`, `v2`, `UIUX.md`) — dokumen ini adalah **satu-satunya rujukan final**.  

---

## 0. INSTRUKSI UNTUK AI (Gemini / Claude) — BACA DULU SEBELUM NGODING

Dokumen ini dipakai sebagai spek buat AI coding assistant. Ikuti aturan main berikut, JANGAN dilanggar:

1. **Dokumen ini adalah single source of truth.** Jangan menambah aturan bisnis, field, tabel, atau layar yang tidak disebutkan di sini, walau kelihatan "masuk akal" atau "biasanya ada di aplikasi seperti ini". Kalau ada kebutuhan yang menurutmu kurang jelas atau ada celah, **STOP dan tanya ke user, jangan berasumsi sendiri.**
2. **Nama tabel, kolom, dan fungsi HARUS persis seperti yang tertulis di dokumen ini** (bahasa Indonesia, snake_case) — jangan diterjemahkan ke bahasa Inggris, jangan disingkat/diubah gaya penamaannya sendiri. Ini demi konsistensi dengan skema database yang sudah berjalan.
3. **Jangan mengubah tabel/kolom/fungsi yang tidak disebutkan di dokumen ini.** Sistem ini sudah punya modul lain yang jalan (payroll, produksi, kasbon, dll) — dokumen ini HANYA tentang fitur konsinyasi. Jangan refactor atau "membenarkan" kode di luar scope ini.
4. **Semua perubahan stok fisik dan uang WAJIB lewat fungsi/trigger yang didefinisikan di §2**, jangan pernah bikin kode aplikasi yang langsung `UPDATE item.stok_fisik_saat_ini` atau `UPDATE pelanggan.total_piutang_berjalan` tanpa lewat fungsi resmi. Ini supaya jejak audit di `riwayat_stok` dan `arus_kas` selalu konsisten.
5. **Pola yang sudah ada di sistem ini WAJIB diikuti**, jangan bikin pola baru: fungsi PL/pgSQL pakai `SECURITY DEFINER`, transaksi stok selalu insert ke `riwayat_stok`, transaksi kas selalu insert ke `arus_kas` + update `akun_kas.saldo_saat_ini`.
6. **Semua teks yang tampil ke user (label, pesan error, tombol) pakai Bahasa Indonesia** — sesuai konteks bisnis (Indonesia, toko-toko lokal). Nama variabel/fungsi tetap ikut konvensi Indonesia snake_case yang sudah ada di database.
7. **Kalau membangun 1 layar UI, ikuti spek di §4 apa adanya** — jangan menambah field, tombol, atau langkah tambahan yang tidak diminta, dan jangan menghilangkan validasi/kondisi khusus yang disebutkan (kondisi kosong/error/loading itu WAJIB, bukan opsional).
8. **Urutan pengerjaan ikuti checklist di §6** — bug fix di §6 poin pertama itu prioritas mutlak sebelum kerjain yang lain, karena itu bug aktif yang bikin transaksi gagal total di skenario nyata.

---

## 1. Latar Belakang & Alur Bisnis Nyata

Perusahaan (KEREN SNACK) menitipkan produk ke toko-toko (konsinyasi). Alur nyata di lapangan:

1. **Toko baru / Restock** → Sales/Admin membuat draf pengiriman tanpa tagihan (`adalah_tagihan = false`, valuasi HPP internal), disetujui Owner (satu pintu), dikirim, dan saat dikonfirmasi sampai oleh sales (`selesai_diterima`), stok gudang berpindah ke rak titipan toko.
2. **Kunjungan & Opname Berkala** → Sales datang ke toko, menghitung:
   - Berapa yang masih ada di rak (`sisa_fisik_di_rak`).
   - Berapa barang bagus yang ditarik balik ke gudang (`retur_bagus`, jika ada).
   - Berapa barang rusak/bocor yang ditarik (`retur_rusak`).
   - Sistem menghitung barang laku: $\text{Laku} = \text{Stok Titip Awal} - (\text{Sisa Fisik} + \text{Retur Bagus} + \text{Retur Rusak})$.
3. **Sisa barang bagus (biasanya sedikit) TIDAK ditarik balik ke gudang** — tetap di rak toko, menyatu dengan kiriman baru berikutnya.
4. **Penagihan Murni dari Barang Laku** → Faktur/Nota tagihan (`adalah_tagihan = true`) otomatis terbit **hanya jika ada barang yang laku terjual** ($\text{Laku} > 0$).
5. **Barang Rusak Masuk Kerugian Resmi** → Diakui sebagai beban kerugian perusahaan berdasarkan HPP saat itu ($\text{Retur Rusak} \times \text{HPP snapshot}$) dan dicatat di `riwayat_stok` via `konsinyasi_retur_rusak`.

Prinsip inti: **barang yang dititip masih milik perusahaan sampai benar-benar laku.** Tagihan ke toko dihitung murni dari barang yang laku, bukan dari semua barang yang dititip.

---

## 2. Perubahan & Penambahan Database

> **Catatan Status Live DB:** Kolom `pesanan.akun_kas_id`, `pesanan.total_dibayar`, `pesanan.sisa_tagihan`, serta kolom `rincian_kunjungan_konsinyasi.retur_bagus` dan `selisih_qty` **sudah ada di Live Database Supabase**. Berikut adalah delta migrasi dan fungsi penyempurnaan yang wajib dieksekusi:

### 2.1 🐞 Fix bug CHECK constraint `riwayat_stok` — **PRIORITAS TERTINGGI**

Bug aktif: fungsi `fn_proses_kunjungan_konsinyasi` insert `tipe_mutasi = 'konsinyasi_retur_rusak'` ke `riwayat_stok`, tapi constraint yang ada di live DB tidak mengizinkan nilai ini → **transaksi rollback total** setiap kali ada barang rusak di kunjungan.

```sql
ALTER TABLE public.riwayat_stok DROP CONSTRAINT IF EXISTS riwayat_stok_tipe_mutasi_check;

ALTER TABLE public.riwayat_stok ADD CONSTRAINT riwayat_stok_tipe_mutasi_check
CHECK (((tipe_mutasi)::text = ANY ((ARRAY[
    'produksi_masuk','bahan_terpakai_produksi','penjualan_keluar','pembelian_masuk',
    'penyesuaian_opname_tambah','penyesuaian_opname_kurang','retur_pelanggan_masuk',
    'konsinyasi_keluar','konsinyasi_retur_masuk','konsinyasi_retur_rusak'
])::text[])));
```

### 2.2 Tambah kolom `pelanggan.sales_driver_id` (relasi sales ↔ toko tetap)

```sql
ALTER TABLE public.pelanggan
    ADD COLUMN IF NOT EXISTS sales_driver_id uuid REFERENCES public.karyawan(id);

CREATE INDEX IF NOT EXISTS idx_pelanggan_sales_driver ON public.pelanggan(sales_driver_id);
```

Ini kolom "sales pemegang toko" — dipakai untuk (a) filter daftar toko di UI sales, dan (b) basis komisi (lihat §2.10 — **basis komisi final: berdasarkan kolom ini, bukan siapa yang eksekusi kunjungan**).

### 2.3 Tambah kolom `pesanan.adalah_tagihan` (pembeda dokumen kirim vs tagihan asli)

```sql
ALTER TABLE public.pesanan
    ADD COLUMN IF NOT EXISTS adalah_tagihan boolean DEFAULT true NOT NULL;

COMMENT ON COLUMN public.pesanan.adalah_tagihan IS
    'true = nota tagihan asli (piutang riil, hasil settlement kunjungan konsinyasi). false = dokumen pengiriman/titip barang konsinyasi, dicatat nilainya (HPP, internal) tapi TIDAK masuk piutang.';
```

Aturan pemakaian:
- `pesanan` hasil `fn_proses_kunjungan_konsinyasi` (settlement) → **tetap `true`** (default).
- `pesanan` untuk kirim/titip barang (toko baru atau restock, §2.8) → aplikasi **WAJIB set `adalah_tagihan = false`** secara eksplisit saat insert.

### 2.4 Tambah nilai status `'sebagian'` di `pesanan_status_pembayaran_check`

```sql
ALTER TABLE public.pesanan DROP CONSTRAINT IF EXISTS pesanan_status_pembayaran_check;

ALTER TABLE public.pesanan ADD CONSTRAINT pesanan_status_pembayaran_check
CHECK (((status_pembayaran)::text = ANY ((ARRAY[
    'belum_lunas','sebagian','tempo','lunas','dibatalkan'
])::text[])));
```

### 2.5 Tambah status penolakan pada constraint `surat_jalan`

Agar penolakan draft oleh Owner di C2 memiliki status valid yang konsisten di database:

```sql
ALTER TABLE public.surat_jalan DROP CONSTRAINT IF EXISTS surat_jalan_status_surat_jalan_check;

ALTER TABLE public.surat_jalan ADD CONSTRAINT surat_jalan_status_surat_jalan_check
CHECK (((status_surat_jalan)::text = ANY ((ARRAY[
    'draf_n8n','disetujui_owner','sedang_dikirim','selesai_diterima','gagal_kembali','ditolak_owner'
])::text[])));
```

### 2.6 Tambah kolom kerugian barang rusak di `rincian_kunjungan_konsinyasi`

```sql
ALTER TABLE public.rincian_kunjungan_konsinyasi
    ADD COLUMN IF NOT EXISTS harga_pokok_satuan numeric(15,2) DEFAULT 0.00 NOT NULL,
    ADD COLUMN IF NOT EXISTS nilai_kerugian_rusak numeric(15,2) DEFAULT 0.00 NOT NULL;
```

`harga_pokok_satuan` = snapshot `item.harga_pokok_pembelian` pada saat kunjungan. `nilai_kerugian_rusak` = `retur_rusak * harga_pokok_satuan`.

### 2.7 Update fungsi `fn_proses_kunjungan_konsinyasi` (Definisi Lengkap & Final)

Fungsi ini dieksekusi saat sales menyelesaikan opname rak toko di layar A2:

```sql
CREATE OR REPLACE FUNCTION public.fn_proses_kunjungan_konsinyasi(
    p_pelanggan_id UUID,
    p_sales_driver_id UUID,
    p_rincian JSONB, -- Array of {item_id, sisa_fisik_di_rak, retur_bagus, retur_rusak, jumlah_laku, selisih_qty}
    p_pengguna_id UUID DEFAULT NULL
)
RETURNS JSONB AS $$
DECLARE
    v_kunjungan_id UUID;
    v_nomor_kunjungan VARCHAR(100);
    v_pesanan_id UUID := NULL;
    v_nomor_nota VARCHAR(100) := NULL;
    v_total_laku_netto NUMERIC(15, 2) := 0.00;
    r_item RECORD;
    v_stok_titip_lama INT;
    v_laku INT;
    v_subtotal NUMERIC(15, 2);
    v_harga_info JSONB;
    v_harga_deal NUMERIC(15, 2);
    v_stok_rak_baru INT;
    v_stok_gudang_lama INT;
    v_stok_gudang_baru INT;
    v_pengguna_id UUID := p_pengguna_id;
    v_driver_id UUID := p_sales_driver_id;
    v_selisih INT := 0;
    v_harga_pokok NUMERIC(15, 2) := 0.00;
    v_nilai_kerugian NUMERIC(15, 2) := 0.00;
BEGIN
    v_nomor_kunjungan := 'KONSIN-' || TO_CHAR(NOW(), 'YYYYMMDD-HH24MISS');

    -- Pastikan user_id valid untuk audit trail
    IF v_pengguna_id IS NULL THEN
        SELECT id INTO v_pengguna_id FROM public.pengguna WHERE karyawan_id = p_sales_driver_id LIMIT 1;
        IF v_pengguna_id IS NULL THEN
            SELECT id INTO v_pengguna_id FROM public.pengguna WHERE status_aktif = TRUE ORDER BY dibuat_pada ASC LIMIT 1;
        END IF;
    END IF;

    -- Validasi sales_driver_id
    IF v_driver_id IS NOT NULL THEN
        IF NOT EXISTS (SELECT 1 FROM public.karyawan WHERE id = v_driver_id) THEN
            SELECT karyawan_id INTO v_driver_id FROM public.pengguna WHERE id = v_pengguna_id;
            IF v_driver_id IS NULL THEN
                SELECT id INTO v_driver_id FROM public.karyawan WHERE status_aktif = TRUE ORDER BY nama_karyawan ASC LIMIT 1;
            END IF;
        END IF;
    ELSE
        SELECT karyawan_id INTO v_driver_id FROM public.pengguna WHERE id = v_pengguna_id;
        IF v_driver_id IS NULL THEN
            SELECT id INTO v_driver_id FROM public.karyawan WHERE status_aktif = TRUE ORDER BY nama_karyawan ASC LIMIT 1;
        END IF;
    END IF;

    -- 1. Buat Header Kunjungan
    INSERT INTO public.kunjungan_konsinyasi (
        nomor_kunjungan, pelanggan_id, sales_driver_id, tanggal_kunjungan, total_laku_nominal, dibuat_oleh
    ) VALUES (
        v_nomor_kunjungan, p_pelanggan_id, v_driver_id, CURRENT_DATE, 0.00, v_pengguna_id
    ) RETURNING id INTO v_kunjungan_id;

    -- 2. Loop Rincian Barang
    FOR r_item IN 
        SELECT 
            (elem->>'item_id')::UUID AS item_id,
            COALESCE((elem->>'sisa_fisik_di_rak')::INT, 0) AS sisa_fisik_di_rak,
            COALESCE((elem->>'retur_bagus')::INT, 0) AS retur_bagus,
            COALESCE((elem->>'retur_rusak')::INT, 0) AS retur_rusak,
            COALESCE((elem->>'jumlah_laku')::INT, NULL) AS jumlah_laku_param,
            COALESCE((elem->>'selisih_qty')::INT, 0) AS selisih_qty
        FROM jsonb_array_elements(p_rincian) AS elem
    LOOP
        -- Ambil stok titip lama di toko ini
        SELECT COALESCE(stok_titip_saat_ini, 0) INTO v_stok_titip_lama
        FROM public.stok_konsinyasi_toko
        WHERE pelanggan_id = p_pelanggan_id AND item_id = r_item.item_id;

        IF v_stok_titip_lama IS NULL THEN
            v_stok_titip_lama := 0;
        END IF;

        -- Ambil snapshot HPP terkini untuk valuasi kerugian barang rusak
        SELECT COALESCE(harga_pokok_pembelian, 0.00) INTO v_harga_pokok
        FROM public.item WHERE id = r_item.item_id;

        -- Reset nilai kerugian rusak per item
        v_nilai_kerugian := 0.00;

        -- A. Jika ada retur bagus (ditarik balik ke gudang), kembalikan ke stok fisik gudang pusat
        IF r_item.retur_bagus > 0 THEN
            SELECT COALESCE(stok_fisik_saat_ini, 0) INTO v_stok_gudang_lama FROM public.item WHERE id = r_item.item_id;
            v_stok_gudang_baru := v_stok_gudang_lama + r_item.retur_bagus;

            UPDATE public.item 
            SET stok_fisik_saat_ini = v_stok_gudang_baru, diubah_pada = NOW()
            WHERE id = r_item.item_id;

            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
            ) VALUES (
                r_item.item_id, 'konsinyasi_retur_masuk', r_item.retur_bagus,
                v_stok_gudang_lama, v_stok_gudang_baru, 'kunjungan_konsinyasi', v_kunjungan_id,
                'Retur barang bagus dari rak konsinyasi kembali ke stok siap jual', v_pengguna_id, NOW()
            );
        END IF;

        -- B. Jika ada retur rusak, catat ke riwayat stok dan hitung kerugian HPP
        IF r_item.retur_rusak > 0 THEN
            v_nilai_kerugian := r_item.retur_rusak * v_harga_pokok;

            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_oleh, dibuat_pada
            ) VALUES (
                r_item.item_id, 'konsinyasi_retur_rusak', r_item.retur_rusak,
                0, 0, 'kunjungan_konsinyasi', v_kunjungan_id,
                'Retur barang rusak/BS ditarik dari rak konsinyasi toko', v_pengguna_id, NOW()
            );
        END IF;

        -- C. Hitung Qty Laku Terjual
        IF r_item.jumlah_laku_param IS NOT NULL THEN
            v_laku := GREATEST(0, r_item.jumlah_laku_param);
        ELSE
            v_laku := GREATEST(0, v_stok_titip_lama - (r_item.sisa_fisik_di_rak + r_item.retur_bagus + r_item.retur_rusak));
        END IF;

        v_stok_rak_baru := GREATEST(0, r_item.sisa_fisik_di_rak);
        v_selisih := r_item.selisih_qty;

        -- D. Hitung harga satuan deal toko
        SELECT public.fn_hitung_harga_jual_item(r_item.item_id, p_pelanggan_id) INTO v_harga_info;
        v_harga_deal := COALESCE((v_harga_info->>'harga_pcs_netto')::NUMERIC(15,2), 0.00);

        IF v_harga_deal = 0.00 THEN
            SELECT COALESCE(gphl.harga_jual_pcs, 15000.00) INTO v_harga_deal
            FROM public.item it
            LEFT JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = it.grup_id AND gphl.level_harga = 1
            WHERE it.id = r_item.item_id;
        END IF;

        v_subtotal := v_laku * v_harga_deal;
        v_total_laku_netto := v_total_laku_netto + v_subtotal;

        -- E. Perbarui saldo stok di rak konsinyasi toko = sisa fisik aktual
        INSERT INTO public.stok_konsinyasi_toko (
            pelanggan_id, item_id, stok_titip_saat_ini, terakhir_opname_pada, dibuat_pada, diubah_pada
        ) VALUES (
            p_pelanggan_id, r_item.item_id, v_stok_rak_baru, NOW(), NOW(), NOW()
        )
        ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
            stok_titip_saat_ini = EXCLUDED.stok_titip_saat_ini,
            terakhir_opname_pada = NOW(),
            diubah_pada = NOW();

        -- F. Catat Rincian Kunjungan beserta valuasi kerugian rusak
        INSERT INTO public.rincian_kunjungan_konsinyasi (
            kunjungan_id, item_id, stok_titip_awal, tambah_titip_baru,
            sisa_fisik_di_rak, retur_bagus, retur_rusak, jumlah_laku_terjual,
            selisih_qty, harga_satuan_deal, subtotal_laku,
            harga_pokok_satuan, nilai_kerugian_rusak, dibuat_pada
        ) VALUES (
            v_kunjungan_id, r_item.item_id, v_stok_titip_lama, 0,
            v_stok_rak_baru, r_item.retur_bagus, r_item.retur_rusak, v_laku,
            v_selisih, v_harga_deal, v_subtotal,
            v_harga_pokok, v_nilai_kerugian, NOW()
        );
    END LOOP;

    -- 3. Update total laku di kunjungan konsinyasi
    UPDATE public.kunjungan_konsinyasi 
    SET total_laku_nominal = v_total_laku_netto
    WHERE id = v_kunjungan_id;

    -- 4. Jika ada barang laku > 0, generate Faktur Penjualan Konsinyasi Otomatis (adalah_tagihan = true)
    IF v_total_laku_netto > 0 THEN
        v_nomor_nota := 'INV-KONSIN-' || TO_CHAR(NOW(), 'YYYYMMDD-HH24MISS');

        INSERT INTO public.pesanan (
            nomor_nota, pelanggan_id, sales_driver_id,
            tanggal_pesanan, total_bruto, total_diskon, total_netto,
            tipe_pembayaran, status_pembayaran, status_pemrosesan,
            total_dibayar, sisa_tagihan, adalah_tagihan, catatan,
            dibuat_oleh, dibuat_pada, diubah_pada
        ) VALUES (
            v_nomor_nota, p_pelanggan_id, v_driver_id,
            CURRENT_DATE, v_total_laku_netto, 0.00, v_total_laku_netto,
            'konsinyasi', 'belum_lunas', 'selesai',
            0.00, v_total_laku_netto, TRUE, 'Hasil Kunjungan & Opname Rak Konsinyasi: ' || v_nomor_kunjungan,
            v_pengguna_id, NOW(), NOW()
        ) RETURNING id INTO v_pesanan_id;

        -- Insert detail item pesanan laku
        INSERT INTO public.item_pesanan (
            pesanan_id, item_id,
            kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
            harga_satuan_deal, diskon_item_persen, diskon_item_nominal,
            is_bonus, subtotal, dibuat_pada
        )
        SELECT 
            v_pesanan_id, rkk.item_id,
            rkk.jumlah_laku_terjual, 0,
            rkk.harga_satuan_deal, 0.00, 0.00,
            FALSE, rkk.subtotal_laku, NOW()
        FROM public.rincian_kunjungan_konsinyasi rkk
        WHERE rkk.kunjungan_id = v_kunjungan_id AND rkk.jumlah_laku_terjual > 0;

        -- Link pesanan ke kunjungan konsinyasi
        UPDATE public.kunjungan_konsinyasi 
        SET pesanan_id = v_pesanan_id 
        WHERE id = v_kunjungan_id;

        -- Update total piutang berjalan di master pelanggan
        UPDATE public.pelanggan 
        SET total_piutang_berjalan = COALESCE(total_piutang_berjalan, 0) + v_total_laku_netto,
            diubah_pada = NOW()
        WHERE id = p_pelanggan_id;
    END IF;

    RETURN jsonb_build_object(
        'success', true,
        'kunjungan_id', v_kunjungan_id,
        'nomor_kunjungan', v_nomor_kunjungan,
        'total_laku_netto', v_total_laku_netto,
        'pesanan_id', v_pesanan_id,
        'nomor_nota', v_nomor_nota
    );
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;
```

### 2.8 Fungsi baru: `fn_catat_pembayaran_konsinyasi`

Fungsi ini mencatat pembayaran piutang konsinyasi dengan verifikasi kas dan mutasi `arus_kas`:

```sql
CREATE OR REPLACE FUNCTION public.fn_catat_pembayaran_konsinyasi(
    p_pesanan_id uuid,
    p_akun_kas_id uuid,
    p_nominal_bayar numeric,
    p_dicatat_oleh uuid DEFAULT NULL,
    p_keterangan text DEFAULT NULL
) RETURNS jsonb
    LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
    v_pesanan RECORD;
    v_sisa_baru NUMERIC(15,2);
    v_status_baru VARCHAR(30);
    v_saldo_lama NUMERIC(15,2);
    v_saldo_baru NUMERIC(15,2);
BEGIN
    SELECT * INTO v_pesanan FROM public.pesanan WHERE id = p_pesanan_id FOR UPDATE;

    IF v_pesanan IS NULL THEN
        RAISE EXCEPTION 'Pesanan % tidak ditemukan', p_pesanan_id;
    END IF;

    IF v_pesanan.adalah_tagihan = FALSE THEN
        RAISE EXCEPTION 'Pesanan ini adalah dokumen pengiriman/titip, bukan tagihan. Tidak bisa dicatat pembayarannya.';
    END IF;

    IF p_nominal_bayar <= 0 THEN
        RAISE EXCEPTION 'Nominal bayar harus lebih dari 0';
    END IF;

    IF p_nominal_bayar > v_pesanan.sisa_tagihan THEN
        RAISE EXCEPTION 'Nominal bayar (%) melebihi sisa tagihan (%)', p_nominal_bayar, v_pesanan.sisa_tagihan;
    END IF;

    v_sisa_baru := v_pesanan.sisa_tagihan - p_nominal_bayar;
    v_status_baru := CASE WHEN v_sisa_baru <= 0 THEN 'lunas' ELSE 'sebagian' END;

    UPDATE public.pesanan
    SET total_dibayar = COALESCE(total_dibayar, 0) + p_nominal_bayar,
        sisa_tagihan = v_sisa_baru,
        status_pembayaran = v_status_baru,
        akun_kas_id = COALESCE(akun_kas_id, p_akun_kas_id),
        diubah_pada = NOW()
    WHERE id = p_pesanan_id;

    UPDATE public.pelanggan
    SET total_piutang_berjalan = GREATEST(0, COALESCE(total_piutang_berjalan, 0) - p_nominal_bayar),
        diubah_pada = NOW()
    WHERE id = v_pesanan.pelanggan_id;

    SELECT saldo_saat_ini INTO v_saldo_lama FROM public.akun_kas WHERE id = p_akun_kas_id;
    IF v_saldo_lama IS NULL THEN
        RAISE EXCEPTION 'Akun kas % tidak ditemukan/tidak aktif', p_akun_kas_id;
    END IF;
    v_saldo_baru := v_saldo_lama + p_nominal_bayar;

    UPDATE public.akun_kas
    SET saldo_saat_ini = v_saldo_baru, diubah_pada = NOW()
    WHERE id = p_akun_kas_id;

    INSERT INTO public.arus_kas (
        akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan,
        referensi_tabel, referensi_id, saldo_berjalan, dicatat_oleh, dibuat_pada
    ) VALUES (
        p_akun_kas_id, CURRENT_DATE, 'masuk', 'penjualan', p_nominal_bayar,
        COALESCE(p_keterangan, 'Pelunasan Nota Konsinyasi: ' || v_pesanan.nomor_nota),
        'pesanan', p_pesanan_id, v_saldo_baru, p_dicatat_oleh, NOW()
    );

    RETURN jsonb_build_object(
        'success', true,
        'pesanan_id', p_pesanan_id,
        'total_dibayar', v_pesanan.total_dibayar + p_nominal_bayar,
        'sisa_tagihan', v_sisa_baru,
        'status_pembayaran', v_status_baru
    );
END;
$$;
```

### 2.9 Trigger baru: alur kirim titip / restock

**Alur:** Admin/Sales bikin `pesanan` (`tipe_pembayaran='konsinyasi'`, `adalah_tagihan=false`, item pakai harga HPP internal) $\rightarrow$ `surat_jalan` berstatus `draf_n8n` $\rightarrow$ **Owner approve** ke `disetujui_owner` (satu pintu) $\rightarrow$ `sedang_dikirim` $\rightarrow$ Sales konfirmasi sampai $\rightarrow$ status diubah ke `selesai_diterima` $\rightarrow$ trigger di bawah jalan otomatis:

```sql
CREATE OR REPLACE FUNCTION public.fn_trg_proses_pengiriman_konsinyasi()
RETURNS trigger LANGUAGE plpgsql SECURITY DEFINER AS $$
DECLARE
    v_pesanan RECORD;
    r_item RECORD;
    v_stok_gudang_lama INT;
    v_stok_gudang_baru INT;
BEGIN
    IF NEW.status_surat_jalan = 'selesai_diterima'
       AND (OLD.status_surat_jalan IS DISTINCT FROM 'selesai_diterima') THEN

        SELECT * INTO v_pesanan FROM public.pesanan WHERE id = NEW.pesanan_id;

        IF v_pesanan.tipe_pembayaran = 'konsinyasi' AND v_pesanan.adalah_tagihan = FALSE THEN
            FOR r_item IN
                SELECT item_id, kuantitas_satuan_dasar
                FROM public.item_pesanan
                WHERE pesanan_id = NEW.pesanan_id
            LOOP
                SELECT stok_fisik_saat_ini INTO v_stok_gudang_lama
                FROM public.item WHERE id = r_item.item_id;

                -- Validasi stok cukup (safety net)
                IF v_stok_gudang_lama < r_item.kuantitas_satuan_dasar THEN
                    RAISE EXCEPTION 'Stok gudang item % tidak cukup: tersedia %, diminta %',
                        r_item.item_id, v_stok_gudang_lama, r_item.kuantitas_satuan_dasar;
                END IF;

                v_stok_gudang_baru := v_stok_gudang_lama - r_item.kuantitas_satuan_dasar;

                UPDATE public.item
                SET stok_fisik_saat_ini = v_stok_gudang_baru, diubah_pada = NOW()
                WHERE id = r_item.item_id;

                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                    referensi_tabel, referensi_id, keterangan, dibuat_pada
                ) VALUES (
                    r_item.item_id, 'konsinyasi_keluar', r_item.kuantitas_satuan_dasar,
                    v_stok_gudang_lama, v_stok_gudang_baru, 'surat_jalan', NEW.id,
                    'Titip barang konsinyasi ke toko', NOW()
                );

                INSERT INTO public.stok_konsinyasi_toko (
                    pelanggan_id, item_id, stok_titip_saat_ini, dibuat_pada, diubah_pada
                ) VALUES (
                    v_pesanan.pelanggan_id, r_item.item_id, r_item.kuantitas_satuan_dasar, NOW(), NOW()
                )
                ON CONFLICT (pelanggan_id, item_id) DO UPDATE SET
                    stok_titip_saat_ini = stok_konsinyasi_toko.stok_titip_saat_ini + EXCLUDED.stok_titip_saat_ini,
                    diubah_pada = NOW();
            END LOOP;
        END IF;
    END IF;
    RETURN NEW;
END;
$$;

DROP TRIGGER IF EXISTS trg_proses_pengiriman_konsinyasi ON public.surat_jalan;
CREATE TRIGGER trg_proses_pengiriman_konsinyasi
    AFTER UPDATE ON public.surat_jalan
    FOR EACH ROW
    EXECUTE FUNCTION public.fn_trg_proses_pengiriman_konsinyasi();
```

### 2.10 Nilai barang di dokumen pengiriman (bukan tagihan) — pakai HPP, internal only

Saat admin/sales bikin `item_pesanan` untuk `pesanan` yang `adalah_tagihan=false`, `harga_satuan_deal` diisi dari `item.harga_pokok_pembelian` (HPP), **bukan** harga jual. Ini nilai internal untuk laporan "aset titipan di lapangan" ke owner — **TIDAK PERNAH ditampilkan di surat jalan / dokumen yang dilihat toko atau sales.** Dokumen fisik yang dilihat toko/driver cukup berisi nama barang + jumlah, tanpa harga (§4-A4).

### 2.11 Basis komisi sales — FINAL: Opsi A (toko yang di-assign tetap)

```sql
SELECT
    p.sales_driver_id,
    k.nama_karyawan,
    SUM(kk.total_laku_nominal) AS total_omzet,
    SUM(kk.total_laku_nominal) * k.persentase_komisi_sales / 100 AS total_komisi
FROM public.kunjungan_konsinyasi kk
JOIN public.pelanggan p ON p.id = kk.pelanggan_id
JOIN public.karyawan k ON k.id = p.sales_driver_id
WHERE kk.tanggal_kunjungan BETWEEN :periode_awal AND :periode_akhir
GROUP BY p.sales_driver_id, k.nama_karyawan, k.persentase_komisi_sales;
```

Komisi dihitung dari toko yang **di-assign tetap** ke sales (`pelanggan.sales_driver_id`), bukan siapa yang kebetulan eksekusi kunjungan. Direkap per periode (bulanan).

---

## 3. Ruang Lingkup

**In scope:** semua di §2, plus UI/UX di §4.  
**Out of scope (dibahas terpisah nanti):** desain/format cetak nota (PDF, kop surat), fitur rekap komisi bulanan otomatis (§2.11), format surat jalan cetak.

---

## 4. UI/UX — Portal Konsinyasi Terpadu

### 4.0 Prinsip Arsitektur UI (WAJIB DIBACA DULU)

#### 4.0.1 Satu Entry-Point, Satu Sidebar Menu

Fitur konsinyasi **hanya punya 1 menu di sidebar**: **"Konsinyasi"** → `/consignment`.

Tidak ada lagi sub-menu konsinyasi di sidebar (misal: "Laporan Konsinyasi", "Piutang Konsinyasi", "Approval", dst. sebagai item sidebar terpisah). Semua akses masuk lewat 1 entry-point, lalu user memilih fitur dari halaman portal.

#### 4.0.2 Halaman Portal sebagai Homepage Konsinyasi

`/consignment` adalah halaman portal — bukan form, bukan tabel data. Halaman ini menampilkan **grid card navigasi** yang masing-masing merepresentasikan satu fitur/sub-halaman. Klik card → buka halaman baru (full page navigation) di URL `/consignment/<sub>`.

Layout portal:
- Header halaman: judul "Konsinyasi" + breadcrumb sederhana.
- Grid card responsif: **2 kolom di mobile, 3–4 kolom di tablet/desktop**.
- Setiap card: icon + label fitur saja — **tanpa summary angka**, bersih dan tidak overfitting.
- Card yang tidak boleh diakses oleh role aktif: **disembunyikan (hidden)**, bukan di-disable/greyed-out.

#### 4.0.3 Responsive — Bukan Mobile-First Terpisah

Semua layar (portal + sub-halaman) harus **responsive** dengan pendekatan mobile-first CSS:
- Tidak ada halaman khusus "versi mobile" atau route `/consignment/sales` — semua role pakai URL yang sama.
- Tabel di mobile → collapse jadi card list atau horizontal scroll dengan sticky column pertama.
- Form input di mobile → full-width, touch-friendly (min height 44px per input).

#### 4.0.4 Prinsip Lintas Layar

- Setiap aksi yang mengubah stok/uang **wajib ada konfirmasi modal** sebelum submit.
- Angka hasil kalkulasi sistem (laku, komisi, sisa tagihan, kerugian) selalu **read-only**, tidak bisa diedit manual.
- State kosong **selalu ada pesan + CTA** yang jelas — tidak boleh ada halaman putih polos.
- State error **actionable** — sebutkan apa yang salah dan langkah yang bisa dilakukan, bukan cuma "Terjadi kesalahan".
- Istilah dipakai konsisten di semua layar: **"titip"**, **"laku"**, **"retur bagus"**, **"retur rusak"**.
- Semua teks UI dalam **Bahasa Indonesia**.

---

### 4.1 Controller & Routing

**Rekomendasi (terbaik untuk jangka panjang):** Gunakan satu `ConsignmentController.php` yang sudah ada, tambah method baru untuk setiap sub-halaman. Jangan buat controller baru yang pecah-pecah karena ini satu domain fitur.

Struktur method controller:

```
ConsignmentController::portal()            → GET /consignment
ConsignmentController::stokRak()           → GET /consignment/stok-rak
ConsignmentController::opname()            → GET /consignment/opname (atau ?pelanggan_id=...)
ConsignmentController::opnameProses()      → POST /consignment/opname/proses
ConsignmentController::hasilKunjungan()    → GET /consignment/opname/hasil (?kunjungan_id=...)
ConsignmentController::laporanPenjualan()  → GET /consignment/laporan-penjualan
ConsignmentController::piutang()           → GET /consignment/piutang
ConsignmentController::catatPembayaran()   → POST /consignment/piutang/bayar
ConsignmentController::assignmentSales()   → GET /consignment/assignment-sales
ConsignmentController::saveAssignment()    → POST /consignment/assignment-sales/save
ConsignmentController::riwayatKunjungan()  → GET /consignment/riwayat-kunjungan
ConsignmentController::komisiSales()       → GET /consignment/komisi-sales
ConsignmentController::kerugianRusak()     → GET /consignment/kerugian-rusak
ConsignmentController::earlyWarning()      → GET /consignment/early-warning
```

Setiap method yang memerlukan role tertentu **wajib cek permission di awal method** — jika tidak punya akses, redirect ke `/consignment` dengan flash error "Kamu tidak punya akses ke halaman ini."

---

### 4.2 Hak Akses Per Card (Role-Based Visibility)

Card di portal ditampilkan atau disembunyikan berdasarkan role user yang sedang login.

| # | Fitur (Card) | URL Sub-halaman | Sales | Admin | Owner |
|---|---|---|:---:|:---:|:---:|
| 1 | Opname / Kunjungan | `/consignment/opname` | ✅ | ✅ | ✅ |
| 2 | Stok Rak per Toko | `/consignment/stok-rak` | ✅ (filter toko assigned) | ✅ (semua toko) | ✅ (semua toko) |
| 3 | Laporan Penjualan | `/consignment/laporan-penjualan` | ✅ (filter toko assigned) | ✅ (semua) | ✅ (semua) |
| 4 | Piutang Konsinyasi | `/consignment/piutang` | ❌ | ✅ | ✅ (read-only, tanpa tombol bayar) |
| 5 | Assignment Sales ↔ Toko | `/consignment/assignment-sales` | ❌ | ✅ | ✅ |
| 6 | Rekap Komisi Sales | `/consignment/komisi-sales` | ❌ | ✅ | ✅ |
| 7 | Laporan Kerugian Barang Rusak | `/consignment/kerugian-rusak` | ❌ | ✅ | ✅ |
| 8 | Early Warning Toko | `/consignment/early-warning` | ❌ | ✅ | ✅ |
| 9 | Riwayat Kunjungan | `/consignment/riwayat-kunjungan` | ❌ | ✅ | ✅ |

**Catatan penting:**
- Sales hanya lihat 3 card → portal ringkas, fokus kerja lapangan.
- **Approval Pengiriman Konsinyasi berada di `/owner` (Owner Command Center)**, bukan di portal konsinyasi — sesuai dengan `OwnerController::approveConsignmentDelivery` dan `rejectConsignmentDelivery` yang sudah ada.
- Pembuatan pesanan/pengiriman konsinyasi baru dilakukan lewat `/customer-orders/create` (modul terpusat), **bukan** dari portal konsinyasi — tidak ada card "Buat Pengiriman" di sini.
- Card Piutang untuk Owner bersifat **read-only** (tidak ada tombol "Catat Pembayaran") — aksi bayar hanya bisa dilakukan Admin.

---

### 4.3 Halaman Portal (`/consignment`)

**Tampilan:**
- Judul halaman: **"Konsinyasi"** — besar, jelas.
- Subjudul opsional berdasarkan role: misal untuk Sales tampilkan *"Selamat datang, [nama]. Pilih aktivitas kamu:"*
- Grid card 2 kolom (mobile) / 3–4 kolom (tablet/desktop).

**Setiap card berisi:**
- Icon yang relevan (gunakan icon set yang sudah dipakai di sistem).
- Label fitur (contoh: "Opname / Kunjungan", "Stok Rak per Toko").
- Tidak ada angka/statistik — card adalah navigasi murni.

**Contoh urutan card berdasarkan role:**

*Sales:*
1. Opname / Kunjungan
2. Stok Rak per Toko
3. Laporan Penjualan

*Admin:*
1. Opname / Kunjungan
2. Stok Rak per Toko
3. Laporan Penjualan
4. Piutang Konsinyasi
5. Assignment Sales ↔ Toko
6. Rekap Komisi Sales
7. Laporan Kerugian Barang Rusak
8. Riwayat Kunjungan
9. Early Warning Toko

*Owner:*
1. Laporan Penjualan
2. Piutang Konsinyasi (read-only)
3. Rekap Komisi Sales
4. Laporan Kerugian Barang Rusak
5. Stok Rak per Toko
6. Assignment Sales ↔ Toko
7. Riwayat Kunjungan
8. Early Warning Toko

> **Catatan untuk Owner:** Approval Pengiriman Konsinyasi tersedia di halaman **Owner Command Center** (`/owner`) — bukan di portal ini.

---

### 4.4 Sub-halaman: Opname / Kunjungan (`/consignment/opname`)

**Deskripsi:** Halaman utama kerja Sales di lapangan — sales pilih toko, lakukan opname rak, submit → sistem catat kunjungan & terbitkan nota otomatis jika ada barang laku. Menggunakan pola **Master-Detail via URL State** (`/consignment/opname` untuk daftar toko dan `?pelanggan_id={id}` untuk form opname).

**Step 1 — Pilih Toko (`GET /consignment/opname`):**
- Tampilkan daftar toko konsinyasi yang di-assign ke sales yang sedang login.
  - Query: `pelanggan WHERE sales_driver_id = :karyawan_id_login AND is_konsinyasi = true AND status_aktif = true`
  - Admin/Owner yang membuka halaman ini bisa pilih semua toko (tanpa filter sales).
- Tampilan per toko: nama toko, alamat singkat, badge merah jika `terakhir_opname_pada > 14 hari`.
- Indikator visual jika ada kiriman sedang menuju toko tersebut.
- Search box filter nama toko (client-side, instan tanpa reload).
- State kosong → pesan: *"Belum ada toko yang di-assign ke kamu, hubungi admin."*
- Klik toko → navigasi ke `GET /consignment/opname?pelanggan_id={id}` (membuka Form Opname Toko).

**Step 2 — Form Opname Rak (`GET /consignment/opname?pelanggan_id={id}`):**
- **Header:** Tombol navigasi *"← Kembali ke Daftar Toko"*, nama toko, alamat, tombol telepon/WA langsung.
- **Alur Restock Otomatis (Driver Delivery):**
  - Stok rak konsinyasi bertambah secara otomatis begitu driver menyelesaikan serah terima pengiriman (`status_surat_jalan = 'selesai_diterima'`) melalui trigger database `trg_proses_pengiriman_konsinyasi`.
  - Form opname difokuskan murni untuk pengecekan fisik rak toko dan pencatatan hasil kunjungan. Tidak ada tombol konfirmasi barang masuk manual oleh sales.
  - **Catatan:** Form opname **TIDAK memiliki input manual `tambah_titip_baru`**. Semua penambahan titipan wajib melalui alur pengiriman resmi satu pintu yang telah disetujui Owner.
- Jika toko belum pernah ada riwayat titipan (`COUNT(stok_konsinyasi_toko) = 0`) → tampilkan CTA: *"Toko ini belum ada barang titipan. Buat pesanan pengiriman pertama di menu Customer Orders."* (link ke `/customer-orders/create`).
- **Daftar Item:** Semua SKU yang terdaftar di rak toko (termasuk saldo 0 pcs — beri badge *"Habis: 0 pcs"*).
- Per item:
  - Nama item, **"Stok titip sistem: X pcs"** (read-only, acuan)
  - Input: `sisa_fisik_di_rak` (wajib diisi), `retur_bagus` (default 0), `retur_rusak` (default 0)
  - **Helper text WAJIB di bawah field `retur_bagus`:** *"Isi ini HANYA kalau barang dibawa pulang ke gudang. Kalau barang masih di toko (walau sisa sedikit), masukkan ke 'sisa fisik di rak'."*
  - Preview real-time: **"Estimasi laku = X pcs"** — rumus: `GREATEST(0, stok_titip - (sisa + retur_bagus + retur_rusak))`
  - Warning merah jika `sisa + retur_bagus + retur_rusak > stok_titip`.
- Tombol **"Submit Opname"** disabled sampai semua item minimal disentuh field `sisa_fisik_di_rak`.
- Konfirmasi modal sebelum submit: *"Kamu akan mencatat kunjungan ke [Toko]: estimasi laku Rp X dari Y item. Lanjutkan?"*
- Jika submit gagal (koneksi putus) → input tidak hilang (tersimpan di session form recovery), tampilkan tombol *"Coba kirim lagi"*.
- Submit sukses → panggil `fn_proses_kunjungan_konsinyasi` → redirect ke `GET /consignment/opname/hasil?kunjungan_id={id}`.

**Step 3 — Hasil Kunjungan (`GET /consignment/opname/hasil?kunjungan_id={kunjungan_id}`):**
- Tampilkan: nomor kunjungan, tanggal, nama toko, nama sales, total laku (Rp), nomor nota (jika `total_laku_netto > 0`), status pembayaran nota.
- Jika total laku = 0 → tampilkan jelas: *"Tidak ada barang laku pada kunjungan ini."* — bukan halaman kosong.
- Rincian per item: titip awal → laku → retur rusak → sisa baru.
- **Fitur Bukti Kunjungan & Dokumen Lapangan:**
  - **Tombol "Kirim Rekap WhatsApp"** → membuka tautan `https://wa.me/{nomor_wa_toko}?text=...` berisi rekap rapi siap kirim (Nama Toko, Tanggal, No. Nota, Qty Laku per item, Retur Rusak, Total Tagihan, Sisa Tagihan).
  - **Tombol "Cetak Struk / Thermal"** → trigger fungsi browser `window.print()` dengan layout struk kasir/thermal 58mm/80mm & A4 bersih.
- **Navigasi:** Tombol **"Kembali ke Daftar Toko"** → `/consignment/opname` | **"Kembali ke Portal"** → `/consignment`.

---

### 4.5 Sub-halaman: Stok Rak per Toko (`/consignment/stok-rak`)

- Tabel: nama toko, total SKU dititip, total qty dititip, tanggal terakhir opname, badge status.
  - Sales: filter otomatis hanya toko yang di-assign ke dia.
  - Admin/Owner: lihat semua toko.
- Badge status opname: **Hijau** (≤ 7 hari), **Kuning** (8–14 hari), **Merah** (> 14 hari atau belum pernah opname).
- Klik baris toko → drill-down: detail item per rak toko (nama item, stok titip saat ini, HPP saat ini — HPP hanya tampil untuk Admin/Owner, bukan Sales).
- State kosong → pesan: *"Belum ada data stok rak. Buat pengiriman pertama lewat menu Customer Orders."*

---

### 4.6 Sub-halaman: Laporan Penjualan Konsinyasi (`/consignment/laporan-penjualan`)

- Filter: periode (tanggal awal–akhir), toko (dropdown — Sales hanya lihat toko miliknya).
- Tabel hasil: tanggal kunjungan, nama toko, nomor kunjungan, nomor nota, total laku (Rp), status pembayaran nota.
- Aggregasi di bawah tabel: total laku periode, total nota terbit, total sudah dibayar, total piutang.
- State kosong → pesan: *"Belum ada data penjualan konsinyasi untuk periode ini."*
- Klik baris → drawer/halaman detail rincian item per kunjungan.

---

### 4.7 Sub-halaman: Piutang Konsinyasi (`/consignment/piutang`)

- Query: `pesanan WHERE tipe_pembayaran='konsinyasi' AND adalah_tagihan=true AND status_pembayaran IN ('belum_lunas','sebagian')`
- Tabel: nama toko, nomor nota, tanggal tagihan, total tagihan (Rp), sudah dibayar (Rp), sisa tagihan (Rp), status.
  - Badge status: **Merah** = `belum_lunas`, **Kuning** = `sebagian`.
- **Tombol "Catat Pembayaran"** — hanya tampil untuk Admin (Owner lihat read-only tanpa tombol ini):
  - Modal: pilih akun kas (dropdown), input nominal bayar (max = sisa tagihan, validasi client-side), keterangan opsional.
  - Submit → panggil `fn_catat_pembayaran_konsinyasi`.
  - Konfirmasi modal sebelum submit: *"Catat pembayaran Rp X untuk nota [No.Nota] dari [Toko]?"*
- State kosong → pesan: *"Tidak ada piutang konsinyasi aktif saat ini."*

---

### 4.8 Sub-halaman: Assignment Sales ↔ Toko (`/consignment/assignment-sales`)

- Tabel toko konsinyasi: nama toko, sales yang di-assign saat ini, tombol "Ubah Assignment".
- Toko belum punya sales → badge **"Belum ada sales"** (warna kuning/warning).
- Tombol "Ubah Assignment" → modal: dropdown pilih karyawan dengan role sales, simpan → update `pelanggan.sales_driver_id`.
- State kosong → pesan: *"Belum ada toko konsinyasi yang terdaftar."*

---

### 4.9 Sub-halaman: Rekap Komisi Sales (`/consignment/komisi-sales`)

- Filter: periode (bulan/tanggal).
- Tabel: nama sales, total toko yang di-assign, total omzet (Rp), persentase komisi (%), nominal komisi (Rp).
- Query basis: §2.11 — komisi berdasarkan toko yang di-assign tetap (`pelanggan.sales_driver_id`).
- State kosong → pesan: *"Belum ada data komisi untuk periode ini."*

---

### 4.10 Sub-halaman: Laporan Kerugian Barang Rusak (`/consignment/kerugian-rusak`)

- Filter: periode, toko.
- Tabel: tanggal kunjungan, nama toko, nama item, qty rusak, HPP satuan (Rp), nilai kerugian (Rp).
- Aggregasi: total nilai kerugian periode.
- Source data: `SUM(nilai_kerugian_rusak)` dari `rincian_kunjungan_konsinyasi`.
- State kosong → pesan: *"Tidak ada barang rusak yang tercatat pada periode ini."*

---

### 4.11 Sub-halaman: Riwayat Kunjungan per Toko (`/consignment/riwayat-kunjungan`)

- Filter: toko (dropdown), periode.
- Tabel: tanggal kunjungan, nomor kunjungan, nama sales, total laku (Rp), nomor nota.
- Klik baris → drawer/halaman detail rincian item kunjungan (read-only, untuk audit).
- State kosong → pesan: *"Belum ada riwayat kunjungan untuk filter ini."*

---

### 4.12 Sub-halaman: Early Warning Toko (`/consignment/early-warning`)

- Threshold default: 14 hari (hardcode, bisa diubah nanti jika diperlukan).
- Tabel: nama toko, nama sales assigned, terakhir opname, jumlah hari sejak opname terakhir (badge merah), total stok titip saat ini.
- Urutkan: terlama (paling berbahaya) di atas.
- State kosong → pesan: *"Semua toko sudah diopname dalam 14 hari terakhir. 👍"*



---

## 5. Ringkasan Keputusan (Final & Terkonfirmasi)

| # | Keputusan | Final |
|---|---|---|
| 1 | Basis komisi sales | Toko yang di-assign tetap (`pelanggan.sales_driver_id`), direkap per periode/bulanan |
| 2 | Barang rusak | Masuk laporan kerugian resmi, dihitung pakai HPP snapshot |
| 3 | Validasi stok gudang | Sistem nolak otomatis (di trigger), dicek dini di UI form opname |
| 4 | Status pembayaran sebagian | Ditambah status baru `'sebagian'` pada constraint status pembayaran |
| 5 | Nilai di dokumen kirim (non-tagihan) | HPP, internal only (admin/owner) — TIDAK ditampilkan ke sales/toko |
| 6 | Approval pengiriman | Draft dibuat via `/customer-orders/create` → **Owner approve di `/owner` (Owner Command Center)** — bukan di portal konsinyasi |
| 7 | Penolakan kiriman | Status surat jalan menjadi `'ditolak_owner'` dan pesanan menjadi `'dibatalkan'` |
| 8 | Arsitektur sidebar | **1 menu "Konsinyasi"** di sidebar → `/consignment` sebagai portal card navigasi |
| 9 | URL sub-halaman | Semua pakai pola `/consignment/<sub>` — tidak ada route `/consignment/sales` lagi |
| 10 | Hak akses card | Card **hidden** (bukan disabled) jika role tidak punya akses |
| 11 | Tampilan | **Responsive** (mobile-first CSS) — tidak ada halaman mobile terpisah |
| 12 | Buat pengiriman konsinyasi | Terpusat di `/customer-orders/create` — bukan dari portal konsinyasi |
| 13 | Controller | Satu `ConsignmentController.php` — method baru per sub-halaman (maintainable jangka panjang) |
| 14 | Format URL Hasil Opname | `GET /consignment/opname/hasil?kunjungan_id={UUID}` — sinkron dengan arsitektur Router query parameter ERP |
| 15 | Alur Opname Master-Detail | `GET /consignment/opname` (daftar toko) & `GET /consignment/opname?pelanggan_id={UUID}` (form opname toko) |
| 16 | Alur Restock saat Kunjungan | Melalui Banner Konfirmasi Kiriman Masuk interaktif di atas form opname yang mentrigger `trg_proses_pengiriman_konsinyasi` sebelum opname dihitung (tidak ada input manual `tambah_titip_baru`) |
| 17 | Bukti Kunjungan Lapangan | Tombol ringkasan WhatsApp ke toko + tombol browser thermal print (58mm/80mm & A4) di layar hasil kunjungan |

---

## 6. Checklist Implementasi (Urutan Pengerjaan)

### Phase 1 — Database (PRIORITAS MUTLAK, KERJAIN PERTAMA)

- [ ] **1. Fix CHECK constraint `riwayat_stok`** (§2.1) — bug aktif yang bikin transaksi rollback total saat ada retur rusak.
- [ ] 2. Tambah kolom `pelanggan.sales_driver_id` (§2.2).
- [ ] 3. Tambah kolom `pesanan.adalah_tagihan` (§2.3).
- [ ] 4. Update constraint `pesanan_status_pembayaran_check` tambah `'sebagian'` (§2.4).
- [ ] 5. Update constraint `surat_jalan_status_surat_jalan_check` tambah `'ditolak_owner'` (§2.5).
- [ ] 6. Tambah kolom `harga_pokok_satuan` & `nilai_kerugian_rusak` di `rincian_kunjungan_konsinyasi` (§2.6).
- [ ] 7. Update fungsi `fn_proses_kunjungan_konsinyasi` lengkap dengan valuasi kerugian rusak (§2.7).
- [ ] 8. Buat fungsi `fn_catat_pembayaran_konsinyasi` (§2.8).
- [ ] 9. Buat & pasang trigger `trg_proses_pengiriman_konsinyasi` pada `surat_jalan` (§2.9).

### Phase 2 — Routing & Controller

- [ ] 10. Tambah semua method baru di `ConsignmentController.php` sesuai mapping §4.1.
- [ ] 11. Daftarkan semua route `/consignment/*` di file routing aplikasi.
- [ ] 12. Implementasi middleware/cek permission di setiap method controller (role-based access).
- [ ] 13. Hapus/nonaktifkan route `/consignment/sales` yang lama (redirect ke `/consignment` jika masih ada link yang tersisa).

### Phase 3 — UI Portal & Sub-halaman

- [ ] 14. Bangun halaman portal `/consignment` — grid card responsif, card hidden berdasarkan role (§4.3).
- [ ] 15. Bangun sub-halaman Opname/Kunjungan `/consignment/opname` — Step 1 (pilih toko) + Step 2 (form opname) + Step 3 (hasil kunjungan) (§4.4).
- [ ] 16. Bangun sub-halaman Stok Rak per Toko `/consignment/stok-rak` (§4.5).
- [ ] 17. Bangun sub-halaman Laporan Penjualan `/consignment/laporan-penjualan` (§4.6).
- [ ] 18. Bangun sub-halaman Piutang Konsinyasi `/consignment/piutang` — termasuk modal catat pembayaran untuk Admin (§4.7).
- [ ] 19. Bangun sub-halaman Assignment Sales ↔ Toko `/consignment/assignment-sales` (§4.8).
- [ ] 20. Bangun sub-halaman Rekap Komisi Sales `/consignment/komisi-sales` (§4.9).
- [ ] 21. Bangun sub-halaman Laporan Kerugian Barang Rusak `/consignment/kerugian-rusak` (§4.10).
- [ ] 22. Bangun sub-halaman Riwayat Kunjungan `/consignment/riwayat-kunjungan` (§4.11).
- [ ] 23. Bangun sub-halaman Early Warning Toko `/consignment/early-warning` (§4.12).
- [ ] 24. Pastikan sidebar hanya menampilkan **1 menu "Konsinyasi"** — hapus semua sub-item konsinyasi lain dari sidebar.

### Phase 4 — Testing End-to-End

- [ ] 25. Test skenario lengkap: toko baru → buat pesanan kirim via `/customer-orders/create` → owner approve di `/owner` (Owner Command Center) → konfirmasi terima (stok masuk rak) → opname di `/consignment/opname` (ada retur rusak) → nota otomatis terbit → bayar sebagian di `/consignment/piutang` → bayar lunas → stok rak terupdate benar.
- [ ] 26. Test role visibility: login sebagai Sales → cek hanya 3 card muncul di portal; login sebagai Admin → 9 card muncul; login sebagai Owner → 8 card muncul (approval ada di `/owner`, bukan di sini).
- [ ] 27. Test responsive: buka semua sub-halaman di mobile (320px) dan desktop (1440px) — tidak ada elemen yang overflow atau terpotong.


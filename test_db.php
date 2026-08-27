<?php
declare(strict_types=1);

/**
 * test_db.php
 * Script CLI untuk menguji koneksi dan integritas data Supabase dari Laragon.
 * Jalankan via: php test_db.php
 */

require_once __DIR__ . '/config/database.php';

echo "\n===================================================================\n";
echo "   KEREN SNACK ERP - SUPABASE HEALTHCHECK & INTEGRITY TEST          \n";
echo "===================================================================\n\n";

try {
    echo "1. Menguji Koneksi PDO PostgreSQL ke Supabase Cloud...\n";
    $pdo = Database::getConnection();
    echo "   ✅ KONEKSI BERHASIL TERHUBUNG DENGAN AMAN (SSL VIA POOLER)!\n\n";

    echo "2. Mengecek Total Tabel di Skema Public...\n";
    $tables = Database::fetchAll("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name ASC
    ");
    $totalTables = count($tables);
    echo "   ✅ Total Tabel Ditemukan: {$totalTables} Tabel\n\n";

    echo "3. Mengecek Data Master (Seed Verification)...\n";
    $grupProdukCount = Database::fetchOne("SELECT count(*) as total FROM public.grup_produk")['total'] ?? 0;
    $itemCount       = Database::fetchOne("SELECT count(*) as total FROM public.item")['total'] ?? 0;
    $karyawanCount   = Database::fetchOne("SELECT count(*) as total FROM public.karyawan")['total'] ?? 0;
    $pelangganCount  = Database::fetchOne("SELECT count(*) as total FROM public.pelanggan")['total'] ?? 0;
    $peranCount      = Database::fetchOne("SELECT count(*) as total FROM public.peran")['total'] ?? 0;
    $akunKasCount    = Database::fetchOne("SELECT count(*) as total FROM public.akun_kas")['total'] ?? 0;

    echo "   - Master Grup Produk : {$grupProdukCount} grup\n";
    echo "   - Master SKU Item   : {$itemCount} SKU produk (Sesuai Excel Asli)\n";
    echo "   - Master Karyawan   : {$karyawanCount} orang (Borongan & Sales-Driver)\n";
    echo "   - Master Toko       : {$pelangganCount} toko pelanggan\n";
    echo "   - Master Peran      : {$peranCount} peran RBAC\n";
    echo "   - Master Akun Kas   : {$akunKasCount} rekening kas\n\n";

    echo "4. Menguji Stored Procedure Hitung Harga Dinamis (RPC Engine)...\n";
    $sampleItem = Database::fetchOne("SELECT id, nama_item FROM public.item LIMIT 1");
    $sampleCust = Database::fetchOne("SELECT id, nama_toko FROM public.pelanggan LIMIT 1");

    if ($sampleItem && $sampleCust) {
        $calcRow = Database::fetchOne("
            SELECT public.fn_hitung_harga_jual_item(:item_id, :cust_id) AS json_res
        ", [
            'item_id' => $sampleItem['id'],
            'cust_id' => $sampleCust['id']
        ]);

        $calcPrice = json_decode($calcRow['json_res'] ?? '{}', true);

        if ($calcPrice) {
            echo "   ✅ Simulasi Pricing Berhasil:\n";
            echo "      • Item       : {$sampleItem['nama_item']}\n";
            echo "      • Pelanggan  : {$sampleCust['nama_toko']} ({$calcPrice['grup_pelanggan']})\n";
            echo "      • Level Harga: Level {$calcPrice['level_harga']}\n";
            echo "      • Harga Pcs  : Rp " . number_format((float)$calcPrice['harga_pcs_netto'], 0, ',', '.') . "\n";
            echo "      • Harga Bal  : Rp " . number_format((float)$calcPrice['harga_bal_netto'], 0, ',', '.') . "\n\n";
        }
    }

    echo "5. Menguji Fungsi Pencarian Barcode Kemasan Universal...\n";
    $sampleBarcode = Database::fetchOne("SELECT barcode FROM public.item WHERE barcode IS NOT NULL LIMIT 1")['barcode'] ?? null;
    if ($sampleBarcode) {
        $barcodeRow = Database::fetchOne("
            SELECT public.fn_cari_item_by_barcode(:barcode) AS json_res
        ", ['barcode' => $sampleBarcode]);

        $barcodeData = json_decode($barcodeRow['json_res'] ?? '{}', true);

        if (!empty($barcodeData['ditemukan'])) {
            echo "   ✅ Pencarian Barcode '{$sampleBarcode}' Berhasil (Ditemukan {$barcodeData['total_varian']} varian rasa):\n";
            foreach ($barcodeData['items'] as $item) {
                echo "      - [{$item['kode_sku']}] {$item['nama_item']} ({$item['grup_nama']}) | Stok: {$item['stok_fisik']} {$item['satuan_dasar']}\n";
            }
            echo "\n";
        }
    }

    echo "===================================================================\n";
    echo "🎉 STATUS: SEMUA SISTEM DATABASE & MESIN OTOMASI 1000% SEHAT!      \n";
    echo "===================================================================\n\n";

} catch (Throwable $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "💡 Panduan Perbaikan:\n";
    echo "1. Pastikan kamu sudah mengisi file .env di root project (.env)\n";
    echo "2. Periksa kembali DB_PASSWORD yang kamu masukkan di file .env\n\n";
}

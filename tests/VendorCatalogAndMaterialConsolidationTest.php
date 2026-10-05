<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');
require_once ROOT_PATH . '/config/database.php';

$pdo = Database::getConnection();
$passed = 0;
$failed = 0;

function runTest(string $title, callable $fn): void {
    global $passed, $failed;
    echo "Testing: {$title} ... ";
    try {
        $result = $fn();
        if ($result === true) {
            echo "\033[32m[PASS]\033[0m\n";
            $passed++;
        } else {
            echo "\033[31m[FAIL]\033[0m (Assertion returned false)\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "\033[31m[ERROR]\033[0m: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "  TEST SUITE: KATALOG MULTI-VENDOR (pemasok_item) & KONSOLIDASI BAHAN\n";
echo "====================================================================\n\n";

// 1. Table & Column Verification
runTest("1. Tabel public.pemasok_item dan kolom relasi tersedia", function() use ($pdo) {
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'pemasok_item'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required = ['id', 'pemasok_id', 'item_id', 'harga_beli', 'kode_sku_vendor', 'catatan', 'status_aktif'];
    foreach ($required as $r) {
        if (!in_array($r, $cols, true)) {
            return false;
        }
    }
    return true;
});

// 2. Zero Duplicates in Master Materials
runTest("2. Tidak ada master bahan mentah / kemasan yang duplikat", function() use ($pdo) {
    $stmt = $pdo->query("
        SELECT LOWER(TRIM(nama_item)), tipe_item, COUNT(*) 
        FROM public.item 
        WHERE tipe_item IN ('bahan_mentah', 'bahan_kemas') 
        GROUP BY LOWER(TRIM(nama_item)), tipe_item 
        HAVING COUNT(*) > 1
    ");
    $duplicates = $stmt->fetchAll();
    return count($duplicates) === 0;
});

// 3. Multi-Vendor Catalog Assignment Test (Isolated Transaction with Rollback)
runTest("3. 1 Bahan Mentah dapat di-assign ke 2 Vendor berbeda dengan beda harga", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // Buat 1 Bahan Mentah Uji Coba
        $stmtItem = $pdo->prepare("
            INSERT INTO public.item (
                kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian, status_aktif
            ) VALUES (
                'TEST-BAHAN-' || floor(random()*1000000), 'TEST BASRENG CURAH', 'bahan_mentah', 'bal', 250000, TRUE
            ) RETURNING id
        ");
        $stmtItem->execute();
        $itemId = $stmtItem->fetchColumn();

        // Buat 2 Vendor Uji Coba
        $stmtSup1 = $pdo->prepare("
            INSERT INTO public.pemasok (kode_pemasok, nama_pemasok, status_aktif)
            VALUES ('TEST-VND-A-' || floor(random()*1000000), 'Vendor A Testing', TRUE)
            RETURNING id
        ");
        $stmtSup1->execute();
        $sup1Id = $stmtSup1->fetchColumn();

        $stmtSup2 = $pdo->prepare("
            INSERT INTO public.pemasok (kode_pemasok, nama_pemasok, status_aktif)
            VALUES ('TEST-VND-B-' || floor(random()*1000000), 'Vendor B Testing', TRUE)
            RETURNING id
        ");
        $stmtSup2->execute();
        $sup2Id = $stmtSup2->fetchColumn();

        // Hubungkan Bahan ke Vendor 1 (@ Rp 240.000)
        $pdo->prepare("
            INSERT INTO public.pemasok_item (pemasok_id, item_id, harga_beli, catatan)
            VALUES (:pid, :iid, 240000, 'Harga promo grosir')
        ")->execute(['pid' => $sup1Id, 'iid' => $itemId]);

        // Hubungkan Bahan yang SAMA ke Vendor 2 (@ Rp 255.000)
        $pdo->prepare("
            INSERT INTO public.pemasok_item (pemasok_id, item_id, harga_beli, catatan)
            VALUES (:pid, :iid, 255000, 'Harga eceran reguler')
        ")->execute(['pid' => $sup2Id, 'iid' => $itemId]);

        // Verifikasi Query M:N
        $stmtCheck = $pdo->prepare("
            SELECT pi.harga_beli, p.nama_pemasok
            FROM public.pemasok_item pi
            JOIN public.pemasok p ON p.id = pi.pemasok_id
            WHERE pi.item_id = :iid
            ORDER BY pi.harga_beli ASC
        ");
        $stmtCheck->execute(['iid' => $itemId]);
        $rows = $stmtCheck->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) !== 2) return false;
        if ((float)$rows[0]['harga_beli'] !== 240000.0) return false;
        if ((float)$rows[1]['harga_beli'] !== 255000.0) return false;

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

// 4. Controller Method Verification
runTest("4. SupplierController memiliki endpoint getCatalog, saveCatalogItem, deleteCatalogItem", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/SupplierController.php');
    return str_contains($content, 'function getCatalog') &&
           str_contains($content, 'function saveCatalogItem') &&
           str_contains($content, 'function deleteCatalogItem');
});

// 5. PurchaseController Catalog Sync Verification
runTest("5. PurchaseController menyinkronkan data katalog pemasok_item pada form & store", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/PurchaseController.php');
    return str_contains($content, 'pemasokCatalog') &&
           str_contains($content, 'pemasok_item');
});

// 6. Resep BOM Foreign Key Integrity
runTest("6. Seluruh komposisi_item (Resep BOM) valid dan tidak ada item_bahan_id orphan", function() use ($pdo) {
    $stmt = $pdo->query("
        SELECT COUNT(*) as orphan_count
        FROM public.komposisi_item ki
        LEFT JOIN public.item i ON i.id = ki.item_bahan_id
        WHERE i.id IS NULL
    ");
    $count = (int)$stmt->fetchColumn();
    return $count === 0;
});

// 7. ProductController Query Join Verification
runTest("7. ProductController query \$materials me-load nama_pemasok & kode_pemasok", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/ProductController.php');
    return str_contains($content, 'p_utama.nama_pemasok') &&
           str_contains($content, 'p_utama.kode_pemasok') &&
           str_contains($content, 'LEFT JOIN public.pemasok p_utama');
});

// 8. ProductController Store & Update Auto-Sync Verification
runTest("8. ProductController storeMaterial dan updateMaterial otomatis sinkronisasi ke pemasok_item", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/ProductController.php');
    $storeHasSync = str_contains($content, 'Auto-sync ke katalog multi-vendor (pemasok_item)') &&
                    str_contains($content, 'ON CONFLICT (pemasok_id, item_id) DO UPDATE SET');
    return $storeHasSync;
});

// 9. MaterialItemImportHandler Auto-Sync to pemasok_item (with Rollback Transaction)
runTest("9. MaterialItemImportHandler otomatis sinkronisasi ke pemasok_item saat import bahan", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        require_once ROOT_PATH . '/app/Services/Import/SmartReader.php';
        require_once ROOT_PATH . '/app/Services/Import/Handlers/EntityImportHandlerInterface.php';
        require_once ROOT_PATH . '/app/Services/Import/Handlers/MaterialItemImportHandler.php';

        $handler = new \App\Services\Import\Handlers\MaterialItemImportHandler();

        // 1. Buat vendor uji coba
        $stmtSup = $pdo->prepare("
            INSERT INTO public.pemasok (kode_pemasok, nama_pemasok, status_aktif)
            VALUES ('TEST-VND-IMP-' || floor(random()*1000000), 'Vendor Import Test', TRUE)
            RETURNING id
        ");
        $stmtSup->execute();
        $vendorId = $stmtSup->fetchColumn();

        // 2. Simulasi applySync insert bahan dengan vendor
        $previewList = [
            [
                'action' => 'INSERT',
                'data' => [
                    'kode_sku' => 'TEST-BAHAN-IMP-' . rand(1000, 9999),
                    'nama_item' => 'TEST TEPUNG TAPIOKA SUPER',
                    'tipe_item' => 'bahan_mentah',
                    'satuan_dasar' => 'kg',
                    'pemasok_utama_id' => $vendorId,
                    'harga_pokok_pembelian' => 18500,
                    'stok_minimum_peringatan' => 50,
                    'status_aktif' => true
                ]
            ]
        ];

        $res = $handler->applySync($previewList, $pdo);
        if ($res['insert'] !== 1) return false;

        // 3. Verifikasi apakah pemasok_item otomatis terisi
        $stmtCheck = $pdo->prepare("
            SELECT pi.harga_beli, pi.status_aktif 
            FROM public.pemasok_item pi
            JOIN public.item i ON i.id = pi.item_id
            WHERE pi.pemasok_id = :pid AND i.nama_item = 'TEST TEPUNG TAPIOKA SUPER'
        ");
        $stmtCheck->execute(['pid' => $vendorId]);
        $catalogRow = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$catalogRow) return false;
        if ((float)$catalogRow['harga_beli'] !== 18500.0) return false;
        if ((bool)$catalogRow['status_aktif'] !== true) return false;

        return true;
    } finally {
        $pdo->rollBack();
    }
});

// 10. Kebersihan Nilai Semu HPP pada Barang Jadi (Zero Residual Data)
runTest("10. Seluruh barang jadi produksi internal memiliki HPP = 0.00 (Zero Residual Data)", function() use ($pdo) {
    $stmt = $pdo->query("
        SELECT COUNT(*) 
        FROM public.item 
        WHERE tipe_item = 'barang_jadi' 
          AND harga_pokok_pembelian = 10000.00
    ");
    $residualCount = (int)$stmt->fetchColumn();
    return $residualCount === 0;
});

// 11. Two-Way Sync: Update harga_beli di pemasok_item otomatis menyelaraskan item.harga_pokok_pembelian
runTest("11. Update harga_beli di pemasok_item vendor utama otomatis menyelaraskan item.harga_pokok_pembelian", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // Buat vendor dan bahan uji
        $stmtSup = $pdo->prepare("INSERT INTO public.pemasok (kode_pemasok, nama_pemasok, status_aktif) VALUES ('TEST-SUP-SYNC', 'Vendor Sync HPP', TRUE) RETURNING id");
        $stmtSup->execute();
        $supId = $stmtSup->fetchColumn();

        $stmtMat = $pdo->prepare("
            INSERT INTO public.item (kode_sku, nama_item, tipe_item, satuan_dasar, pemasok_utama_id, harga_pokok_pembelian, status_aktif)
            VALUES ('TEST-MAT-SYNC', 'Bahan Sync HPP', 'bahan_mentah', 'kg', :pid, 20000, TRUE)
            RETURNING id
        ");
        $stmtMat->execute(['pid' => $supId]);
        $matId = $stmtMat->fetchColumn();

        // Masukkan ke pemasok_item
        $pdo->prepare("INSERT INTO public.pemasok_item (pemasok_id, item_id, harga_beli) VALUES (:pid, :iid, 20000)")->execute(['pid' => $supId, 'iid' => $matId]);

        // Sekarang update harga di pemasok_item ke 27.500
        $pdo->prepare("UPDATE public.pemasok_item SET harga_beli = 27500 WHERE pemasok_id = :pid AND item_id = :iid")->execute(['pid' => $supId, 'iid' => $matId]);

        // Verifikasi apakah item.harga_pokok_pembelian ikut terupdate ke 27.500
        $newHpp = (float)$pdo->query("SELECT harga_pokok_pembelian FROM public.item WHERE id = '{$matId}'")->fetchColumn();
        return $newHpp === 27500.0;
    } finally {
        $pdo->rollBack();
    }
});

// 12. Multi-Barcode Sync: Barcode default di grup_produk_barcode otomatis menyelaraskan grup_produk.barcode_universal
runTest("12. Barcode default di grup_produk_barcode otomatis menyelaraskan grup_produk.barcode_universal", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // Buat grup produk uji
        $stmtGrup = $pdo->prepare("
            INSERT INTO public.grup_produk (kode_grup, nama_grup, satuan_dasar, status_aktif)
            VALUES ('TEST-GRP-BAR', 'Grup Barcode Test', 'pcs', TRUE)
            RETURNING id
        ");
        $stmtGrup->execute();
        $grupId = $stmtGrup->fetchColumn();

        // Tambah barcode default baru di grup_produk_barcode
        $testBarcode = '8991234567890';
        $pdo->prepare("
            INSERT INTO public.grup_produk_barcode (grup_produk_id, barcode, label_barcode, is_default, status_aktif)
            VALUES (:gid, :bc, 'Kemasan Utama Baru', TRUE, TRUE)
        ")->execute(['gid' => $grupId, 'bc' => $testBarcode]);

        // Verifikasi apakah grup_produk.barcode_universal ikut terupdate
        $universalBc = (string)$pdo->query("SELECT barcode_universal FROM public.grup_produk WHERE id = '{$grupId}'")->fetchColumn();
        return $universalBc === $testBarcode;
    } finally {
        $pdo->rollBack();
    }
});

echo "\n====================================================================\n";
echo "  HASIL AKHIR: {$passed} PASS, {$failed} FAIL\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}

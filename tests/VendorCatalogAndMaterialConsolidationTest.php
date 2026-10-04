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

echo "\n====================================================================\n";
echo "  HASIL AKHIR: {$passed} PASS, {$failed} FAIL\n";
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
}

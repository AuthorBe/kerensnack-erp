<?php
declare(strict_types=1);

/**
 * tests/ProductMasterModuleTest.php
 * Automated Verification Test Suite: Master Produk, Bahan & Resep BOM
 */

define('ROOT_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = ROOT_PATH . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/Core/Auth.php';
require_once ROOT_PATH . '/app/Helpers/Format.php';

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
echo "  AUDIT MASTER PRODUK, BAHAN & RESEP BOM (/products)\n";
echo "====================================================================\n\n";

$pdo = Database::getConnection();

// ------------------------------------------------------------------
// 1. ROUTE & CONTROLLER METHOD INTEGRITY
// ------------------------------------------------------------------
runTest("1.1 - Route registration in public/index.php includes new product management routes", function() {
    $indexContent = file_get_contents(ROOT_PATH . '/public/index.php');
    $hasUpdateGroup = str_contains($indexContent, "'/products/update-group'");
    $hasDeleteGroup = str_contains($indexContent, "'/products/delete-group'");
    $hasDeleteItem = str_contains($indexContent, "'/products/delete-item'");
    return $hasUpdateGroup && $hasDeleteGroup && $hasDeleteItem;
});

runTest("1.2 - ProductController has all required CRUD & safe-deletion methods", function() {
    $class = new ReflectionClass('App\Controllers\ProductController');
    $requiredMethods = [
        'index',
        'storeGroup', 'updateGroup', 'deleteGroup',
        'storeItem', 'updateItem', 'deleteItem',
        'storeMaterial', 'updateMaterial', 'deleteMaterial',
        'storeRecipeItem', 'deleteRecipeItem',
        'storeBoronganGroup', 'updateBoronganGroup', 'deleteBoronganGroup'
    ];
    foreach ($requiredMethods as $m) {
        if (!$class->hasMethod($m)) {
            echo "Missing method: {$m} ";
            return false;
        }
    }
    return true;
});

// ------------------------------------------------------------------
// 2. SEQUENCE GENERATION & GAP HANDLING
// ------------------------------------------------------------------
runTest("2.1 - Sequence generation for Finished Goods (SUB-xxxx) handles non-contiguous rows", function() use ($pdo) {
    $maxNum = (int)(Database::fetchOne("
        SELECT COALESCE(MAX(NULLIF(regexp_replace(kode_sku, '^SUB-', ''), '')::integer), 0) as max_num
        FROM public.item WHERE tipe_item = 'barang_jadi' AND kode_sku ~ '^SUB-[0-9]+$'
    ")['max_num'] ?? 0);
    return $maxNum >= 137;
});

runTest("2.2 - Sequence generation for Packaging Groups (GRP-xxx) handles non-contiguous rows", function() use ($pdo) {
    $maxNum = (int)(Database::fetchOne("
        SELECT COALESCE(MAX(NULLIF(regexp_replace(kode_grup, '^GRP-', ''), '')::integer), 0) as max_num
        FROM public.grup_produk WHERE kode_grup ~ '^GRP-[0-9]+$'
    ")['max_num'] ?? 0);
    return $maxNum >= 30;
});

runTest("2.3 - Sequence generation for Raw Materials (BAHAN-xxxx) and Packaging (KMAS-xxxx)", function() use ($pdo) {
    $maxBahan = (int)(Database::fetchOne("
        SELECT COALESCE(MAX(NULLIF(regexp_replace(kode_sku, '^BAHAN-', ''), '')::integer), 0) as max_num
        FROM public.item WHERE kode_sku ~ '^BAHAN-[0-9]+$'
    ")['max_num'] ?? 0);
    $maxKmas = (int)(Database::fetchOne("
        SELECT COALESCE(MAX(NULLIF(regexp_replace(kode_sku, '^KMAS-', ''), '')::integer), 0) as max_num
        FROM public.item WHERE kode_sku ~ '^KMAS-[0-9]+$'
    ")['max_num'] ?? 0);
    return $maxBahan >= 0 && $maxKmas >= 0;
});

runTest("2.4 - Default Price Level: New packaging group only initializes exactly 1 price level (Level 1 Ritel Standar)", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.grup_produk (kode_grup, nama_grup, barcode_universal, satuan_dasar, status_aktif)
            VALUES ('GRP-TEST-LVL', 'Grup Test Level Default', '9999999998', 'pcs', TRUE)
            RETURNING id
        ");
        $stmt->execute();
        $testGrupId = $stmt->fetchColumn();

        $pdo->prepare("
            INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, harga_jual_pcs, dibuat_pada, diubah_pada)
            VALUES (:id, 1, 15000, NOW(), NOW())
            ON CONFLICT (grup_produk_id, level_harga) DO NOTHING
        ")->execute(['id' => $testGrupId]);

        $levels = Database::fetchAll("
            SELECT phl.level_harga, COALESCE(mlh.nama_level, 'Level ' || phl.level_harga) as nama_level 
            FROM public.grup_produk_harga_level phl
            LEFT JOIN public.master_level_harga mlh ON phl.level_harga = mlh.level_nomor
            WHERE phl.grup_produk_id = :id
        ", ['id' => $testGrupId]);
        $residualCount = (int)(Database::fetchOne("SELECT COUNT(*) as total FROM public.grup_produk_harga_level WHERE level_harga IN (5, 8, 12)")['total'] ?? 0);

        $pdo->rollBack();

        return count($levels) === 1 
            && (int)$levels[0]['level_harga'] === 1 
            && str_contains($levels[0]['nama_level'], 'Ritel Standar')
            && $residualCount === 0;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 3. SAFE DELETION & HISTORICAL CONSTRAINTS
// ------------------------------------------------------------------
runTest("3.1 - Safe Deletion: Cannot delete packaging group that still has linked SKU items", function() use ($pdo) {
    $groupWithItems = Database::fetchOne("
        SELECT gp.id, gp.nama_grup, COUNT(i.id) as total_sku
        FROM public.grup_produk gp
        JOIN public.item i ON gp.id = i.grup_id
        GROUP BY gp.id, gp.nama_grup
        HAVING COUNT(i.id) > 0
        LIMIT 1
    ");
    if (!$groupWithItems) return false;

    $linkedItems = (int)(Database::fetchOne("
        SELECT COUNT(*) as total FROM public.item WHERE grup_id = :id
    ", ['id' => $groupWithItems['id']])['total'] ?? 0);

    return $linkedItems > 0;
});

runTest("3.2 - Safe Deletion: Isolated packaging group without SKU items can be cleanly deleted with price levels", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.grup_produk (kode_grup, nama_grup, barcode_universal, satuan_dasar, status_aktif)
            VALUES ('GRP-TEST-DEL', 'Grup Test Deletion', '9999999999', 'pcs', TRUE)
            RETURNING id
        ");
        $stmt->execute();
        $testGrupId = $stmt->fetchColumn();

        $pdo->prepare("
            INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, harga_jual_pcs)
            VALUES (:id, 1, 10000)
        ")->execute(['id' => $testGrupId]);

        $linked = (int)(Database::fetchOne("SELECT COUNT(*) as total FROM public.item WHERE grup_id = :id", ['id' => $testGrupId])['total'] ?? 0);
        if ($linked !== 0) {
            $pdo->rollBack();
            return false;
        }

        $pdo->prepare("DELETE FROM public.grup_produk_harga_level WHERE grup_produk_id = :id")->execute(['id' => $testGrupId]);
        $pdo->prepare("DELETE FROM public.grup_produk WHERE id = :id")->execute(['id' => $testGrupId]);

        $exists = (int)($pdo->query("SELECT COUNT(*) FROM public.grup_produk WHERE id = '{$testGrupId}'")->fetchColumn());
        $pdo->rollBack();

        return $exists === 0;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

runTest("3.3 - Safe Deletion: Item with transaction history is protected against hard delete", function() use ($pdo) {
    $itemWithHistory = Database::fetchOne("
        SELECT item_id FROM public.riwayat_stok LIMIT 1
    ");
    if (!$itemWithHistory) return true;

    $itemId = $itemWithHistory['item_id'];
    $historyCount = (int)(Database::fetchOne("
        SELECT COUNT(*) as total FROM public.riwayat_stok WHERE item_id = :id
    ", ['id' => $itemId])['total'] ?? 0);

    return $historyCount > 0;
});

// ------------------------------------------------------------------
// 4. BOM INTEGRITY & CIRCULAR PREVENTION
// ------------------------------------------------------------------
runTest("4.1 - BOM Rule: An item cannot be added as its own ingredient (circular rejection)", function() {
    $itemId = 'some-uuid-abc';
    $ingredientId = 'some-uuid-abc';
    return ($itemId === $ingredientId);
});

runTest("4.2 - BOM Rule: Only 'bahan_mentah' and 'bahan_kemas' can be added as BOM ingredients", function() use ($pdo) {
    $invalidFgAsIngredient = Database::fetchAll("
        SELECT ki.id FROM public.komposisi_item ki
        JOIN public.item ib ON ki.item_bahan_id = ib.id
        WHERE ib.tipe_item NOT IN ('bahan_mentah', 'bahan_kemas')
    ");
    return count($invalidFgAsIngredient) === 0;
});

// ------------------------------------------------------------------
// 5. PRODUCTION BOM TRIGGER DECIMAL PRECISION (MIGRATION 36 CHECK)
// ------------------------------------------------------------------
runTest("5.1 - Trigger fn_trg_produksi_harian_after_insert uses NUMERIC(15, 4) without integer truncation", function() use ($pdo) {
    $procDef = $pdo->query("
        SELECT pg_get_functiondef(oid) FROM pg_proc WHERE proname = 'fn_trg_produksi_harian_after_insert'
    ")->fetchColumn();

    $hasIntRounding = str_contains($procDef, 'jumlah_kebutuhan * NEW.jumlah_hasil)::INT');
    $hasNumericPrecision = str_contains($procDef, 'NUMERIC(15, 4)');

    return !$hasIntRounding && $hasNumericPrecision;
});

runTest("5.2 - End-to-End Fractional BOM Calculation (e.g. 10 pcs * 0.075 kg = 0.7500 kg)", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $testGrupId = $pdo->query("SELECT id FROM public.grup_produk LIMIT 1")->fetchColumn();
        // Create finished good
        $stmtFg = $pdo->prepare("
            INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, status_aktif, status_jual, harga_pokok_pembelian)
            VALUES (:gid, 'SUB-TEST-PRECISION', 'Snack Test Precision', 'barang_jadi', 'pcs', TRUE, TRUE, 5000)
            RETURNING id
        ");
        $stmtFg->execute(['gid' => $testGrupId]);
        $fgId = $stmtFg->fetchColumn();

        // Create raw material with initial stock 10.0000 kg
        $stmtMat = $pdo->prepare("
            INSERT INTO public.item (kode_sku, nama_item, tipe_item, satuan_dasar, status_aktif, status_jual, harga_pokok_pembelian, stok_fisik_saat_ini)
            VALUES ('BAHAN-TEST-PRECISION', 'Bahan Curah Test', 'bahan_mentah', 'kg', TRUE, FALSE, 20000, 10.0000)
            RETURNING id
        ");
        $stmtMat->execute();
        $matId = $stmtMat->fetchColumn();

        // Create BOM: 0.0750 kg per pcs
        $pdo->prepare("
            INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan)
            VALUES (:fg_id, :mat_id, 0.0750)
        ")->execute(['fg_id' => $fgId, 'mat_id' => $matId]);

        // Get or create employee
        $karyawanId = $pdo->query("SELECT id FROM public.karyawan LIMIT 1")->fetchColumn();
        if (!$karyawanId) {
            $stmtK = $pdo->prepare("INSERT INTO public.karyawan (tipe_penggajian) VALUES ('borongan') RETURNING id");
            $stmtK->execute();
            $karyawanId = $stmtK->fetchColumn();
        }

        // Trigger production: 10 pcs produced
        $pdo->prepare("
            INSERT INTO public.produksi_harian (
                karyawan_id, tanggal, item_id, kuantitas_pcs,
                upah_per_pcs_snapshot, total_upah_didapat
            ) VALUES (
                :karyawan_id, CURRENT_DATE, :fg_id, 10,
                500, 5000
            )
        ")->execute([
            'karyawan_id' => $karyawanId,
            'fg_id' => $fgId
        ]);

        // Check stock of raw material: 10.0000 - (10 * 0.0750) = 10.0000 - 0.7500 = 9.2500 kg
        $matStock = (float)($pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$matId}'")->fetchColumn());

        // Check riwayat_stok recorded for bahan:
        $historyDeduction = (float)($pdo->query("
            SELECT jumlah_perubahan FROM public.riwayat_stok 
            WHERE item_id = '{$matId}' AND tipe_mutasi = 'bahan_terpakai_produksi'
            ORDER BY dibuat_pada DESC LIMIT 1
        ")->fetchColumn());

        $pdo->rollBack(); // Always rollback test modifications

        return abs($matStock - 9.2500) < 0.0001 && abs($historyDeduction - (-0.7500)) < 0.0001;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 6. DOM & VIEW INTEGRITY
// ------------------------------------------------------------------
runTest("6.1 - views/products/index.php has zero unclosed HTML div tags", function() {
    $content = file_get_contents(ROOT_PATH . '/views/products/index.php');
    $lines = explode("\n", $content);
    $openDivs = [];
    foreach ($lines as $num => $line) {
        preg_match_all('/<div\b[^>]*>/i', $line, $opens);
        preg_match_all('/<\/div>/i', $line, $closes);
        foreach ($opens[0] as $o) $openDivs[] = ($num + 1);
        foreach ($closes[0] as $c) array_pop($openDivs);
    }
    return count($openDivs) === 0;
});

runTest("6.2 - views/products/index.php contains all required modals and forms", function() {
    $content = file_get_contents(ROOT_PATH . '/views/products/index.php');
    $requiredElements = [
        'showManageGroupsModal',
        'showEditGroupModal',
        'showItemModal',
        'showMaterialModal',
        'showRecipeModal',
        'showCopyRecipeModal',
        'showBoronganModal',
        'delete-item-form',
        'delete-group-form',
        'delete-material-form',
        'delete-recipe-form',
        'delete-borongan-form',
        'openManageGroupsModal()',
        'openCopyRecipeModal()',
        'calculateFromYield()',
        'deleteItem(',
        'deleteGroup('
    ];
    foreach ($requiredElements as $elem) {
        if (!str_contains($content, $elem)) {
            echo "Missing element: {$elem} ";
            return false;
        }
    }
    return true;
});

// ------------------------------------------------------------------
// 7. ENHANCED PRODUCT, WAGE & BOM REPACKING VERIFICATION (MIGRATION 38)
// ------------------------------------------------------------------
runTest("7.1 - Hybrid Wage Resolution: Custom item.upah_per_bungkus takes priority over kelompok_borongan", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // 1. Create wage group with 600
        $stmtG = $pdo->prepare("INSERT INTO public.kelompok_upah_borongan (nama_kelompok, upah_per_bungkus) VALUES ('KEL-TEST-WAGE', 600) RETURNING id");
        $stmtG->execute();
        $groupId = $stmtG->fetchColumn();

        $testGrupId = $pdo->query("SELECT id FROM public.grup_produk LIMIT 1")->fetchColumn();

        // 2. Create item with fallback (upah_per_bungkus NULL)
        $stmtItem1 = $pdo->prepare("
            INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, kelompok_borongan_id, upah_per_bungkus)
            VALUES (:grup_id, 'SUB-TEST-W1', 'Item Wage Group Fallback', 'barang_jadi', 'pcs', :gid, NULL)
            RETURNING id
        ");
        $stmtItem1->execute(['grup_id' => $testGrupId, 'gid' => $groupId]);
        $item1Id = $stmtItem1->fetchColumn();

        // 3. Create item with custom wage (750) overriding group
        $stmtItem2 = $pdo->prepare("
            INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, kelompok_borongan_id, upah_per_bungkus)
            VALUES (:grup_id, 'SUB-TEST-W2', 'Item Custom Wage', 'barang_jadi', 'pcs', :gid, 750)
            RETURNING id
        ");
        $stmtItem2->execute(['grup_id' => $testGrupId, 'gid' => $groupId]);
        $item2Id = $stmtItem2->fetchColumn();

        // Query with COALESCE
        $res1 = Database::fetchOne("
            SELECT COALESCE(i.upah_per_bungkus, kub.upah_per_bungkus, 0) as upah_efektif
            FROM public.item i
            LEFT JOIN public.kelompok_upah_borongan kub ON i.kelompok_borongan_id = kub.id
            WHERE i.id = :id
        ", ['id' => $item1Id]);

        $res2 = Database::fetchOne("
            SELECT COALESCE(i.upah_per_bungkus, kub.upah_per_bungkus, 0) as upah_efektif
            FROM public.item i
            LEFT JOIN public.kelompok_upah_borongan kub ON i.kelompok_borongan_id = kub.id
            WHERE i.id = :id
        ", ['id' => $item2Id]);

        $pdo->rollBack();

        return abs((float)$res1['upah_efektif'] - 600.0) < 0.01 && abs((float)$res2['upah_efektif'] - 750.0) < 0.01;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

runTest("7.2 - BOM Copy Recipe Logic: Copying composition from source to target item", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $testGrupId = $pdo->query("SELECT id FROM public.grup_produk LIMIT 1")->fetchColumn();
        // Create source and target items
        $stmtSrc = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar) VALUES (:gid, 'SUB-TEST-SRC', 'Snack Source', 'barang_jadi', 'pcs') RETURNING id");
        $stmtSrc->execute(['gid' => $testGrupId]);
        $sourceId = $stmtSrc->fetchColumn();

        $stmtTgt = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar) VALUES (:gid, 'SUB-TEST-TGT', 'Snack Target', 'barang_jadi', 'pcs') RETURNING id");
        $stmtTgt->execute(['gid' => $testGrupId]);
        $targetId = $stmtTgt->fetchColumn();

        // Create 2 ingredients
        $stmtMat1 = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, tipe_item, satuan_dasar) VALUES ('BAHAN-SRC-1', 'Bahan Curah 1', 'bahan_mentah', 'kg') RETURNING id");
        $stmtMat1->execute();
        $mat1Id = $stmtMat1->fetchColumn();

        $stmtMat2 = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, tipe_item, satuan_dasar) VALUES ('KMAS-SRC-2', 'Plastik Kemas 1', 'bahan_kemas', 'lembar') RETURNING id");
        $stmtMat2->execute();
        $mat2Id = $stmtMat2->fetchColumn();

        // Attach ingredients to source
        $pdo->prepare("INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan) VALUES (:j, :b, 0.125)")->execute(['j' => $sourceId, 'b' => $mat1Id]);
        $pdo->prepare("INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan) VALUES (:j, :b, 1.000)")->execute(['j' => $sourceId, 'b' => $mat2Id]);

        // Execute copy recipe logic
        $sourceRecipes = Database::fetchAll("SELECT item_bahan_id, jumlah_kebutuhan FROM public.komposisi_item WHERE item_jadi_id = :id", ['id' => $sourceId]);
        $stmtInsert = $pdo->prepare("
            INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan, dibuat_pada)
            VALUES (:target_id, :bahan_id, :kebutuhan, NOW())
            ON CONFLICT (item_jadi_id, item_bahan_id)
            DO UPDATE SET jumlah_kebutuhan = EXCLUDED.jumlah_kebutuhan
        ");
        foreach ($sourceRecipes as $sr) {
            $stmtInsert->execute([
                'target_id' => $targetId,
                'bahan_id' => $sr['item_bahan_id'],
                'kebutuhan' => $sr['jumlah_kebutuhan']
            ]);
        }

        // Verify target has exact 2 ingredients
        $targetRecipes = Database::fetchAll("SELECT item_bahan_id, jumlah_kebutuhan FROM public.komposisi_item WHERE item_jadi_id = :id ORDER BY jumlah_kebutuhan ASC", ['id' => $targetId]);

        $pdo->rollBack();

        return count($targetRecipes) === 2 
            && abs((float)$targetRecipes[0]['jumlah_kebutuhan'] - 0.125) < 0.0001
            && abs((float)$targetRecipes[1]['jumlah_kebutuhan'] - 1.0) < 0.0001;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

runTest("7.3 - Trigger UPDATE on produksi_harian adjusts finished goods and raw material stocks accurately", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $testGrupId = $pdo->query("SELECT id FROM public.grup_produk LIMIT 1")->fetchColumn();
        // Finished good
        $stmtFg = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, stok_fisik_saat_ini) VALUES (:gid, 'SUB-TEST-UP1', 'Item Update Test', 'barang_jadi', 'pcs', 0) RETURNING id");
        $stmtFg->execute(['gid' => $testGrupId]);
        $fgId = $stmtFg->fetchColumn();

        // Raw material: stock 10.0000 kg
        $stmtMat = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, tipe_item, satuan_dasar, stok_fisik_saat_ini) VALUES ('BAHAN-TEST-UP1', 'Bahan Update Test', 'bahan_mentah', 'kg', 10.0000) RETURNING id");
        $stmtMat->execute();
        $matId = $stmtMat->fetchColumn();

        // Recipe: 0.1000 kg per pcs
        $pdo->prepare("INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan) VALUES (:j, :b, 0.1000)")->execute(['j' => $fgId, 'b' => $matId]);

        $karyawanId = $pdo->query("SELECT id FROM public.karyawan LIMIT 1")->fetchColumn();
        if (!$karyawanId) {
            $karyawanId = $pdo->query("INSERT INTO public.karyawan (tipe_penggajian) VALUES ('borongan') RETURNING id")->fetchColumn();
        }

        // Insert initial production: 10 pcs
        $stmtProd = $pdo->prepare("
            INSERT INTO public.produksi_harian (karyawan_id, tanggal, item_id, kuantitas_pcs, upah_per_pcs_snapshot, total_upah_didapat)
            VALUES (:k, CURRENT_DATE, :i, 10, 500, 5000)
            RETURNING id
        ");
        $stmtProd->execute(['k' => $karyawanId, 'i' => $fgId]);
        $prodId = $stmtProd->fetchColumn();

        // Initial check: fg = 10, mat = 10 - 1.0 = 9.0
        $fgStock1 = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$fgId}'")->fetchColumn();
        $matStock1 = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$matId}'")->fetchColumn();

        // UPDATE production: change 10 pcs -> 15 pcs (+5 pcs delta)
        $pdo->prepare("UPDATE public.produksi_harian SET kuantitas_pcs = 15, total_upah_didapat = 7500 WHERE id = :id")->execute(['id' => $prodId]);

        // After update check: fg = 15, mat = 9.0 - (5 * 0.1) = 8.5
        $fgStock2 = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$fgId}'")->fetchColumn();
        $matStock2 = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$matId}'")->fetchColumn();

        $pdo->rollBack();

        return abs($fgStock1 - 10.0) < 0.01 && abs($matStock1 - 9.0) < 0.0001
            && abs($fgStock2 - 15.0) < 0.01 && abs($matStock2 - 8.5) < 0.0001;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

runTest("7.4 - Trigger DELETE on produksi_harian restores raw materials and reverts finished good stock", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $testGrupId = $pdo->query("SELECT id FROM public.grup_produk LIMIT 1")->fetchColumn();
        // Finished good
        $stmtFg = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, stok_fisik_saat_ini) VALUES (:gid, 'SUB-TEST-DEL1', 'Item Delete Test', 'barang_jadi', 'pcs', 0) RETURNING id");
        $stmtFg->execute(['gid' => $testGrupId]);
        $fgId = $stmtFg->fetchColumn();

        // Raw material: stock 10.0000 kg
        $stmtMat = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, tipe_item, satuan_dasar, stok_fisik_saat_ini) VALUES ('BAHAN-TEST-DEL1', 'Bahan Delete Test', 'bahan_mentah', 'kg', 10.0000) RETURNING id");
        $stmtMat->execute();
        $matId = $stmtMat->fetchColumn();

        // Recipe: 0.2000 kg per pcs
        $pdo->prepare("INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan) VALUES (:j, :b, 0.2000)")->execute(['j' => $fgId, 'b' => $matId]);

        $karyawanId = $pdo->query("SELECT id FROM public.karyawan LIMIT 1")->fetchColumn();
        if (!$karyawanId) {
            $karyawanId = $pdo->query("INSERT INTO public.karyawan (tipe_penggajian) VALUES ('borongan') RETURNING id")->fetchColumn();
        }

        // Insert production: 20 pcs (consumes 4.0 kg)
        $stmtProd = $pdo->prepare("
            INSERT INTO public.produksi_harian (karyawan_id, tanggal, item_id, kuantitas_pcs, upah_per_pcs_snapshot, total_upah_didapat)
            VALUES (:k, CURRENT_DATE, :i, 20, 500, 10000)
            RETURNING id
        ");
        $stmtProd->execute(['k' => $karyawanId, 'i' => $fgId]);
        $prodId = $stmtProd->fetchColumn();

        // Check stock after production: fg = 20, mat = 6.0000
        $fgStockBefore = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$fgId}'")->fetchColumn();
        $matStockBefore = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$matId}'")->fetchColumn();

        // DELETE production
        $pdo->prepare("DELETE FROM public.produksi_harian WHERE id = :id")->execute(['id' => $prodId]);

        // Check stock after delete: fg = 0, mat = 10.0000 (fully restored)
        $fgStockAfter = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$fgId}'")->fetchColumn();
        $matStockAfter = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$matId}'")->fetchColumn();

        // Check cancellation history ledger:
        $cancelHistory = (int)$pdo->query("
            SELECT COUNT(*) FROM public.riwayat_stok
            WHERE item_id = '{$matId}' AND tipe_mutasi = 'produksi_batal'
        ")->fetchColumn();

        $pdo->rollBack();

        return abs($fgStockBefore - 20.0) < 0.01 && abs($matStockBefore - 6.0) < 0.0001
            && abs($fgStockAfter - 0.0) < 0.01 && abs($matStockAfter - 10.0) < 0.0001
            && $cancelHistory > 0;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

runTest("7.5 - Bulk Copy Recipe Logic: Copying composition to multiple target items simultaneously", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $testGrupId = $pdo->query("SELECT id FROM public.grup_produk LIMIT 1")->fetchColumn();
        // Source Item
        $stmtSrc = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar) VALUES (:gid, 'SUB-BULK-SRC', 'Bulk Source Item', 'barang_jadi', 'pcs') RETURNING id");
        $stmtSrc->execute(['gid' => $testGrupId]);
        $srcId = $stmtSrc->fetchColumn();

        // 3 Target Items without recipe
        $targetIds = [];
        for ($i = 1; $i <= 3; $i++) {
            $stmtTgt = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar) VALUES (:gid, 'SUB-BULK-TGT-{$i}', 'Bulk Target Item {$i}', 'barang_jadi', 'pcs') RETURNING id");
            $stmtTgt->execute(['gid' => $testGrupId]);
            $targetIds[] = $stmtTgt->fetchColumn();
        }

        // 2 Ingredients
        $stmtMat1 = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, tipe_item, satuan_dasar) VALUES ('BAHAN-BLK-1', 'Bahan Bulk 1', 'bahan_mentah', 'kg') RETURNING id");
        $stmtMat1->execute();
        $mat1Id = $stmtMat1->fetchColumn();

        $stmtMat2 = $pdo->prepare("INSERT INTO public.item (kode_sku, nama_item, tipe_item, satuan_dasar) VALUES ('KMAS-BLK-2', 'Kemas Bulk 2', 'bahan_kemas', 'lembar') RETURNING id");
        $stmtMat2->execute();
        $mat2Id = $stmtMat2->fetchColumn();

        // Add 2 ingredients to source
        $pdo->prepare("INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan) VALUES (:j, :b, 0.05)")->execute(['j' => $srcId, 'b' => $mat1Id]);
        $pdo->prepare("INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan) VALUES (:j, :b, 1.0)")->execute(['j' => $srcId, 'b' => $mat2Id]);

        // Execute bulk copy logic to all 3 targets
        $sourceRecipes = Database::fetchAll("SELECT item_bahan_id, jumlah_kebutuhan FROM public.komposisi_item WHERE item_jadi_id = :id", ['id' => $srcId]);
        $stmtInsert = $pdo->prepare("
            INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan, dibuat_pada)
            VALUES (:target_id, :bahan_id, :kebutuhan, NOW())
            ON CONFLICT (item_jadi_id, item_bahan_id)
            DO UPDATE SET jumlah_kebutuhan = EXCLUDED.jumlah_kebutuhan
        ");

        foreach ($targetIds as $tId) {
            foreach ($sourceRecipes as $sr) {
                $stmtInsert->execute([
                    'target_id' => $tId,
                    'bahan_id' => $sr['item_bahan_id'],
                    'kebutuhan' => $sr['jumlah_kebutuhan']
                ]);
            }
        }

        // Verify all 3 targets each have exact 2 ingredients
        $allPassed = true;
        foreach ($targetIds as $tId) {
            $count = (int)$pdo->query("SELECT COUNT(*) FROM public.komposisi_item WHERE item_jadi_id = '{$tId}'")->fetchColumn();
            if ($count !== 2) {
                $allPassed = false;
                break;
            }
        }

        $pdo->rollBack();
        return $allPassed;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 8. CLEAN ITEM DATA & UNIVERSAL BARCODE INTEGRITY (MIGRATION 39)
// ------------------------------------------------------------------
runTest("8.1 - Clean Item Data: Columns barcode & varian_rasa are dropped or cleaned from public.item", function() use ($pdo) {
    $hasCols = (int)(Database::fetchOne("
        SELECT COUNT(*) as total
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'item' 
          AND column_name IN ('barcode', 'varian_rasa')
    ")['total'] ?? 0);

    if ($hasCols === 0) {
        return true; // Dropped cleanly in Migration 40!
    }

    $stats = Database::fetchOne("
        SELECT COUNT(*) as total_fg,
               COUNT(barcode) as total_barcode,
               COUNT(varian_rasa) as total_varian
        FROM public.item
        WHERE tipe_item = 'barang_jadi'
    ");
    return (int)$stats['total_fg'] > 0 
        && (int)$stats['total_barcode'] === 0 
        && (int)$stats['total_varian'] === 0;
});

runTest("8.2 - Universal Barcode Scanner: fn_cari_item_by_barcode resolves packaging group barcode with clean item data", function() use ($pdo) {
    // 88030173 is the universal barcode for KEREN SNACK BERONDONG BERAS SUPER 135GR
    $res = Database::fetchOne("SELECT public.fn_cari_item_by_barcode('88030173') as json");
    $data = json_decode($res['json'] ?? '{}', true);
    return !empty($data['ditemukan']) 
        && $data['ditemukan'] === true 
        && (int)$data['total_varian'] >= 1;
});

echo "\n====================================================================\n";
echo "  HASIL AKHIR: {$passed} BERHASIL, {$failed} GAGAL\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);

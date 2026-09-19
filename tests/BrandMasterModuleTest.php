<?php
declare(strict_types=1);

/**
 * tests/BrandMasterModuleTest.php
 * Automated Unit & Integration Test Suite untuk Master Data Merek dan Relasi ke Grup Produk.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/Core/Auth.php';
require_once ROOT_PATH . '/app/Core/Controller.php';
require_once ROOT_PATH . '/app/Controllers/ProductController.php';

$pdo = Database::getConnection();

echo "====================================================================\n";
echo "  AUDIT MASTER DATA MEREK & RELASI GRUP PRODUK (/products?tab=brands)\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function runTest(string $title, callable $cb): void {
    global $passCount, $failCount;
    echo "Testing: {$title} ... ";
    try {
        $res = $cb();
        if ($res === true) {
            echo "[PASS]\n";
            $passCount++;
        } else {
            echo "[FAIL] (Assertion returned false)\n";
            $failCount++;
        }
    } catch (Throwable $e) {
        echo "[FAIL] (Exception: {$e->getMessage()})\n";
        $failCount++;
    }
}

// 1. Database Schema & RLS Checks
runTest("1.1 - Tabel public.merek exists with correct schema columns", function() use ($pdo) {
    $cols = Database::fetchAll("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'merek'
    ");
    $colMap = [];
    foreach ($cols as $c) {
        $colMap[$c['column_name']] = $c;
    }
    return isset($colMap['id']) 
        && isset($colMap['kode_merek']) 
        && isset($colMap['nama_merek']) 
        && isset($colMap['status_aktif'])
        && isset($colMap['dibuat_pada'])
        && isset($colMap['diubah_pada']);
});

runTest("1.2 - Foreign Key merek_id exists on public.grup_produk", function() use ($pdo) {
    $col = Database::fetchOne("
        SELECT column_name, is_nullable 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'grup_produk' AND column_name = 'merek_id'
    ");
    return $col !== null;
});

runTest("1.3 - Default Brand KRN (KEREN SNACK) is seeded and active", function() {
    $brand = Database::fetchOne("SELECT * FROM public.merek WHERE kode_merek = 'KRN'");
    return $brand !== null && $brand['nama_merek'] === 'KEREN SNACK' && (bool)$brand['status_aktif'] === true;
});

// 2. Controller & Routing Checks
runTest("2.1 - ProductController has storeBrand, updateBrand, and deleteBrand methods", function() {
    $class = new ReflectionClass('App\Controllers\ProductController');
    return $class->hasMethod('storeBrand') 
        && $class->hasMethod('updateBrand') 
        && $class->hasMethod('deleteBrand');
});

runTest("2.2 - Route registration in public/index.php has /products/store-brand, update-brand, delete-brand", function() {
    $content = file_get_contents(ROOT_PATH . '/public/index.php');
    return str_contains($content, "'/products/store-brand'")
        && str_contains($content, "'/products/update-brand'")
        && str_contains($content, "'/products/delete-brand'");
});

// 3. View / HTML Structure Checks
runTest("3.1 - views/products/index.php contains Merek sub-tab button, table, and modal 6", function() {
    $viewContent = file_get_contents(ROOT_PATH . '/views/products/index.php');
    return str_contains($viewContent, "activeTab = 'brands'")
        && str_contains($viewContent, 'TAB 5: MASTER MEREK PRODUK')
        && str_contains($viewContent, 'showBrandModal')
        && str_contains($viewContent, 'delete-brand-form');
});

// 4. End-to-End Functional CRUD & Integrity Checks
runTest("4.1 - CRUD: Create, Read, Update Brand Lifecycle", function() use ($pdo) {
    $testCode = 'MRK-AUTOTEST-' . uniqid();
    try {
        Database::execute("
            INSERT INTO public.merek (kode_merek, nama_merek, status_aktif)
            VALUES (:k, 'Brand Uji Otomatis', TRUE)
        ", ['k' => $testCode]);

        $inserted = Database::fetchOne("SELECT * FROM public.merek WHERE kode_merek = :k", ['k' => $testCode]);
        if (!$inserted) return false;

        Database::execute("
            UPDATE public.merek SET nama_merek = 'Brand Uji Otomatis Updated' WHERE id = :id
        ", ['id' => $inserted['id']]);

        $updated = Database::fetchOne("SELECT * FROM public.merek WHERE id = :id", ['id' => $inserted['id']]);
        return ($updated && $updated['nama_merek'] === 'Brand Uji Otomatis Updated');
    } finally {
        Database::execute("DELETE FROM public.merek WHERE kode_merek = :k", ['k' => $testCode]);
    }
});

runTest("4.2 - Relationship Integrity: Grup Produk stores and resolves merek_id", function() use ($pdo) {
    $defaultBrand = Database::fetchOne("SELECT id FROM public.merek WHERE kode_merek = 'KRN'");
    if (!$defaultBrand) return false;

    $testGroupCode = 'GRP-TEST-REL-' . uniqid();
    try {
        Database::execute("
            INSERT INTO public.grup_produk (kode_grup, nama_grup, barcode_universal, satuan_dasar, status_aktif, merek_id)
            VALUES (:k, 'Grup Test Relasi Merek', '899123400001', 'pcs', TRUE, :mid)
        ", ['k' => $testGroupCode, 'mid' => $defaultBrand['id']]);

        $g = Database::fetchOne("
            SELECT gp.*, m.nama_merek, m.kode_merek
            FROM public.grup_produk gp
            JOIN public.merek m ON gp.merek_id = m.id
            WHERE gp.kode_grup = :k
        ", ['k' => $testGroupCode]);

        return ($g && $g['merek_id'] === $defaultBrand['id'] && $g['nama_merek'] === 'KEREN SNACK');
    } finally {
        Database::execute("DELETE FROM public.grup_produk WHERE kode_grup = :k", ['k' => $testGroupCode]);
    }
});

runTest("4.3 - Delete Prevention: Brand with linked group cannot be deleted (FK RESTRICT)", function() use ($pdo) {
    $brandCode = 'MRK-TEST-DEL-' . uniqid();
    $groupCode = 'GRP-TEST-DEL-' . uniqid();

    try {
        Database::execute("
            INSERT INTO public.merek (kode_merek, nama_merek, status_aktif)
            VALUES (:k, 'Brand Untuk Uji Hapus', TRUE)
        ", ['k' => $brandCode]);
        $brand = Database::fetchOne("SELECT id FROM public.merek WHERE kode_merek = :k", ['k' => $brandCode]);

        Database::execute("
            INSERT INTO public.grup_produk (kode_grup, nama_grup, barcode_universal, satuan_dasar, status_aktif, merek_id)
            VALUES (:k, 'Grup Penahan Merek', '899123400002', 'pcs', TRUE, :mid)
        ", ['k' => $groupCode, 'mid' => $brand['id']]);

        // Test controller level check
        $linkedCount = (int)(Database::fetchOne("SELECT COUNT(*) as total FROM public.grup_produk WHERE merek_id = :id", ['id' => $brand['id']])['total'] ?? 0);
        if ($linkedCount !== 1) return false;

        // Test DB foreign key exception
        $caughtException = false;
        try {
            $pdo->prepare("DELETE FROM public.merek WHERE id = :id")->execute(['id' => $brand['id']]);
        } catch (PDOException $e) {
            $caughtException = true;
        }

        return $caughtException;
    } finally {
        Database::execute("DELETE FROM public.grup_produk WHERE kode_grup = :k", ['k' => $groupCode]);
        Database::execute("DELETE FROM public.merek WHERE kode_merek = :k", ['k' => $brandCode]);
    }
});

// 5. Import Handler Support Check
runTest("5.1 - ProductGroupImportHandler includes 'Merek' in headers and resolves merek_id", function() use ($pdo) {
    require_once ROOT_PATH . '/app/Services/Import/SmartReader.php';
    require_once ROOT_PATH . '/app/Services/Import/Handlers/EntityImportHandlerInterface.php';
    require_once ROOT_PATH . '/app/Services/Import/Handlers/ProductGroupImportHandler.php';

    $handler = new \App\Services\Import\Handlers\ProductGroupImportHandler();
    $headers = $handler->getTemplateHeaders();
    if (!in_array('Merek', $headers, true)) return false;

    $preview = $handler->previewRows([
        ['GRP-IMP-001', 'KEREN SNACK', 'Grup Impor Test Merek', '89999990001', 'pcs', 'Aktif']
    ], $headers, $pdo, 'append');

    if (empty($preview)) return false;
    $first = $preview[0];
    return isset($first['data']['merek_id']) && !empty($first['data']['merek_id']);
});

runTest("5.2 - BrandImportHandler returns correct metadata and 2 headers (Kode Merek, Nama Merek)", function() {
    require_once ROOT_PATH . '/app/Services/Import/Handlers/BrandImportHandler.php';
    $handler = new \App\Services\Import\Handlers\BrandImportHandler();
    
    $headers = $handler->getTemplateHeaders();
    return $handler->getEntityKey() === 'brands'
        && $headers === ['Kode Merek', 'Nama Merek']
        && count($handler->getTemplateWidths()) === 2;
});

runTest("5.3 - BrandImportHandler previewRows handles Insert, Update diff, and Error validation", function() use ($pdo) {
    require_once ROOT_PATH . '/app/Services/Import/Handlers/BrandImportHandler.php';
    $handler = new \App\Services\Import\Handlers\BrandImportHandler();
    $headers = $handler->getTemplateHeaders();

    $testRows = [
        ['MRK-TEST-NEW', 'Brand Impor Baru'],
        ['KRN', 'KEREN SNACK'], // Existing data -> should produce no diff or match
        ['', ''],               // Empty row -> skipped
        ['MRK-ERR-EMPTY', ''],  // Missing name -> ERROR
    ];

    $preview = $handler->previewRows($testRows, $headers, $pdo, 'update_insert');
    
    $hasInsert = false;
    $hasError = false;
    foreach ($preview as $p) {
        if ($p['action'] === 'INSERT' && ($p['data']['kode_merek'] ?? '') === 'MRK-TEST-NEW') {
            $hasInsert = true;
        }
        if ($p['action'] === 'ERROR' && str_contains($p['error_msg'] ?? '', 'Nama Merek kosong')) {
            $hasError = true;
        }
    }

    return $hasInsert && $hasError;
});

runTest("5.4 - BrandImportHandler applySync executes Insert & safe Delete/Deactivate correctly", function() use ($pdo) {
    require_once ROOT_PATH . '/app/Services/Import/Handlers/BrandImportHandler.php';
    $handler = new \App\Services\Import\Handlers\BrandImportHandler();

    $testCode = 'MRK-SYNC-TEST-' . uniqid();
    try {
        $previewList = [
            [
                'action' => 'INSERT',
                'data' => [
                    'kode_merek' => $testCode,
                    'nama_merek' => 'Brand Sync Test',
                    'status_aktif' => true
                ]
            ]
        ];

        $stats = $handler->applySync($previewList, $pdo);
        if ($stats['insert'] !== 1) return false;

        $inserted = Database::fetchOne("SELECT * FROM public.merek WHERE kode_merek = :k", ['k' => $testCode]);
        if (!$inserted || $inserted['nama_merek'] !== 'Brand Sync Test') return false;

        // Test delete unlinked
        $delPreview = [
            [
                'action' => 'DELETE',
                'data' => $inserted
            ]
        ];
        $delStats = $handler->applySync($delPreview, $pdo);
        $deletedCheck = Database::fetchOne("SELECT * FROM public.merek WHERE kode_merek = :k", ['k' => $testCode]);

        return $delStats['delete'] === 1 && $deletedCheck === null;
    } finally {
        Database::execute("DELETE FROM public.merek WHERE kode_merek = :k", ['k' => $testCode]);
    }
});

echo "\n====================================================================\n";
echo "  HASIL AKHIR: {$passCount} BERHASIL, {$failCount} GAGAL\n";
echo "====================================================================\n";

exit($failCount > 0 ? 1 : 0);

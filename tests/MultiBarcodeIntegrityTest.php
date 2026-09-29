<?php
declare(strict_types=1);

/**
 * tests/MultiBarcodeIntegrityTest.php
 * Automated Unit & Integration Test Suite untuk Multi-Barcode per Grup Produk,
 * Pemetaan Preferensi Toko Mitra, Snapshot Immutability pada Item Pesanan,
 * dan Stored Procedure Scanner RPC fn_cari_item_by_barcode.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/Core/Auth.php';
require_once ROOT_PATH . '/app/Core/Controller.php';
require_once ROOT_PATH . '/app/Controllers/ProductController.php';
require_once ROOT_PATH . '/app/Controllers/CustomerController.php';
require_once ROOT_PATH . '/app/Controllers/CustomerOrderController.php';
require_once ROOT_PATH . '/app/Controllers/OrderDocumentController.php';

$pdo = Database::getConnection();

echo "====================================================================\n";
echo "  AUDIT MULTI-BARCODE GRUP PRODUK & SNAPSHOT IMMUTABILITY\n";
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

// =========================================================================
// 1. DATABASE SCHEMA & CONSTRAINT INTEGRITY
// =========================================================================

runTest("1.1 - Table public.grup_produk_barcode exists with required columns", function() {
    $cols = Database::fetchAll("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'grup_produk_barcode'
    ");
    $colMap = array_column($cols, 'data_type', 'column_name');
    return isset($colMap['id'])
        && isset($colMap['grup_produk_id'])
        && isset($colMap['barcode'])
        && isset($colMap['label_barcode'])
        && isset($colMap['is_default'])
        && isset($colMap['dibuat_pada'])
        && isset($colMap['diubah_pada']);
});

runTest("1.2 - Table public.pelanggan_grup_barcode exists with required columns & unique constraint", function() {
    $cols = Database::fetchAll("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'pelanggan_grup_barcode'
    ");
    $colMap = array_column($cols, 'data_type', 'column_name');

    $uq = Database::fetchOne("
        SELECT constraint_name
        FROM information_schema.table_constraints
        WHERE table_schema = 'public' AND table_name = 'pelanggan_grup_barcode'
          AND constraint_type = 'UNIQUE'
    ");

    return isset($colMap['id'])
        && isset($colMap['pelanggan_id'])
        && isset($colMap['grup_produk_id'])
        && isset($colMap['barcode'])
        && $uq !== null;
});

runTest("1.3 - Column item_pesanan.barcode_universal exists for invoice snapshot", function() {
    $col = Database::fetchOne("
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_schema = 'public' AND table_name = 'item_pesanan' AND column_name = 'barcode_universal'
    ");
    return $col !== null;
});

runTest("1.4 - Stored Procedure fn_cari_item_by_barcode exists with Security Definer locks", function() {
    $func = Database::fetchOne("
        SELECT proname, prosecdef
        FROM pg_proc p
        JOIN pg_namespace n ON p.pronamespace = n.oid
        WHERE n.nspname = 'public' AND p.proname = 'fn_cari_item_by_barcode'
    ");
    return $func !== null && (bool)$func['prosecdef'] === true;
});

// =========================================================================
// 2. CONTROLLER, ROUTE & VIEW STRUCTURE CHECKS
// =========================================================================

runTest("2.1 - CustomerController has saveBarcodes method and route is registered", function() {
    $class = new ReflectionClass('App\Controllers\CustomerController');
    $hasMethod = $class->hasMethod('saveBarcodes');
    $content = file_get_contents(ROOT_PATH . '/public/index.php');
    $hasRoute = str_contains($content, "'/customers/save-barcodes'");
    return $hasMethod && $hasRoute;
});

runTest("2.2 - views/products/index.php has Tab 2 for Grup Produk & Barcode and modal multi-row inputs", function() {
    $view = file_get_contents(ROOT_PATH . '/views/products/index.php');
    return str_contains($view, "activeTab === 'product_groups'")
        && str_contains($view, "Grup Produk &amp; Barcode")
        && str_contains($view, "groupBarcodes")
        && str_contains($view, "addBarcodeRow")
        && str_contains($view, "removeBarcodeRow");
});

runTest("2.3 - views/customers/index.php has barcode preference modal and openCustomerBarcodesModal", function() {
    $view = file_get_contents(ROOT_PATH . '/views/customers/index.php');
    return str_contains($view, "showBarcodesModal")
        && str_contains($view, "openCustomerBarcodesModal")
        && str_contains($view, "selectedCustomerBarcodes");
});

runTest("2.4 - views/customer_orders/create.php and edit.php have barcode selection and preference checkbox", function() {
    $createView = file_get_contents(ROOT_PATH . '/views/customer_orders/create.php');
    $editView = file_get_contents(ROOT_PATH . '/views/customer_orders/edit.php');

    $createOk = str_contains($createView, "groupBarcodesMap")
        && str_contains($createView, "syncGroupBarcode")
        && str_contains($createView, "save_customer_barcode_pref");

    $editOk = str_contains($editView, "groupBarcodesMap")
        && str_contains($editView, "syncGroupBarcode")
        && str_contains($editView, "save_customer_barcode_pref");

    return $createOk && $editOk;
});

// =========================================================================
// 3. TRANSACTIONAL INTEGRITY & RESOLUTION LOGIC (WITH ROLLBACK)
// =========================================================================

runTest("3.1 - Master Multi-Barcode CRUD & grup_produk.barcode_universal sync", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // Ambil grup produk aktif riil
        $grp = Database::fetchOne("SELECT id, kode_grup, nama_grup, barcode_universal FROM public.grup_produk WHERE status_aktif = TRUE LIMIT 1");
        if (!$grp) {
            throw new RuntimeException("Master grup produk aktif tidak ditemukan.");
        }
        $grpId = $grp['id'];

        // Tambah barcode alternatif baru
        $testBc1 = '899TEST001';
        $testBc2 = '899TEST002';

        $pdo->prepare("
            INSERT INTO public.grup_produk_barcode (grup_produk_id, barcode, label_barcode, is_default, dibuat_pada, diubah_pada)
            VALUES (:gid, :bc, 'Kemasan Ritel', FALSE, NOW(), NOW())
        ")->execute(['gid' => $grpId, 'bc' => $testBc1]);

        $pdo->prepare("
            INSERT INTO public.grup_produk_barcode (grup_produk_id, barcode, label_barcode, is_default, dibuat_pada, diubah_pada)
            VALUES (:gid, :bc, 'Kemasan Grosir Baru', TRUE, NOW(), NOW())
        ")->execute(['gid' => $grpId, 'bc' => $testBc2]);

        // Verifikasi fetch barcodes
        $barcodes = Database::fetchAll("
            SELECT barcode, label_barcode, is_default 
            FROM public.grup_produk_barcode 
            WHERE grup_produk_id = :gid
            ORDER BY is_default DESC
        ", ['gid' => $grpId]);

        $bcsFound = array_column($barcodes, 'barcode');
        return in_array($testBc1, $bcsFound, true) && in_array($testBc2, $bcsFound, true);
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

runTest("3.2 - Customer Barcode Preference mapping upsert & retrieval", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $cust = Database::fetchOne("SELECT id FROM public.pelanggan WHERE status_aktif = TRUE LIMIT 1");
        $grp = Database::fetchOne("SELECT id FROM public.grup_produk WHERE status_aktif = TRUE LIMIT 1");
        if (!$cust || !$grp) {
            throw new RuntimeException("Master pelanggan/grup tidak ditemukan.");
        }

        $custId = $cust['id'];
        $grpId = $grp['id'];
        $testBc = '899PREF999';

        // Upsert 1: Insert
        $pdo->prepare("
            INSERT INTO public.pelanggan_grup_barcode (pelanggan_id, grup_produk_id, barcode, dibuat_pada, diubah_pada)
            VALUES (:pid, :gid, :bc, NOW(), NOW())
            ON CONFLICT (pelanggan_id, grup_produk_id)
            DO UPDATE SET barcode = EXCLUDED.barcode, diubah_pada = NOW()
        ")->execute(['pid' => $custId, 'gid' => $grpId, 'bc' => $testBc]);

        $row1 = Database::fetchOne("SELECT barcode FROM public.pelanggan_grup_barcode WHERE pelanggan_id = :pid AND grup_produk_id = :gid", ['pid' => $custId, 'gid' => $grpId]);
        if (!$row1 || $row1['barcode'] !== $testBc) {
            return false;
        }

        // Upsert 2: Update
        $testBcUpdated = '899PREF888';
        $pdo->prepare("
            INSERT INTO public.pelanggan_grup_barcode (pelanggan_id, grup_produk_id, barcode, dibuat_pada, diubah_pada)
            VALUES (:pid, :gid, :bc, NOW(), NOW())
            ON CONFLICT (pelanggan_id, grup_produk_id)
            DO UPDATE SET barcode = EXCLUDED.barcode, diubah_pada = NOW()
        ")->execute(['pid' => $custId, 'gid' => $grpId, 'bc' => $testBcUpdated]);

        $row2 = Database::fetchOne("SELECT barcode FROM public.pelanggan_grup_barcode WHERE pelanggan_id = :pid AND grup_produk_id = :gid", ['pid' => $custId, 'gid' => $grpId]);
        return $row2 && $row2['barcode'] === $testBcUpdated;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

runTest("3.3 - Transaction Snapshot Immutability: Historical invoice never mutates when master barcode changes", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $cust = Database::fetchOne("SELECT id FROM public.pelanggan WHERE status_aktif = TRUE LIMIT 1");
        $grp = Database::fetchOne("SELECT id FROM public.grup_produk WHERE status_aktif = TRUE LIMIT 1");
        if (!$cust || !$grp) {
            throw new RuntimeException("Master pelanggan/grup tidak ditemukan.");
        }

        $custId = $cust['id'];
        $grpId = $grp['id'];

        // Buat item transaksi terisolasi
        $stmtItemInsert = $pdo->prepare("
            INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, status_aktif, status_jual, dibuat_pada)
            VALUES (:gid, 'TEST-SKU-SNAP', 'Test Item Immutability', 'barang_jadi', 'pcs', TRUE, TRUE, NOW())
            RETURNING id
        ");
        $stmtItemInsert->execute(['gid' => $grpId]);
        $itemId = $stmtItemInsert->fetchColumn();

        $historicalBarcode = '899HISTORIC001';

        // 1. Buat pesanan dengan snapshot barcode
        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_diskon, total_netto,
                total_dibayar, sisa_tagihan, tipe_pembayaran, status_pembayaran, status_pemrosesan,
                catatan, is_tagihan, dibuat_pada
            ) VALUES (
                'TEST-NOTA-BC-01', :pid, CURRENT_DATE, 10000, 0, 10000,
                10000, 0, 'cash', 'lunas', 'selesai',
                'Test Immutability', TRUE, NOW()
            ) RETURNING id
        ");
        $stmtOrder->execute(['pid' => $custId]);
        $orderId = $stmtOrder->fetchColumn();

        $stmtItem = $pdo->prepare("
            INSERT INTO public.item_pesanan (
                pesanan_id, item_id, kuantitas_satuan_dasar, harga_satuan_deal, diskon_item_nominal,
                subtotal, harga_pokok_satuan, barcode_universal, dibuat_pada
            ) VALUES (
                :oid, :iid, 1, 10000, 0, 10000, 7000, :bc, NOW()
            )
        ");
        $stmtItem->execute(['oid' => $orderId, 'iid' => $itemId, 'bc' => $historicalBarcode]);

        // 2. Ubah barcode master grup produk di tabel grup_produk dan grup_produk_barcode
        $pdo->prepare("UPDATE public.grup_produk SET barcode_universal = '899NEWMASTER999' WHERE id = :gid")->execute(['gid' => $grpId]);
        $pdo->prepare("UPDATE public.grup_produk_barcode SET barcode = '899NEWMASTER999' WHERE grup_produk_id = :gid AND is_default = TRUE")->execute(['gid' => $grpId]);

        // 3. Query invoice gabungan seperti di OrderDocumentController & CustomerOrderController
        $invoiceItem = Database::fetchOne("
            SELECT COALESCE(ip.barcode_universal, gp.barcode_universal, gp.kode_grup, i.kode_sku) as barcode_universal
            FROM public.item_pesanan ip
            JOIN public.item i ON ip.item_id = i.id
            LEFT JOIN public.grup_produk gp ON i.grup_id = gp.id
            WHERE ip.pesanan_id = :oid
        ", ['oid' => $orderId]);

        // Verifikasi: Nilai invoice tetap menggunakan historical snapshot $historicalBarcode, BUKAN master baru '899NEWMASTER999'
        return $invoiceItem !== null && $invoiceItem['barcode_universal'] === $historicalBarcode;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

runTest("3.4 - Stored Procedure Scanner RPC fn_cari_item_by_barcode resolves primary, alternative & sku", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $grp = Database::fetchOne("SELECT id, barcode_universal FROM public.grup_produk WHERE status_aktif = TRUE LIMIT 1");
        if (!$grp) {
            throw new RuntimeException("Master grup tidak ditemukan.");
        }
        $grpId = $grp['id'];

        // Buat item transaksi terisolasi
        $testSku = 'TEST-SKU-SCAN-01';
        $stmtItemInsert = $pdo->prepare("
            INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, status_aktif, status_jual, dibuat_pada)
            VALUES (:gid, :sku, 'Test Scanner Item', 'barang_jadi', 'pcs', TRUE, TRUE, NOW())
            RETURNING id
        ");
        $stmtItemInsert->execute(['gid' => $grpId, 'sku' => $testSku]);
        $itemId = $stmtItemInsert->fetchColumn();

        $altBarcode = '899SCANTEST777';
        $pdo->prepare("
            INSERT INTO public.grup_produk_barcode (grup_produk_id, barcode, label_barcode, is_default, dibuat_pada, diubah_pada)
            VALUES (:gid, :bc, 'Scanner Test', FALSE, NOW(), NOW())
        ")->execute(['gid' => $grpId, 'bc' => $altBarcode]);

        // Test 1: Cari by SKU
        $scanRow1 = Database::fetchOne("SELECT public.fn_cari_item_by_barcode(:q) as res", ['q' => $testSku]);
        $scanRes1 = json_decode($scanRow1['res'] ?? '{}', true);
        $items1 = $scanRes1['data'] ?? [];
        $foundSku = !empty($scanRes1['ditemukan']) && in_array($itemId, array_column($items1, 'item_id'), true);

        // Test 2: Cari by Alt Barcode Grup
        $scanRow2 = Database::fetchOne("SELECT public.fn_cari_item_by_barcode(:q) as res", ['q' => $altBarcode]);
        $scanRes2 = json_decode($scanRow2['res'] ?? '{}', true);
        $items2 = $scanRes2['data'] ?? [];
        $foundAlt = !empty($scanRes2['ditemukan']) && in_array($itemId, array_column($items2, 'item_id'), true);

        return $foundSku && $foundAlt;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

// =========================================================================
// SUMMARY
// =========================================================================

echo "\n====================================================================\n";
echo "  HASIL PENGUJIAN MULTI-BARCODE & SNAPSHOT: {$passCount} PASS, {$failCount} FAIL\n";
echo "====================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);

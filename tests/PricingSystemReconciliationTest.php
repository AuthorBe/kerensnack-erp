<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/app/Core/Auth.php';

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn) {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "\n------------------------------------------------------------\n";
    echo "[TEST #{$totalTests}] {$title}...\n";
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo " [PASS] {$title}\n";
            $passed++;
        } else {
            echo " [FAIL] {$title}: " . (is_string($result) ? $result : 'Returned false') . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo " [ERROR] {$title}: " . $e->getMessage() . "\n";
        echo " Trace: " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

echo "============================================================\n";
echo " TEST SUITE: SISTEM PRICING, MASTER LEVEL 1-30 & MURNI PCS\n";
echo "============================================================\n";

$pdo = Database::getConnection();

// TEST 1: Verifikasi Tabel Master Level Harga (1 s/d 30)
runTest("1. Master Level Harga Terpusat Memiliki Tepat 30 Baris", function() {
    $count = (int)(Database::fetchOne("SELECT count(*) as total FROM public.master_level_harga")['total'] ?? 0);
    if ($count !== 30) {
        return "Expected 30 master levels, found {$count}";
    }
    $l1 = Database::fetchOne("SELECT nama_level FROM public.master_level_harga WHERE level_nomor = 1");
    if (!$l1 || !str_contains($l1['nama_level'], 'Ritel Standar')) {
        return "Level 1 is not configured as Ritel Standar: " . json_encode($l1);
    }
    return true;
});

// TEST 2: Verifikasi Kolom harga_jual_bal Benar-Benar Sudah Dihapus
runTest("2. Kolom harga_jual_bal Terhapus Tuntas dari grup_produk_harga_level", function() {
    $col = Database::fetchOne("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'grup_produk_harga_level' 
          AND column_name = 'harga_jual_bal'
    ");
    if ($col) {
        return "Kolom harga_jual_bal masih ada di database!";
    }
    return true;
});

// TEST 3: Verifikasi Foreign Key Constraints
runTest("3. Foreign Key Constraints ke master_level_harga Aktif", function() {
    $fks = Database::fetchAll("
        SELECT tc.table_name, kcu.column_name, ccu.table_name AS foreign_table_name, ccu.column_name AS foreign_column_name
        FROM information_schema.table_constraints AS tc 
        JOIN information_schema.key_column_usage AS kcu ON tc.constraint_name = kcu.constraint_name
        JOIN information_schema.constraint_column_usage AS ccu ON ccu.constraint_name = tc.constraint_name
        WHERE tc.constraint_type = 'FOREIGN KEY' AND ccu.table_name = 'master_level_harga'
    ");
    $fkTables = array_column($fks, 'table_name');
    if (!in_array('grup_produk_harga_level', $fkTables, true)) {
        return "FK dari grup_produk_harga_level ke master_level_harga tidak ditemukan.";
    }
    if (!in_array('grup_pelanggan', $fkTables, true)) {
        return "FK dari grup_pelanggan ke master_level_harga tidak ditemukan.";
    }
    return true;
});

// TEST 4: Verifikasi Pilihan B (Strict Rejection jika level belum diset di /pricing)
runTest("4. Pilihan B: fn_hitung_harga_jual_item Menolak Transaksi Jika Level Belum Diatur", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // Buat dummy grup produk tanpa level harga
        $stmtGrup = $pdo->prepare("
            INSERT INTO public.grup_produk (kode_grup, nama_grup)
            VALUES ('GRP-TEST-REJECT', 'Grup Test Pilihan B')
            RETURNING id
        ");
        $stmtGrup->execute();
        $grupId = $stmtGrup->fetchColumn();

        // Buat dummy item
        $stmtItem = $pdo->prepare("
            INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian)
            VALUES (:gid, 'SKU-TEST-REJECT', 'Item Uji Pilihan B', 'barang_jadi', 'pcs', 10000)
            RETURNING id
        ");
        $stmtItem->execute(['gid' => $grupId]);
        $itemId = $stmtItem->fetchColumn();

        // Panggil RPC tanpa pelanggan (default Level 1)
        // Karena grup ini BELUM punya Level 1, harus ditolak!
        $stmtRpc = $pdo->prepare("SELECT public.fn_hitung_harga_jual_item(:id, NULL) as res");
        $stmtRpc->execute(['id' => $itemId]);
        $res = json_decode($stmtRpc->fetchColumn(), true);

        $pdo->rollBack();

        if (empty($res['error']) || $res['error'] !== true) {
            return "Pilihan B Gagal: RPC tidak menolak item tanpa konfigurasi level! Res: " . json_encode($res);
        }
        if (($res['code'] ?? '') !== 'PRICE_LEVEL_NOT_CONFIGURED') {
            return "Pilihan B Gagal: Kode error tidak sesuai: " . json_encode($res);
        }

        echo "    (Respons Penolakan Resmi: {$res['message']})\n";
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

// TEST 5: Verifikasi Kalkulasi Normal Pcs pada Produk yang Terkonfigurasi
runTest("5. Kalkulasi Harga Pcs Normal Berjalan Konsisten Tanpa Bal", function() {
    $item = Database::fetchOne("SELECT id, nama_item FROM public.item WHERE status_aktif = TRUE AND tipe_item = 'barang_jadi' LIMIT 1");
    if (!$item) return "Tidak ada item aktif.";

    $res = Database::fetchOne("SELECT public.fn_hitung_harga_jual_item(:id, NULL) as res", ['id' => $item['id']]);
    $data = json_decode($res['res'] ?? '{}', true);

    if (!empty($data['error'])) {
        return "Item {$item['nama_item']} gagal dihitung: " . ($data['message'] ?? '');
    }
    if (!isset($data['harga_pcs_bruto']) || !isset($data['harga_pcs_netto'])) {
        return "Kunci harga_pcs tidak ditemukan pada respons: " . json_encode($data);
    }
    if (isset($data['harga_bal_bruto']) || isset($data['harga_bal_netto'])) {
        return "Respons RPC masih memuat harga_bal: " . json_encode($data);
    }

    echo "    (Item: {$item['nama_item']}, Harga Netto: Rp " . number_format($data['harga_pcs_netto'], 0, ',', '.') . " / pcs)\n";
    return true;
});

// TEST 6: Proteksi Anti-Duplikat Level di Database
runTest("6. Database Menolak Duplikasi Level Pada Grup Produk yang Sama", function() use ($pdo) {
    $grup = Database::fetchOne("SELECT id FROM public.grup_produk WHERE status_aktif = TRUE LIMIT 1");
    if (!$grup) return "Tidak ada grup produk.";

    // Pastikan Level 1 sudah ada
    $exists = Database::fetchOne("SELECT id FROM public.grup_produk_harga_level WHERE grup_produk_id = :gid AND level_harga = 1", ['gid' => $grup['id']]);
    if (!$exists) return "Grup tidak memiliki Level 1.";

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, harga_jual_pcs)
            VALUES (:gid, 1, 20000)
        ");
        $stmt->execute(['gid' => $grup['id']]);
        $pdo->rollBack();
        return "Database mengizinkan insert duplikat level 1!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        // Unique violation is code 23505
        if ($e->getCode() === '23505' || str_contains($e->getMessage(), 'uq_grup_harga_level')) {
            return true; // Sukses tertolak oleh DB!
        }
        throw $e;
    }
});

// TEST 7: Proteksi Hapus Level 1 & Level Aktif Grup Pelanggan di PricingController
runTest("7. PricingController Memiliki Proteksi Hapus Level 1 & Level Aktif Grup Pelanggan", function() {
    $ctrl = file_get_contents(APP_ROOT . '/app/Controllers/PricingController.php');
    if (!str_contains($ctrl, 'Level 1 (Ritel Standar) adalah harga dasar acuan')) {
        return "PricingController tidak memproteksi penghapusan Level 1.";
    }
    if (!str_contains($ctrl, 'sedang aktif digunakan oleh grup pelanggan')) {
        return "PricingController tidak memproteksi penghapusan level yang sedang aktif digunakan grup pelanggan.";
    }
    return true;
});

echo "\n============================================================\n";
echo " HASIL PENGUJIAN: {$passed} BERHASIL, {$failed} GAGAL (Total: {$totalTests})\n";
echo "============================================================\n";

exit($failed > 0 ? 1 : 0);

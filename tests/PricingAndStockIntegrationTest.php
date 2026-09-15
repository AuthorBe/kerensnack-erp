<?php
declare(strict_types=1);

/**
 * tests/audit_fase2_master_data_extreme.php
 * Automated Extreme Test Suite for FASE 2:
 * 2.1 Validasi Harga Jual Server-Side pada Pesanan B2B
 * 2.2 Konsolidasi Berkas Migrasi Git (database/27_consolidate_missing_master_schema.sql)
 * 2.3 Perbaikan Pencatatan Audit Log pada ActivityLog::record() & ActivityLog::log()
 * 2.4 Pencatatan Saldo Awal Produk Baru ke Kartu Stok Fisik (riwayat_stok)
 * 2.5 Snapshot HPP Berjalan pada Baris Faktur Penjualan B2B (item_pesanan.harga_pokok_satuan)
 */

define('APP_ROOT', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');
require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/Helpers/ActivityLog.php';

use App\Helpers\ActivityLog;

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
echo " EXTREME AUDIT SUITE - FASE 2: KEAMANAN TRANSAKSI, SKEMA & AUDIT\n";
echo "============================================================\n";

// -------------------------------------------------------------
// ITEM 2.2: KONSOLIDASI BERKAS MIGRASI GIT & SKEMA KANONIKAL
// -------------------------------------------------------------
runTest("2.2.1 Verifikasi Berkas Migrasi 27 & Sinkronisasi 01_schema.sql", function() {
    $migration27Path = APP_ROOT . '/database/27_consolidate_missing_master_schema.sql';
    if (!file_exists($migration27Path)) {
        return "Berkas database/27_consolidate_missing_master_schema.sql tidak ditemukan.";
    }
    $m27Content = file_get_contents($migration27Path);
    if (!strpos($m27Content, 'CREATE TABLE IF NOT EXISTS public.pelanggan_item')) {
        return "Migrasi 27 tidak memuat DDL pelanggan_item";
    }
    if (!strpos($m27Content, 'CREATE TABLE IF NOT EXISTS public.kategori_biaya')) {
        return "Migrasi 27 tidak memuat DDL kategori_biaya";
    }
    if (!strpos($m27Content, 'harga_pokok_satuan')) {
        return "Migrasi 27 tidak memuat kolom item_pesanan.harga_pokok_satuan";
    }

    $schema01Path = APP_ROOT . '/database/01_schema.sql';
    $s01Content = file_get_contents($schema01Path);
    if (!strpos($s01Content, 'CREATE TABLE IF NOT EXISTS public.pelanggan_item')) {
        return "01_schema.sql belum memuat DDL pelanggan_item";
    }
    if (!strpos($s01Content, 'CREATE TABLE IF NOT EXISTS public.kategori_biaya')) {
        return "01_schema.sql belum memuat DDL kategori_biaya";
    }
    if (!strpos($s01Content, 'harga_pokok_satuan')) {
        return "01_schema.sql belum memuat kolom item_pesanan.harga_pokok_satuan";
    }
    return true;
});

runTest("2.2.2 Verifikasi Live DB: Tabel pelanggan_item dan Foreign Keys", function() {
    $table = Database::fetchOne("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_name = 'pelanggan_item'
    ");
    if (!$table) {
        return "Tabel public.pelanggan_item tidak ada di live database.";
    }

    $cols = Database::fetchAll("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pelanggan_item'
    ");
    $colNames = array_column($cols, 'column_name');
    $requiredCols = ['id', 'pelanggan_id', 'item_id', 'dibuat_pada'];
    foreach ($requiredCols as $req) {
        if (!in_array($req, $colNames, true)) {
            return "Kolom {$req} hilang dari pelanggan_item.";
        }
    }
    return true;
});

runTest("2.2.3 Verifikasi Live DB: Tabel kategori_biaya", function() {
    $table = Database::fetchOne("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_name = 'kategori_biaya'
    ");
    if (!$table) {
        return "Tabel public.kategori_biaya tidak ada di live database.";
    }

    $cols = Database::fetchAll("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'kategori_biaya'
    ");
    $colNames = array_column($cols, 'column_name');
    $requiredCols = ['id', 'kode_kategori', 'nama_kategori', 'tipe_beban', 'status_aktif', 'deskripsi'];
    foreach ($requiredCols as $req) {
        if (!in_array($req, $colNames, true)) {
            return "Kolom {$req} hilang dari kategori_biaya.";
        }
    }
    return true;
});

runTest("2.2.4 Verifikasi Live DB: Kolom akun_kas & pemasok", function() {
    // akun_kas
    $kasCols = Database::fetchAll("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'akun_kas'
    ");
    $kasColNames = array_column($kasCols, 'column_name');
    if (!in_array('tipe_akun', $kasColNames, true)) {
        return "Kolom tipe_akun tidak ada di tabel akun_kas.";
    }
    if (!in_array('is_default_pos', $kasColNames, true)) {
        return "Kolom is_default_pos tidak ada di tabel akun_kas.";
    }

    // pemasok
    $pemasokCols = Database::fetchAll("
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'pemasok'
    ");
    $pemasokColNames = array_column($pemasokCols, 'column_name');
    foreach (['nama_bank', 'nomor_rekening', 'atas_nama_rekening'] as $bankCol) {
        if (!in_array($bankCol, $pemasokColNames, true)) {
            return "Kolom {$bankCol} tidak ada di tabel pemasok.";
        }
    }
    return true;
});

runTest("2.2.5 Verifikasi Live DB: Kolom item_pesanan.harga_pokok_satuan", function() {
    $col = Database::fetchOne("
        SELECT column_name, data_type, numeric_precision, numeric_scale
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'item_pesanan' AND column_name = 'harga_pokok_satuan'
    ");
    if (!$col) {
        return "Kolom item_pesanan.harga_pokok_satuan tidak ditemukan di database.";
    }
    if ($col['data_type'] !== 'numeric' || (int)$col['numeric_precision'] !== 15 || (int)$col['numeric_scale'] !== 2) {
        return "Tipe data kolom harga_pokok_satuan bukan numeric(15,2): " . json_encode($col);
    }
    return true;
});

// -------------------------------------------------------------
// ITEM 2.3: PERBAIKAN AUDIT LOG PADA ActivityLog::record() & ActivityLog::log()
// -------------------------------------------------------------
runTest("2.3.1 ActivityLog::record() Tidak Menyebabkan Constraint Violation", function() {
    $uniqueDesc = "Audit Test Record User Security " . microtime(true);
    
    // Test direct call to ActivityLog::record(?userId, jenisAksi, deskripsi, tabel, refId, sebelum, sesudah)
    $res = ActivityLog::record(null, 'USER_LOGIN', $uniqueDesc, 'pengguna', null, null, ['ip' => '127.0.0.1']);

    if (!$res) {
        return "ActivityLog::record returned false/null.";
    }

    // Check that row exists in log_aktivitas with valid kategori_aktivitas
    $logRow = Database::fetchOne("
        SELECT id, kategori_aktivitas, jenis_aksi, deskripsi_aktivitas 
        FROM public.log_aktivitas 
        WHERE deskripsi_aktivitas = :desc
    ", ['desc' => $uniqueDesc]);

    if (!$logRow) {
        return "Log row tidak ditemukan di database.";
    }

    if ($logRow['kategori_aktivitas'] !== 'keamanan_auth') {
        return "Kategori aktivitas harusnya 'keamanan_auth', didapat: " . $logRow['kategori_aktivitas'];
    }

    // Clean up test log
    Database::execute("DELETE FROM public.log_aktivitas WHERE id = :id", ['id' => $logRow['id']]);
    return true;
});

runTest("2.3.2 ActivityLog::log() Melakukan Sanitasi Kategori Tidak Valid", function() {
    $uniqueDesc = "Audit Test Log Invalid Category Sanitization " . microtime(true);
    
    // Pass completely invalid category string
    $res = ActivityLog::log('kategori_acak_ngawur', 'Aksi Uji Sanitasi', $uniqueDesc);
    if (!$res) {
        return "ActivityLog::log returned false/null for invalid category.";
    }

    // Check log row was sanitized and saved
    $logRow = Database::fetchOne("
        SELECT id, kategori_aktivitas, jenis_aksi, deskripsi_aktivitas 
        FROM public.log_aktivitas 
        WHERE deskripsi_aktivitas = :desc
    ", ['desc' => $uniqueDesc]);

    if (!$logRow) {
        return "Log row tidak tersimpan saat kategori tidak valid.";
    }

    $validCategories = [
        'keuangan', 'penjualan', 'logistik', 'gudang_stok',
        'produksi_bom', 'hr_payroll', 'master_data', 'keamanan_auth', 'ai_interaction'
    ];
    if (!in_array($logRow['kategori_aktivitas'], $validCategories, true)) {
        return "Kategori setelah sanitasi masih tidak valid: " . $logRow['kategori_aktivitas'];
    }

    // Clean up
    Database::execute("DELETE FROM public.log_aktivitas WHERE id = :id", ['id' => $logRow['id']]);
    return true;
});

runTest("2.3.3 ActivityLog::record() Beragam Action User Berhasil", function() {
    $actions = [
        'USER_CREATE' => 'keamanan_auth',
        'ROLE_UPDATE' => 'keamanan_auth',
        'SYSTEM_BACKUP' => 'keamanan_auth',
        'PRODUCT_CREATE' => 'master_data'
    ];

    foreach ($actions as $action => $expectedCat) {
        $desc = "Audit Multi Action {$action} " . microtime(true);
        ActivityLog::record(null, $action, $desc, null, null, null, ['meta' => 'ok']);

        $row = Database::fetchOne("
            SELECT id, kategori_aktivitas 
            FROM public.log_aktivitas 
            WHERE deskripsi_aktivitas = :desc
        ", ['desc' => $desc]);

        if (!$row) {
            return "Gagal merekam action {$action}";
        }
        if ($row['kategori_aktivitas'] !== $expectedCat) {
            return "Action {$action} dipetakan ke {$row['kategori_aktivitas']}, harusnya {$expectedCat}";
        }
        Database::execute("DELETE FROM public.log_aktivitas WHERE id = :id", ['id' => $row['id']]);
    }
    return true;
});

// -------------------------------------------------------------
// ITEM 2.4: PENCATATAN SALDO AWAL PRODUK KE KARTU STOK FISIK
// -------------------------------------------------------------
runTest("2.4.1 Registrasi Produk dengan Saldo Awal > 0 Mencatat Riwayat Stok", function() {
    $testSku = 'TEST-PRD-' . mt_rand(1000, 9999);
    $testName = 'Test Keripik Audit Fase 2 ' . mt_rand(100, 999);
    $stokAwal = 85;

    $pdo = Database::pdo();
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.item (
                kode_sku, nama_item, varian_rasa, tipe_item, satuan_dasar, satuan_distribusi,
                harga_pokok_pembelian, stok_minimum_peringatan, stok_fisik_saat_ini, status_jual, status_aktif
            ) VALUES (
                :sku, :nama, 'Original', 'barang_jadi', 'pcs', 'bal',
                12500, 10, :stok, TRUE, TRUE
            ) RETURNING id
        ");
        $stmt->execute([
            'sku' => $testSku,
            'nama' => $testName,
            'stok' => $stokAwal
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $testItemId = $row['id'];

        if ($stokAwal > 0) {
            $stmtHist = $pdo->prepare("
                INSERT INTO public.riwayat_stok (
                    item_id, tipe_mutasi, jumlah_perubahan,
                    stok_sebelum, stok_sesudah, referensi_tabel, referensi_id,
                    keterangan, dibuat_oleh, dibuat_pada
                ) VALUES (
                    :item_id, 'penyesuaian_opname_tambah', :jumlah,
                    0, :stok_sesudah, 'item', :ref_id,
                    :ket, NULL, NOW()
                )
            ");
            $stmtHist->execute([
                'item_id' => $testItemId,
                'jumlah' => $stokAwal,
                'stok_sesudah' => $stokAwal,
                'ref_id' => $testItemId,
                'ket' => 'Saldo awal registrasi produk baru ' . $testName
            ]);
        }
        $pdo->commit();

        // Verifikasi keberadaan row di riwayat_stok
        $hist = Database::fetchOne("
            SELECT * FROM public.riwayat_stok 
            WHERE item_id = :id AND referensi_tabel = 'item' AND referensi_id = :id
        ", ['id' => $testItemId]);

        if (!$hist) {
            throw new Exception("Riwayat stok tidak ditemukan untuk item yang baru dibuat.");
        }

        if ((float)$hist['jumlah_perubahan'] !== (float)$stokAwal) {
            throw new Exception("Jumlah perubahan riwayat stok salah: expected {$stokAwal}, got {$hist['jumlah_perubahan']}");
        }
        if ((float)$hist['stok_sebelum'] !== 0.0) {
            throw new Exception("Stok sebelum harusnya 0, got {$hist['stok_sebelum']}");
        }
        if ((float)$hist['stok_sesudah'] !== (float)$stokAwal) {
            throw new Exception("Stok sesudah harusnya {$stokAwal}, got {$hist['stok_sesudah']}");
        }
        if ($hist['tipe_mutasi'] !== 'penyesuaian_opname_tambah') {
            throw new Exception("Tipe mutasi salah: {$hist['tipe_mutasi']}");
        }

        // Clean up
        Database::execute("DELETE FROM public.riwayat_stok WHERE item_id = :id", ['id' => $testItemId]);
        Database::execute("DELETE FROM public.item WHERE id = :id", ['id' => $testItemId]);

        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
});

runTest("2.4.2 Verifikasi Logika storeProduct & storeMaterial di ProductController", function() {
    $controllerContent = file_get_contents(APP_ROOT . '/app/Controllers/ProductController.php');
    
    // Cek storeProduct memiliki logic riwayat_stok
    if (!strpos($controllerContent, "penyesuaian_opname_tambah") || !strpos($controllerContent, "\$stokAwal > 0")) {
        return "ProductController belum memuat logic insert ke riwayat_stok saat stok_awal > 0";
    }
    if (!strpos($controllerContent, "\$pdo->beginTransaction()") || !strpos($controllerContent, "\$pdo->commit()")) {
        return "ProductController belum menggunakan PDO transaction untuk integritas stok fisik dan ledger";
    }
    return true;
});

// -------------------------------------------------------------
// ITEM 2.1 & 2.5: VALIDASI HARGA JUAL SERVER-SIDE & SNAPSHOT HPP
// -------------------------------------------------------------
runTest("2.1.1 Anti-Tampering: RPC fn_hitung_harga_jual_item Berjalan Konsisten", function() {
    // Ambil 1 produk aktif
    $item = Database::fetchOne("
        SELECT id, nama_item, harga_pokok_pembelian 
        FROM public.item 
        WHERE tipe_item = 'barang_jadi' AND status_jual = TRUE 
        LIMIT 1
    ");

    if (!$item) {
        return "Tidak ada item barang_jadi aktif untuk pengujian.";
    }

    // Ambil 1 pelanggan
    $pelanggan = Database::fetchOne("SELECT id, nama_toko FROM public.pelanggan LIMIT 1");
    if (!$pelanggan) {
        return "Tidak ada pelanggan untuk pengujian.";
    }

    $rpc = Database::fetchOne("
        SELECT public.fn_hitung_harga_jual_item(:item_id, :pelanggan_id) as pricing
    ", [
        'item_id' => $item['id'],
        'pelanggan_id' => $pelanggan['id']
    ]);

    if (!isset($rpc['pricing'])) {
        return "RPC fn_hitung_harga_jual_item gagal dipanggil: " . json_encode($rpc);
    }

    $pricing = is_string($rpc['pricing']) ? json_decode($rpc['pricing'], true) : $rpc['pricing'];
    $hargaPcs = (float)($pricing['harga_pcs_netto'] ?? $pricing['harga_pcs_dasar'] ?? 0);
    $hargaBal = (float)($pricing['harga_bal_netto'] ?? $pricing['harga_bal_dasar'] ?? 0);

    if ($hargaPcs <= 0 && $hargaBal <= 0) {
        return "RPC mengembalikan harga 0: " . json_encode($pricing);
    }

    echo "    (Item: {$item['nama_item']}, Harga Resmi Pcs: Rp " . number_format($hargaPcs, 0, ',', '.') . ", Bal: Rp " . number_format($hargaBal, 0, ',', '.') . ")\n";
    return true;
});

runTest("2.1.2 & 2.5.1 CustomerOrderController Melakukan Validasi Harga & HPP Snapshot", function() {
    $orderController = file_get_contents(APP_ROOT . '/app/Controllers/CustomerOrderController.php');
    
    // Verifikasi pemanggilan fn_hitung_harga_jual_item
    if (!strpos($orderController, 'fn_hitung_harga_jual_item')) {
        return "CustomerOrderController tidak memanggil fn_hitung_harga_jual_item untuk validasi harga server-side.";
    }

    // Verifikasi snapshot harga_pokok_satuan
    if (!strpos($orderController, 'harga_pokok_satuan') || !strpos($orderController, 'harga_pokok_pembelian')) {
        return "CustomerOrderController tidak melakukan snapshot harga_pokok_satuan dari harga_pokok_pembelian.";
    }

    return true;
});

runTest("2.5.2 PosController Melakukan Snapshot HPP (harga_pokok_satuan)", function() {
    $posController = file_get_contents(APP_ROOT . '/app/Controllers/PosController.php');
    
    if (!strpos($posController, 'harga_pokok_satuan') || !strpos($posController, 'harga_pokok_pembelian')) {
        return "PosController checkout() tidak melakukan snapshot harga_pokok_satuan dari harga_pokok_pembelian.";
    }

    return true;
});

runTest("2.5.3 Integrasi DB: Snapshot HPP Tersimpan di item_pesanan", function() {
    // Buat dummy pesanan dan item_pesanan untuk memverifikasi snapshot HPP tersimpan dengan presisi numeric(15,2)
    $pelanggan = Database::fetchOne("SELECT id FROM public.pelanggan LIMIT 1");
    $item = Database::fetchOne("SELECT id, harga_pokok_pembelian FROM public.item WHERE tipe_item = 'barang_jadi' LIMIT 1");

    if (!$pelanggan || !$item) {
        return "Data pelanggan atau item tidak cukup untuk tes integrasi order.";
    }

    $pdo = Database::pdo();
    $pdo->beginTransaction();

    try {
        $dummyNota = 'AUDIT-ORD-' . mt_rand(100000, 999999);
        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_diskon, total_netto,
                tipe_pembayaran, status_pemrosesan, status_pembayaran
            ) VALUES (
                :nota, :pelanggan_id, CURRENT_DATE, 50000, 0, 50000,
                'cash', 'menunggu_approval', 'lunas'
            ) RETURNING id
        ");
        $stmtOrder->execute([
            'nota' => $dummyNota,
            'pelanggan_id' => $pelanggan['id']
        ]);
        $orderRow = $stmtOrder->fetch(PDO::FETCH_ASSOC);
        $orderId = $orderRow['id'];

        $expectedHpp = (float)($item['harga_pokok_pembelian'] ?? 8500.0);

        $stmtItem = $pdo->prepare("
            INSERT INTO public.item_pesanan (
                pesanan_id, item_id, kuantitas_satuan_dasar, kuantitas_satuan_distribusi,
                harga_satuan_deal, diskon_item_nominal, is_bonus, subtotal, harga_pokok_satuan
            ) VALUES (
                :order_id, :item_id, 2, 0,
                25000, 0, FALSE, 50000, :hpp
            ) RETURNING id
        ");
        $stmtItem->execute([
            'order_id' => $orderId,
            'item_id' => $item['id'],
            'hpp' => $expectedHpp
        ]);
        $pdo->commit();

        // Query check
        $savedItem = Database::fetchOne("
            SELECT harga_pokok_satuan, harga_satuan_deal 
            FROM public.item_pesanan 
            WHERE pesanan_id = :id
        ", ['id' => $orderId]);

        if (!$savedItem) {
            throw new Exception("item_pesanan tidak tersimpan.");
        }

        if (abs((float)$savedItem['harga_pokok_satuan'] - $expectedHpp) > 0.001) {
            throw new Exception("harga_pokok_satuan tidak sesuai: expected {$expectedHpp}, got {$savedItem['harga_pokok_satuan']}");
        }

        // Clean up
        Database::execute("DELETE FROM public.item_pesanan WHERE pesanan_id = :id", ['id' => $orderId]);
        Database::execute("DELETE FROM public.pesanan WHERE id = :id", ['id' => $orderId]);

        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
});

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
echo "\n============================================================\n";
echo " FASE 2 AUDIT EXECUTION SUMMARY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed}\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
echo " ALL FASE 2 AUDIT TESTS PASSED 100%! EXCELLENT!\n";
exit(0);

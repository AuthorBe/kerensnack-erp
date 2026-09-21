<?php
declare(strict_types=1);

/**
 * tests/audit_fase1_master_data_extreme.php
 * Automated Verification Test Suite: FASE 1 MASTER DATA & FINANCIAL INTEGRITY (P0)
 */

define('ROOT_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');
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
echo "  AUDIT EKSTREM FASE 1: MASTER DATA & INTEGRITAS FINANSIAL (P0)\n";
echo "====================================================================\n\n";

$pdo = Database::getConnection();

// ------------------------------------------------------------------
// 1. BARCODE INTEGRITY TESTS
// ------------------------------------------------------------------
runTest("1.1.1 - Database: Tidak ada barcode notasi ilmiah di public.grup_produk", function() use ($pdo) {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM public.grup_produk WHERE barcode_universal ~* 'e' OR barcode_universal ~* '\\.0'")->fetchColumn();
    return $count === 0;
});

runTest("1.1.2 - Database: Kolom barcode pada public.item telah dibersihkan/dihapus tuntas", function() use ($pdo) {
    $hasCol = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = 'public' AND table_name = 'item' AND column_name = 'barcode'")->fetchColumn();
    return $hasCol === 0;
});

runTest("1.1.3 - Stored Procedure fn_cari_item_by_barcode mengenali barcode hasil normalisasi (88026176)", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $gpStmt = $pdo->prepare("
            INSERT INTO public.grup_produk (kode_grup, nama_grup, barcode_universal)
            VALUES ('GRP-TEST-88026176', 'Grup Test Barcode Normal', '88026176')
            RETURNING id
        ");
        $gpStmt->execute();
        $gpId = $gpStmt->fetchColumn();

        $itemStmt = $pdo->prepare("
            INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian)
            VALUES (:gp_id, 'SKU-TEST-88026176', 'Item Test Barcode', 'barang_jadi', 'pcs', 10000)
        ");
        $itemStmt->execute(['gp_id' => $gpId]);

        $stmt = $pdo->prepare("SELECT public.fn_cari_item_by_barcode('88026176') as result");
        $stmt->execute();
        $res = json_decode($stmt->fetchColumn(), true);
        $pdo->rollBack();
        return !empty($res['ditemukan']) && $res['ditemukan'] === true && $res['total_varian'] > 0;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
});

runTest("1.1.4 - Stored Procedure fn_cari_item_by_barcode mengenali barcode tempe (888025056)", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $gpStmt = $pdo->prepare("
            INSERT INTO public.grup_produk (kode_grup, nama_grup, barcode_universal)
            VALUES ('GRP-TEST-888025056', 'Grup Test Barcode Tempe', '888025056')
            RETURNING id
        ");
        $gpStmt->execute();
        $gpId = $gpStmt->fetchColumn();

        $itemStmt = $pdo->prepare("
            INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian)
            VALUES (:gp_id, 'SKU-TEST-888025056', 'Item Test Barcode Tempe', 'barang_jadi', 'pcs', 10000)
        ");
        $itemStmt->execute(['gp_id' => $gpId]);

        $stmt = $pdo->prepare("SELECT public.fn_cari_item_by_barcode('888025056') as result");
        $stmt->execute();
        $res = json_decode($stmt->fetchColumn(), true);
        $pdo->rollBack();
        return !empty($res['ditemukan']) && $res['ditemukan'] === true && $res['total_varian'] > 0;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
});

runTest("1.1.5 - Seed File 03_seed_master_produk.sql bersih dari notasi ilmiah", function() use ($pdo) {
    $seedFile = ROOT_PATH . '/database/seeds/03_seed_master_produk.sql';
    if (file_exists($seedFile)) {
        $content = file_get_contents($seedFile);
        if (preg_match("/'[0-9]+\\.[0-9]+[eE][0-9]+'/", (string)$content)) {
            return "03_seed_master_produk.sql masih memuat kode barcode dengan notasi ilmiah.";
        }
    }
    // Fallback verifikasi langsung ke database
    $count = (int)$pdo->query("SELECT COUNT(*) FROM public.grup_produk WHERE barcode_universal ~* '^[0-9]+\\.[0-9]+[eE][0-9]+$'")->fetchColumn();
    if ($count > 0) {
        return "Ditemukan kode barcode universal dengan format notasi ilmiah di database grup_produk.";
    }
    return true;
});

// ------------------------------------------------------------------
// 2. ARUS KAS CONSTRAINT TESTS
// ------------------------------------------------------------------
runTest("1.2.1 - Database: arus_kas_kategori_check telah dilepas", function() use ($pdo) {
    $cnt = (int)$pdo->query("SELECT COUNT(*) FROM pg_constraint WHERE conrelid = 'public.arus_kas'::regclass AND conname = 'arus_kas_kategori_check'")->fetchColumn();
    return $cnt === 0;
});

runTest("1.2.2 - Database: arus_kas_jenis_kas_check mendukung transfer_masuk dan transfer_keluar", function() use ($pdo) {
    $def = $pdo->query("SELECT pg_get_constraintdef(oid) FROM pg_constraint WHERE conrelid = 'public.arus_kas'::regclass AND conname = 'arus_kas_jenis_kas_check'")->fetchColumn();
    return str_contains($def, 'transfer_masuk') && str_contains($def, 'transfer_keluar');
});

runTest("1.2.3 - Transaksi: Insert arus_kas dengan transfer_masuk & transfer_keluar berhasil", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $accId = $pdo->query("SELECT id FROM public.akun_kas LIMIT 1")->fetchColumn();
        if (!$accId) {
            $accStmt = $pdo->prepare("INSERT INTO public.akun_kas (nama_akun, tipe, nomor_rekening, saldo_mengendap) VALUES ('Kas Test MD', 'kas_tunai', 'KAS-TEST', 0) RETURNING id");
            $accStmt->execute();
            $accId = $accStmt->fetchColumn();
        }

        // Test transfer_keluar
        $stmtOut = $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, saldo_berjalan, dibuat_pada
            ) VALUES (
                :acc, CURRENT_DATE, 'transfer_keluar', 'Transfer Antar Kas', 50000,
                'Test transfer keluar', 100000, NOW()
            )
        ");
        $stmtOut->execute(['acc' => $accId]);

        // Test transfer_masuk
        $stmtIn = $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, saldo_berjalan, dibuat_pada
            ) VALUES (
                :acc, CURRENT_DATE, 'transfer_masuk', 'Transfer Antar Kas', 50000,
                'Test transfer masuk', 150000, NOW()
            )
        ");
        $stmtIn->execute(['acc' => $accId]);

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
});

runTest("1.2.4 - Transaksi: Insert arus_kas dengan kategori dinamis (modal_awal, koreksi, listrik) berhasil", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $accId = $pdo->query("SELECT id FROM public.akun_kas LIMIT 1")->fetchColumn();
        if (!$accId) {
            $accStmt = $pdo->prepare("INSERT INTO public.akun_kas (nama_akun, tipe, nomor_rekening, saldo_mengendap) VALUES ('Kas Test MD', 'kas_tunai', 'KAS-TEST', 0) RETURNING id");
            $accStmt->execute();
            $accId = $accStmt->fetchColumn();
        }

        // Test modal_awal
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, saldo_berjalan, dibuat_pada
            ) VALUES (
                :acc, CURRENT_DATE, 'masuk', 'modal_awal', 1000000,
                'Saldo Awal Pembukaan Akun', 1000000, NOW()
            )
        ")->execute(['acc' => $accId]);

        // Test koreksi
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, saldo_berjalan, dibuat_pada
            ) VALUES (
                :acc, CURRENT_DATE, 'keluar', 'koreksi', 25000,
                'Koreksi Pesanan', 975000, NOW()
            )
        ")->execute(['acc' => $accId]);

        // Test kategori custom bebas
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, saldo_berjalan, dibuat_pada
            ) VALUES (
                :acc, CURRENT_DATE, 'keluar', 'Listrik & WiFi Toko', 350000,
                'Tagihan Bulanan', 625000, NOW()
            )
        ")->execute(['acc' => $accId]);

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 3. STORED PROCEDURE fn_hitung_harga_jual_item PILIHAN B STRICT REJECTION
// ------------------------------------------------------------------
runTest("1.3.1 - Stored Procedure: Pilihan B menolak transaksi jika harga level belum diset di /pricing", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // Buat dummy grup produk tanpa level harga dan dummy item
        $gpStmt = $pdo->prepare("
            INSERT INTO public.grup_produk (kode_grup, nama_grup)
            VALUES ('GRP-TEST-P0', 'Grup Test P0 Fallback')
            RETURNING id
        ");
        $gpStmt->execute();
        $gpId = $gpStmt->fetchColumn();

        $itemStmt = $pdo->prepare("
            INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian)
            VALUES (:gp_id, 'SKU-TEST-P0', 'Item Test Fallback P0', 'barang_jadi', 'pcs', 12500.00)
            RETURNING id
        ");
        $itemStmt->execute(['gp_id' => $gpId]);
        $itemId = $itemStmt->fetchColumn();

        // Cari atau buat pelanggan
        $pelangganId = $pdo->query("SELECT id FROM public.pelanggan LIMIT 1")->fetchColumn();
        if (!$pelangganId) {
            $pelStmt = $pdo->prepare("INSERT INTO public.pelanggan (nama_pelanggan, telepon, kategori_pelanggan) VALUES ('Customer Test MD', '0812345678', 'b2b_silver') RETURNING id");
            $pelStmt->execute();
            $pelangganId = $pelStmt->fetchColumn();
        }

        // Panggil fn_hitung_harga_jual_item
        $call = $pdo->prepare("SELECT public.fn_hitung_harga_jual_item(:item_id, :pel_id) as res");
        $call->execute(['item_id' => $itemId, 'pel_id' => $pelangganId]);
        $pricing = json_decode($call->fetchColumn(), true);

        $pdo->rollBack();

        // Assert: Pilihan B mengembalikan error = true dan kode PRICE_LEVEL_NOT_CONFIGURED
        if (empty($pricing['error']) || $pricing['error'] !== true) {
            echo "[Expected error=true, Got " . json_encode($pricing) . "] ";
            return false;
        }

        if (($pricing['code'] ?? '') !== 'PRICE_LEVEL_NOT_CONFIGURED') {
            echo "[Expected PRICE_LEVEL_NOT_CONFIGURED, Got " . ($pricing['code'] ?? '') . "] ";
            return false;
        }

        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
});

// ------------------------------------------------------------------
// 4. RINCIAN PEMBELIAN DECIMAL QUANTITY TESTS
// ------------------------------------------------------------------
runTest("1.4.1 - Database: rincian_pembelian.kuantitas bertipe numeric(15,2)", function() use ($pdo) {
    $col = $pdo->query("
        SELECT data_type, numeric_precision, numeric_scale 
        FROM information_schema.columns 
        WHERE table_name = 'rincian_pembelian' AND column_name = 'kuantitas'
    ")->fetch();
    return $col['data_type'] === 'numeric' && (int)$col['numeric_precision'] === 15 && (int)$col['numeric_scale'] === 2;
});

runTest("1.4.2 - Transaksi: Insert rincian_pembelian dengan kuantitas desimal (12.75) berhasil dan presisi utuh", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $pemasokId = $pdo->query("SELECT id FROM public.pemasok LIMIT 1")->fetchColumn();
        if (!$pemasokId) {
            $pemStmt = $pdo->prepare("INSERT INTO public.pemasok (kode_pemasok, nama_pemasok, nama_kontak, nomor_whatsapp) VALUES ('SUP-TEST-DEC', 'Pemasok Test Decimal', 'Budi', '08123456789') RETURNING id");
            $pemStmt->execute();
            $pemasokId = $pemStmt->fetchColumn();
        }

        $itemId = $pdo->query("SELECT id FROM public.item LIMIT 1")->fetchColumn();
        if (!$itemId) {
            $gpStmt = $pdo->prepare("INSERT INTO public.grup_produk (kode_grup, nama_grup) VALUES ('GRP-TEST-DEC', 'Grup Test Decimal') RETURNING id");
            $gpStmt->execute();
            $gpId = $gpStmt->fetchColumn();

            $itStmt = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian) VALUES (:gp_id, 'SKU-TEST-DEC', 'Item Test Decimal', 'bahan_mentah', 'kg', 25000) RETURNING id");
            $itStmt->execute(['gp_id' => $gpId]);
            $itemId = $itStmt->fetchColumn();
        }

        $pbStmt = $pdo->prepare("
            INSERT INTO public.pembelian (nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya, status_pembayaran, jenis_dokumen)
            VALUES ('TEST-PO-DESIMAL-001', :pemasok_id, CURRENT_DATE, 318750.00, 'belum_lunas', 'po')
            RETURNING id
        ");
        $pbStmt->execute(['pemasok_id' => $pemasokId]);
        $pbId = $pbStmt->fetchColumn();

        $rinStmt = $pdo->prepare("
            INSERT INTO public.rincian_pembelian (pembelian_id, item_id, kuantitas, satuan, harga_satuan, subtotal)
            VALUES (:pb_id, :item_id, 12.75, 'kg', 25000.00, 318750.00)
            RETURNING kuantitas
        ");
        $rinStmt->execute(['pb_id' => $pbId, 'item_id' => $itemId]);
        $savedQty = (float)$rinStmt->fetchColumn();

        $pdo->rollBack();
        return abs($savedQty - 12.75) < 0.001;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
});

runTest("1.4.3 - Frontend: views/purchases/index.php quantity input mendukung desimal (step=any min=0.01)", function() {
    $content = file_get_contents(ROOT_PATH . '/views/purchases/index.php');
    // Memastikan tidak ada lagi step="1" atau min="1" pada input kuantitas pembelian
    $hasOldStep1 = preg_match('/class="[^"]*purchase-item-qty[^"]*"[^>]*>[\s\S]*?<input[^>]*step="1"/', $content);
    $hasNewStepAny = str_contains($content, 'step="any" min="0.01"');
    return !$hasOldStep1 && $hasNewStepAny;
});

echo "\n====================================================================\n";
echo "HASIL AKHIR AUDIT FASE 1: {$passed} LULUS, {$failed} GAGAL\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);

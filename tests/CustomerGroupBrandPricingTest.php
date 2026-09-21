<?php
declare(strict_types=1);

/**
 * tests/CustomerGroupBrandPricingTest.php
 * Keren Snack ERP - Automated Test Suite: Dynamic Brand Price Levels & Brand Discounts
 * 
 * Verifikasi Mendalam:
 * 1. Skema & Integritas Tabel grup_pelanggan_level_merek
 * 2. Auto-sync Trigger saat grup pelanggan / merek baru dibuat
 * 3. RPC fn_hitung_harga_jual_item: Multi-brand resolution & BRAND_NOT_ALLOWED
 * 4. Perhitungan diskon per merek (% dan nominal Rp/pcs)
 * 5. Pelanggan Umum / Walk-in Cash resolution
 * 6. Impor Data Grup Pelanggan multi-kolom merek
 * 
 * PROTOKOL AGENTS.MD: Seluruh pengujian berjalan di dalam transaksi terisolasi dengan auto-rollback.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/Services/Import/SmartReader.php';
require_once __DIR__ . '/../app/Services/Import/Handlers/EntityImportHandlerInterface.php';
require_once __DIR__ . '/../app/Services/Import/Handlers/CustomerGroupImportHandler.php';

function runCustomerGroupBrandPricingTests(): array {
    $pdo = Database::getConnection();
    $results = [];

    // TEST 1: Integritas Struktur Tabel & Relasi grup_pelanggan_level_merek
    $t1Start = microtime(true);
    try {
        $tableCheck = $pdo->query("
            SELECT column_name, data_type 
            FROM information_schema.columns 
            WHERE table_schema = 'public' AND table_name = 'grup_pelanggan_level_merek'
        ")->fetchAll(PDO::FETCH_KEY_PAIR);

        $requiredCols = ['grup_pelanggan_id', 'merek_id', 'level_harga', 'diskon_persen', 'diskon_nominal', 'is_dijual'];
        foreach ($requiredCols as $col) {
            if (!isset($tableCheck[$col])) {
                throw new Exception("Kolom '{$col}' tidak ditemukan pada tabel grup_pelanggan_level_merek.");
            }
        }
        $results[] = ['name' => '1. Verifikasi Struktur Tabel grup_pelanggan_level_merek', 'status' => 'PASSED', 'duration' => microtime(true) - $t1Start];
    } catch (Throwable $e) {
        $results[] = ['name' => '1. Verifikasi Struktur Tabel grup_pelanggan_level_merek', 'status' => 'FAILED', 'error' => $e->getMessage(), 'duration' => microtime(true) - $t1Start];
    }

    // TEST 2: Auto-Trigger saat Pembuatan Grup Pelanggan Baru
    $t2Start = microtime(true);
    $pdo->beginTransaction();
    try {
        $merekKrn = $pdo->query("SELECT id FROM public.merek WHERE kode_merek = 'KRN' LIMIT 1")->fetchColumn();
        if (!$merekKrn) {
            $merekKrn = $pdo->query("INSERT INTO public.merek (kode_merek, nama_merek) VALUES ('KRN', 'KEREN SNACK') RETURNING id")->fetchColumn();
        }

        $testGroupStmt = $pdo->prepare("
            INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, default_level_harga, diskon_persen_default, diskon_nominal_default)
            VALUES ('GRP-TEST-BRAND', 'Grup Uji Merek Dinamis', 10, 5.0, 500)
            RETURNING id
        ");
        $testGroupStmt->execute();
        $testGroupId = $testGroupStmt->fetchColumn();

        // Verifikasi trigger otomatis membuat relasi di grup_pelanggan_level_merek
        $gplmRow = $pdo->query("
            SELECT level_harga, diskon_persen, diskon_nominal, is_dijual 
            FROM public.grup_pelanggan_level_merek 
            WHERE grup_pelanggan_id = '{$testGroupId}' AND merek_id = '{$merekKrn}'
        ")->fetch(PDO::FETCH_ASSOC);

        if (!$gplmRow) {
            throw new Exception("Trigger trg_grup_pelanggan_after_insert gagal menginisialisasi baris di grup_pelanggan_level_merek.");
        }
        if ((int)$gplmRow['level_harga'] !== 10 || (float)$gplmRow['diskon_persen'] != 5.0 || !$gplmRow['is_dijual']) {
            throw new Exception("Data level harga / diskon inisialisasi trigger tidak sesuai.");
        }

        $results[] = ['name' => '2. Auto-Trigger Inisialisasi Merek Grup Pelanggan', 'status' => 'PASSED', 'duration' => microtime(true) - $t2Start];
    } catch (Throwable $e) {
        $results[] = ['name' => '2. Auto-Trigger Inisialisasi Merek Grup Pelanggan', 'status' => 'FAILED', 'error' => $e->getMessage(), 'duration' => microtime(true) - $t2Start];
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    // TEST 3: RPC fn_hitung_harga_jual_item Multi-Brand & BRAND_NOT_ALLOWED
    $t3Start = microtime(true);
    $pdo->beginTransaction();
    try {
        // 1. Buat 2 Merek: Merek A (Diizinkan, Lvl 18) & Merek B (Tidak Dijual)
        $merekA = $pdo->query("INSERT INTO public.merek (kode_merek, nama_merek) VALUES ('MRK-A', 'Merek A Test') RETURNING id")->fetchColumn();
        $merekB = $pdo->query("INSERT INTO public.merek (kode_merek, nama_merek) VALUES ('MRK-B', 'Merek B Test') RETURNING id")->fetchColumn();

        // 2. Buat 2 Grup Produk & Item
        $gpA = $pdo->query("INSERT INTO public.grup_produk (kode_grup, nama_grup, merek_id) VALUES ('GP-A', 'Grup Produk A', '{$merekA}') RETURNING id")->fetchColumn();
        $gpB = $pdo->query("INSERT INTO public.grup_produk (kode_grup, nama_grup, merek_id) VALUES ('GP-B', 'Grup Produk B', '{$merekB}') RETURNING id")->fetchColumn();

        // 3. Set Matriks Harga
        $pdo->exec("INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, harga_jual_pcs) VALUES ('{$gpA}', 18, 14800)");
        $pdo->exec("INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, harga_jual_pcs) VALUES ('{$gpB}', 18, 25000)");

        $itemA = $pdo->query("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar) VALUES ('{$gpA}', 'SKU-A', 'Item Merek A', 'barang_jadi', 'pcs') RETURNING id")->fetchColumn();
        $itemB = $pdo->query("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar) VALUES ('{$gpB}', 'SKU-B', 'Item Merek B', 'barang_jadi', 'pcs') RETURNING id")->fetchColumn();

        // 4. Buat Grup Pelanggan & Pelanggan
        $grpId = $pdo->query("INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, default_level_harga) VALUES ('GRP-MULTI', 'Grup Multi Brand', 1) RETURNING id")->fetchColumn();
        $custId = $pdo->query("INSERT INTO public.pelanggan (kode_pelanggan, nama_toko, grup_pelanggan_id, alamat_lengkap) VALUES ('PEL-MB', 'Toko Multi Brand', '{$grpId}', 'Jl. Test Multi') RETURNING id")->fetchColumn();

        // 5. Konfigurasi: Merek A = Level 18 (Dijual), Merek B = Tidak Dijual
        $pdo->exec("
            INSERT INTO public.grup_pelanggan_level_merek (grup_pelanggan_id, merek_id, level_harga, diskon_persen, diskon_nominal, is_dijual)
            VALUES 
                ('{$grpId}', '{$merekA}', 18, 0.0, 0.0, TRUE),
                ('{$grpId}', '{$merekB}', NULL, 0.0, 0.0, FALSE)
            ON CONFLICT (grup_pelanggan_id, merek_id) DO UPDATE SET
                level_harga = EXCLUDED.level_harga,
                is_dijual = EXCLUDED.is_dijual
        ");

        // 6. Test RPC untuk Item A (Wajib Sukses Level 18 = 14800)
        $resA_raw = $pdo->query("SELECT public.fn_hitung_harga_jual_item('{$itemA}', '{$custId}')")->fetchColumn();
        $resA = json_decode((string)$resA_raw, true);

        if (!empty($resA['error']) || (float)($resA['harga_pcs_netto'] ?? 0) !== 14800.0) {
            throw new Exception("RPC gagal menghitung harga Merek A: " . json_encode($resA));
        }

        // 7. Test RPC untuk Item B (Wajib Ditolak BRAND_NOT_ALLOWED)
        $resB_raw = $pdo->query("SELECT public.fn_hitung_harga_jual_item('{$itemB}', '{$custId}')")->fetchColumn();
        $resB = json_decode((string)$resB_raw, true);

        if (empty($resB['error']) || ($resB['code'] ?? '') !== 'BRAND_NOT_ALLOWED') {
            throw new Exception("RPC harusnya menolak item dari Merek B (Tidak Dijual), tetapi menghasilkan: " . json_encode($resB));
        }

        $results[] = ['name' => '3. RPC Multi-Brand Pricing & Proteksi BRAND_NOT_ALLOWED', 'status' => 'PASSED', 'duration' => microtime(true) - $t3Start];
    } catch (Throwable $e) {
        $results[] = ['name' => '3. RPC Multi-Brand Pricing & Proteksi BRAND_NOT_ALLOWED', 'status' => 'FAILED', 'error' => $e->getMessage(), 'duration' => microtime(true) - $t3Start];
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    // TEST 4: Perhitungan Diskon per Merek (% dan Nominal Rp/pcs)
    $t4Start = microtime(true);
    $pdo->beginTransaction();
    try {
        $merekDisc = $pdo->query("INSERT INTO public.merek (kode_merek, nama_merek) VALUES ('MRK-D', 'Merek Diskon') RETURNING id")->fetchColumn();
        $gpDisc = $pdo->query("INSERT INTO public.grup_produk (kode_grup, nama_grup, merek_id) VALUES ('GP-D', 'Grup Produk Diskon', '{$merekDisc}') RETURNING id")->fetchColumn();
        $pdo->exec("INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, harga_jual_pcs) VALUES ('{$gpDisc}', 5, 20000)");
        $itemDisc = $pdo->query("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar) VALUES ('{$gpDisc}', 'SKU-D', 'Item Diskon', 'barang_jadi', 'pcs') RETURNING id")->fetchColumn();

        $grpDiscId = $pdo->query("INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, default_level_harga) VALUES ('GRP-DISC', 'Grup Pelanggan Diskon', 1) RETURNING id")->fetchColumn();
        $custDiscId = $pdo->query("INSERT INTO public.pelanggan (kode_pelanggan, nama_toko, grup_pelanggan_id, alamat_lengkap) VALUES ('PEL-DISC', 'Toko Diskon Merek', '{$grpDiscId}', 'Jl. Diskon') RETURNING id")->fetchColumn();

        // Atur Diskon: 10% + Rp 500/pcs pada Level 5 (Harga Bruto 20.000 -> 20.000 - 2.000 - 500 = 17.500)
        $pdo->exec("
            INSERT INTO public.grup_pelanggan_level_merek (grup_pelanggan_id, merek_id, level_harga, diskon_persen, diskon_nominal, is_dijual)
            VALUES ('{$grpDiscId}', '{$merekDisc}', 5, 10.0, 500.0, TRUE)
            ON CONFLICT (grup_pelanggan_id, merek_id) DO UPDATE SET
                level_harga = EXCLUDED.level_harga,
                diskon_persen = EXCLUDED.diskon_persen,
                diskon_nominal = EXCLUDED.diskon_nominal,
                is_dijual = TRUE
        ");

        $resDiscRaw = $pdo->query("SELECT public.fn_hitung_harga_jual_item('{$itemDisc}', '{$custDiscId}')")->fetchColumn();
        $resDisc = json_decode((string)$resDiscRaw, true);

        if (!empty($resDisc['error'])) {
            throw new Exception("RPC gagal menghitung diskon: " . json_encode($resDisc));
        }
        if ((float)$resDisc['harga_pcs_bruto'] !== 20000.0 || (float)$resDisc['harga_pcs_netto'] !== 17500.0) {
            throw new Exception("Perhitungan harga netto diskon salah. Diharapkan 17.500, didapat: " . ($resDisc['harga_pcs_netto'] ?? 'null'));
        }

        $results[] = ['name' => '4. Perhitungan Diskon per Merek (% dan Nominal)', 'status' => 'PASSED', 'duration' => microtime(true) - $t4Start];
    } catch (Throwable $e) {
        $results[] = ['name' => '4. Perhitungan Diskon per Merek (% dan Nominal)', 'status' => 'FAILED', 'error' => $e->getMessage(), 'duration' => microtime(true) - $t4Start];
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    // TEST 5: Pelanggan Umum / Walk-in Cash Resolusi Level 1
    $t5Start = microtime(true);
    $pdo->beginTransaction();
    try {
        $merekUmum = $pdo->query("SELECT id FROM public.merek WHERE status_aktif = TRUE LIMIT 1")->fetchColumn();
        $itemUmum = $pdo->query("
            SELECT i.id 
            FROM public.item i
            JOIN public.grup_produk gp ON i.grup_id = gp.id
            JOIN public.grup_produk_harga_level gphl ON gphl.grup_produk_id = gp.id AND gphl.level_harga = 1
            WHERE gp.merek_id = '{$merekUmum}' AND i.tipe_item = 'barang_jadi' AND i.status_aktif = TRUE
            LIMIT 1
        ")->fetchColumn();

        if ($itemUmum) {
            // Panggil RPC dengan p_pelanggan_id = NULL
            $resUmumRaw = $pdo->query("SELECT public.fn_hitung_harga_jual_item('{$itemUmum}', NULL)")->fetchColumn();
            $resUmum = json_decode((string)$resUmumRaw, true);

            if (!empty($resUmum['error'])) {
                throw new Exception("RPC gagal untuk Pelanggan Umum: " . json_encode($resUmum));
            }
            if ((int)$resUmum['level_harga'] !== 1) {
                throw new Exception("Level harga Pelanggan Umum harus Level 1, didapat: " . ($resUmum['level_harga'] ?? 'null'));
            }
        }

        $results[] = ['name' => '5. Resolusi Harga Pelanggan Umum (Walk-in Cash)', 'status' => 'PASSED', 'duration' => microtime(true) - $t5Start];
    } catch (Throwable $e) {
        $results[] = ['name' => '5. Resolusi Harga Pelanggan Umum (Walk-in Cash)', 'status' => 'FAILED', 'error' => $e->getMessage(), 'duration' => microtime(true) - $t5Start];
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    // TEST 6: CustomerGroupImportHandler Multi-Brand Preview & Apply
    $t6Start = microtime(true);
    $pdo->beginTransaction();
    try {
        $handler = new \App\Services\Import\Handlers\CustomerGroupImportHandler();
        $header = ['Kode Grup', 'Nama Grup', 'Level Keren Snack', 'Diskon % Keren Snack', 'Status Aktif'];
        $rows = [
            ['GRP-IMP-01', 'Grup Import 01', '18', '5', 'Aktif'],
            ['GRP-IMP-02', 'Grup Import 02', 'Tidak dijual', '0', 'Aktif']
        ];

        $preview = $handler->previewRows($rows, $header, $pdo, 'append_update');
        if (count($preview) !== 2 || $preview[0]['action'] !== 'INSERT' || $preview[1]['action'] !== 'INSERT') {
            throw new Exception("Preview import gagal memetakan baris multi-merek: " . json_encode($preview));
        }

        $syncRes = $handler->applySync($preview, $pdo);
        if ($syncRes['insert'] !== 2) {
            throw new Exception("Apply sync import gagal insert 2 grup: " . json_encode($syncRes));
        }

        $results[] = ['name' => '6. Impor Data Grup Pelanggan Multi-Kolom Merek', 'status' => 'PASSED', 'duration' => microtime(true) - $t6Start];
    } catch (Throwable $e) {
        $results[] = ['name' => '6. Impor Data Grup Pelanggan Multi-Kolom Merek', 'status' => 'FAILED', 'error' => $e->getMessage(), 'duration' => microtime(true) - $t6Start];
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }

    return $results;
}

// CLI Execution Wrapper
if (PHP_SAPI === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "====================================================================\n";
    echo " TEST: CustomerGroupBrandPricingTest.php (Dynamic Brand Price Levels)\n";
    echo "====================================================================\n";
    $testResults = runCustomerGroupBrandPricingTests();
    $allPassed = true;
    foreach ($testResults as $res) {
        $statusIcon = $res['status'] === 'PASSED' ? '✅' : '❌';
        $timeStr = number_format($res['duration'], 3) . 's';
        echo "{$statusIcon} {$res['name']} ({$timeStr})\n";
        if ($res['status'] !== 'PASSED') {
            echo "   Error: {$res['error']}\n";
            $allPassed = false;
        }
    }
    echo "====================================================================\n";
    if ($allPassed) {
        echo "🎉 ALL " . count($testResults) . " BRAND PRICING TESTS PASSED!\n";
        exit(0);
    } else {
        echo "❌ SOME TESTS FAILED!\n";
        exit(1);
    }
}

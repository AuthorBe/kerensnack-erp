<?php
declare(strict_types=1);

/**
 * tests/ProduksiHybridDeductionTest.php
 * Suite Pengujian Komprehensif: Pemotongan Stok Bahan Hybrid (Bal vs Pcs) & Proteksi Produksi
 *
 * Menguji:
 * 1. Struktur schema: Kolom potong_sesuai_bal pada tabel komposisi_item
 * 2. Input produksi baru: Bahan mentah curah terpotong utuh sesuai Bal, kemasan terpotong sesuai Pcs
 * 3. Update/Koreksi produksi: Penyesuaian delta bal & pcs ke stok fisik dan riwayat_stok
 * 4. Proteksi Anti-Stok Minus Barang Jadi: Penolakan koreksi jika barang jadi sudah terjual
 * 5. Proteksi Anti-Stok Minus Bahan Mentah: Penolakan jika stok bal gudang tidak mencukupi
 * 6. Hapus/Pembatalan produksi: Pengembalian bal dan kemasan ke stok gudang tanpa residu
 *
 * Lingkungan: Wajib Local DB Sandbox (kerensnack_erp_local) dengan rollBack() di blok finally.
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
echo "KEREN SNACK ERP - PRODUKSI HYBRID DEDUCTION & INTEGRITY TEST SUITE\n";
echo "====================================================================\n\n";

$pdo = Database::getConnection();

// Guard Sandbox: Pastikan hanya berjalan di database lokal jika mutating
$activeDbName = (string)$pdo->query("SELECT current_database()")->fetchColumn();
echo "Active Database: {$activeDbName}\n\n";

if ($activeDbName !== 'kerensnack_erp_local') {
    echo "\033[33m[SKIP]\033[0m Mutating suite ini ditangguhkan karena koneksi aktif mengarah ke Supabase Live ({$activeDbName}).\n";
    echo "Hanya read-only assertion yang dijalankan.\n\n";
}

// -----------------------------------------------------------------------------
// 1. SCHEMA ASSERTION (Read-Only)
// -----------------------------------------------------------------------------
runTest("1. Kolom potong_sesuai_bal exists on public.komposisi_item", function() use ($pdo) {
    $stmt = $pdo->prepare("
        SELECT column_name, data_type, column_default 
        FROM information_schema.columns 
        WHERE table_schema = 'public' 
          AND table_name = 'komposisi_item' 
          AND column_name = 'potong_sesuai_bal'
    ");
    $stmt->execute();
    $col = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$col) {
        throw new Exception("Kolom 'potong_sesuai_bal' tidak ditemukan di public.komposisi_item");
    }
    if ($col['data_type'] !== 'boolean') {
        throw new Exception("Tipe data kolom 'potong_sesuai_bal' adalah {$col['data_type']}, expected boolean");
    }
    return true;
});

runTest("2. Trigger trg_produksi_harian_after_insert, update, delete active on database", function() use ($pdo) {
    $triggers = $pdo->query("
        SELECT trigger_name 
        FROM information_schema.triggers 
        WHERE event_object_table = 'produksi_harian'
    ")->fetchAll(PDO::FETCH_COLUMN);

    $expected = [
        'trg_produksi_harian_after_insert',
        'trg_produksi_harian_after_update',
        'trg_produksi_harian_after_delete'
    ];

    foreach ($expected as $t) {
        if (!in_array($t, $triggers, true)) {
            throw new Exception("Trigger '{$t}' tidak aktif di public.produksi_harian");
        }
    }
    return true;
});

// -----------------------------------------------------------------------------
// 2. TRANSACTIONAL SIMULATION (Strictly Local DB Sandbox with Rollback)
// -----------------------------------------------------------------------------
if ($activeDbName === 'kerensnack_erp_local') {

    $pdo->beginTransaction();

    try {
        // Setup Fixtures
        // 1. Item Bahan Mentah (Curah)
        $bahanMentahId = 'a1111111-1111-4111-a111-111111111111';
        $pdo->prepare("
            INSERT INTO public.item (id, kode_sku, nama_item, tipe_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian, status_aktif)
            VALUES (:id, 'TEST-MAT-RAW', 'TEST BASRENG BULAT BAL', 'bahan_mentah', 'bal', 50.00, 63000.00, TRUE)
            ON CONFLICT (id) DO UPDATE SET stok_fisik_saat_ini = 50.00
        ")->execute(['id' => $bahanMentahId]);

        // 2. Item Bahan Kemas (Plastik)
        $bahanKemasId = 'a2222222-2222-4222-a222-222222222222';
        $pdo->prepare("
            INSERT INTO public.item (id, kode_sku, nama_item, tipe_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian, status_aktif)
            VALUES (:id, 'TEST-MAT-PKG', 'TEST PLASTIK PACK 100G', 'bahan_kemas', 'lembar', 1000.00, 250.00, TRUE)
            ON CONFLICT (id) DO UPDATE SET stok_fisik_saat_ini = 1000.00
        ")->execute(['id' => $bahanKemasId]);

        // 3. Kelompok Upah Borongan
        $kelompokUpahId = 'a3333333-3333-4333-a333-333333333333';
        $pdo->prepare("
            INSERT INTO public.kelompok_upah_borongan (id, nama_kelompok, upah_per_bungkus, status_aktif)
            VALUES (:id, 'TEST BORONGAN 500', 500.00, TRUE)
            ON CONFLICT (id) DO NOTHING
        ")->execute(['id' => $kelompokUpahId]);

        // 4. Grup Produk & Item Barang Jadi
        $grupId = $pdo->query("SELECT id FROM public.grup_produk WHERE status_aktif = TRUE LIMIT 1")->fetchColumn();
        if (!$grupId) {
            $grupId = 'a0000000-0000-4000-a000-000000000000';
            $pdo->prepare("
                INSERT INTO public.grup_produk (id, nama_grup, kode_sku, status_aktif)
                VALUES (:id, 'TEST GROUP SNACK', 'GRP-TEST-SNK', TRUE)
                ON CONFLICT (id) DO NOTHING
            ")->execute(['id' => $grupId]);
        }

        $barangJadiId = 'a4444444-4444-4444-a444-444444444444';
        $pdo->prepare("
            INSERT INTO public.item (id, grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, stok_fisik_saat_ini, kelompok_borongan_id, status_aktif)
            VALUES (:id, :grup_id, 'TEST-FG-SNACK', 'TEST BERONDONG BERAS PACK', 'barang_jadi', 'bungkus', 0.00, :kel_id, TRUE)
            ON CONFLICT (id) DO UPDATE SET stok_fisik_saat_ini = 0.00, grup_id = :grup_id
        ")->execute(['id' => $barangJadiId, 'grup_id' => $grupId, 'kel_id' => $kelompokUpahId]);

        // 5. Karyawan Borongan
        $karyawanId = $pdo->query("SELECT id FROM public.karyawan WHERE tipe_penggajian = 'borongan' LIMIT 1")->fetchColumn();
        if (!$karyawanId) {
            $karyawanId = 'a5555555-5555-4555-a555-555555555555';
            $pdo->prepare("
                INSERT INTO public.karyawan (id, tipe_penggajian)
                VALUES (:id, 'borongan')
                ON CONFLICT (id) DO NOTHING
            ")->execute(['id' => $karyawanId]);
        }

        // 6. Setup Komposisi Item (BOM):
        // - Bahan mentah: potong_sesuai_bal = TRUE (kebutuhan 0.0125 untuk estimasi HPP)
        $pdo->prepare("
            INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan, potong_sesuai_bal)
            VALUES (:jadi, :bahan, 0.0125, TRUE)
            ON CONFLICT (item_jadi_id, item_bahan_id) DO UPDATE SET potong_sesuai_bal = TRUE, jumlah_kebutuhan = 0.0125
        ")->execute(['jadi' => $barangJadiId, 'bahan' => $bahanMentahId]);

        // - Bahan kemasan: potong_sesuai_bal = FALSE (kebutuhan 1 lembar per bungkus)
        $pdo->prepare("
            INSERT INTO public.komposisi_item (item_jadi_id, item_bahan_id, jumlah_kebutuhan, potong_sesuai_bal)
            VALUES (:jadi, :bahan, 1.0000, FALSE)
            ON CONFLICT (item_jadi_id, item_bahan_id) DO UPDATE SET potong_sesuai_bal = FALSE, jumlah_kebutuhan = 1.0000
        ")->execute(['jadi' => $barangJadiId, 'bahan' => $bahanKemasId]);

        // ---------------------------------------------------------------------
        // TEST 3: INSERT PRODUKSI (Hybrid Bal & Pcs)
        // Output: 85 pcs bungkus, 1 bal fisik diinput
        // Expected:
        // - Barang jadi: +85 pcs
        // - Bahan mentah (potong_sesuai_bal = TRUE): -1 bal utuh (stok 50 -> 49)
        // - Bahan kemas (potong_sesuai_bal = FALSE): -85 lembar (stok 1000 -> 915)
        // ---------------------------------------------------------------------
        runTest("3. INSERT Produksi: Bahan mentah potong 1 bal utuh & kemasan potong 85 lembar", function() use ($pdo, $barangJadiId, $bahanMentahId, $bahanKemasId, $karyawanId) {
            $prodId = 'a6666666-6666-4666-a666-666666666666';
            $tgl = date('Y-m-d');

            $pdo->prepare("
                INSERT INTO public.produksi_harian (
                    id, karyawan_id, tanggal, item_id, kuantitas_pcs, kuantitas_bal,
                    lembur_pcs, lembur_bal, upah_per_pcs_snapshot, total_upah_didapat
                ) VALUES (
                    :id, :kid, :tgl, :item_id, 85, 1,
                    0, 0, 500.00, 42500.00
                )
            ")->execute([
                'id' => $prodId,
                'kid' => $karyawanId,
                'tgl' => $tgl,
                'item_id' => $barangJadiId
            ]);

            // Cek Stok Barang Jadi
            $stokFg = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$barangJadiId}'")->fetchColumn();
            if ($stokFg !== 85.00) {
                throw new Exception("Stok barang jadi expected 85.00, got {$stokFg}");
            }

            // Cek Stok Bahan Mentah (Harus 49.00 bal, BUKAN 50 - (85 * 0.0125) = 48.9375)
            $stokRaw = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$bahanMentahId}'")->fetchColumn();
            if ($stokRaw !== 49.00) {
                throw new Exception("Stok bahan mentah expected 49.00 bal (potong utuh 1 bal), got {$stokRaw}");
            }

            // Cek Stok Bahan Kemasan (Harus 915.00 lembar)
            $stokPkg = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$bahanKemasId}'")->fetchColumn();
            if ($stokPkg !== 915.00) {
                throw new Exception("Stok bahan kemasan expected 915.00 lembar (potong 85 lembar), got {$stokPkg}");
            }

            return true;
        });

        // ---------------------------------------------------------------------
        // TEST 4: UPDATE PRODUKSI (Koreksi Delta Bal & Pcs)
        // Edit dari 85 pcs (1 bal) -> 170 pcs (2 bal)
        // Expected:
        // - Barang jadi: 85 -> 170 (+85 pcs)
        // - Bahan mentah: 49 -> 48 (-1 bal lagi)
        // - Bahan kemas: 915 -> 830 (-85 lembar lagi)
        // ---------------------------------------------------------------------
        runTest("4. UPDATE Produksi: Koreksi naik ke 170 pcs & 2 bal sesuaikan delta secara presisi", function() use ($pdo, $barangJadiId, $bahanMentahId, $bahanKemasId) {
            $prodId = 'a6666666-6666-4666-a666-666666666666';

            $pdo->prepare("
                UPDATE public.produksi_harian SET
                    kuantitas_pcs = 170,
                    kuantitas_bal = 2,
                    total_upah_didapat = 85000.00,
                    diubah_pada = NOW()
                WHERE id = :id
            ")->execute(['id' => $prodId]);

            $stokFg = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$barangJadiId}'")->fetchColumn();
            $stokRaw = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$bahanMentahId}'")->fetchColumn();
            $stokPkg = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$bahanKemasId}'")->fetchColumn();

            if ($stokFg !== 170.00) throw new Exception("Stok barang jadi expected 170.00, got {$stokFg}");
            if ($stokRaw !== 48.00) throw new Exception("Stok bahan mentah expected 48.00 bal, got {$stokRaw}");
            if ($stokPkg !== 830.00) throw new Exception("Stok bahan kemas expected 830.00 lembar, got {$stokPkg}");

            return true;
        });

        // ---------------------------------------------------------------------
        // TEST 5: PROTEKSI ANTI-STOK MINUS BARANG JADI (Jika Barang Sudah Terjual)
        // Simulasikan: Barang jadi laku terjual 165 pcs (stok sisa 5 pcs).
        // Lalu user mencoba menurunkan output produksi dari 170 menjadi 100 (selisih -70 pcs).
        // Karena stok saat ini cuma 5 pcs, pengurangan 70 pcs harus DITOLAK trigger!
        // ---------------------------------------------------------------------
        runTest("5. PROTEKSI ANTI-STOK MINUS: Tolak penurunan produksi jika stok barang jadi tidak cukup (laku terjual)", function() use ($pdo, $barangJadiId) {
            $prodId = 'a6666666-6666-4666-a666-666666666666';

            // Simulasikan penjualan: turunkan stok_fisik_saat_ini menjadi 5 pcs
            $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = 5.00 WHERE id = :id")->execute(['id' => $barangJadiId]);

            $pdo->exec("SAVEPOINT sp_test5");
            $caught = false;
            try {
                // Mencoba menurunkan kuantitas dari 170 menjadi 100 (delta: -70 pcs, padahal stok cuma 5)
                $pdo->prepare("UPDATE public.produksi_harian SET kuantitas_pcs = 100 WHERE id = :id")->execute(['id' => $prodId]);
            } catch (PDOException $e) {
                $pdo->exec("ROLLBACK TO SAVEPOINT sp_test5");
                if (str_contains($e->getMessage(), 'Koreksi produksi gagal') && str_contains($e->getMessage(), 'tidak mencukupi')) {
                    $caught = true;
                } else {
                    throw $e;
                }
            }

            if (!$caught) {
                throw new Exception("Harusnya trigger melempar exception anti-stok minus barang jadi, tetapi eksekusi berhasil!");
            }

            // Kembalikan stok simulasi ke 170 pcs untuk test selanjutnya
            $pdo->prepare("UPDATE public.item SET stok_fisik_saat_ini = 170.00 WHERE id = :id")->execute(['id' => $barangJadiId]);

            return true;
        });

        // ---------------------------------------------------------------------
        // TEST 6: PROTEKSI ANTI-STOK MINUS BAHAN MENTAH
        // Menaikkan kuantitas bal melebihi stok bal gudang saat ini (misal tambah 100 bal)
        // ---------------------------------------------------------------------
        runTest("6. PROTEKSI ANTI-STOK MINUS: Tolak penambahan bal jika stok bahan mentah gudang tidak cukup", function() use ($pdo) {
            $prodId = 'a6666666-6666-4666-a666-666666666666';

            $pdo->exec("SAVEPOINT sp_test6");
            $caught = false;
            try {
                // Tambah 100 bal (stok cuma 48)
                $pdo->prepare("UPDATE public.produksi_harian SET kuantitas_bal = 102 WHERE id = :id")->execute(['id' => $prodId]);
            } catch (PDOException $e) {
                $pdo->exec("ROLLBACK TO SAVEPOINT sp_test6");
                if (str_contains($e->getMessage(), 'tidak mencukupi')) {
                    $caught = true;
                } else {
                    throw $e;
                }
            }

            if (!$caught) {
                throw new Exception("Harusnya trigger melempar exception stok bahan tidak cukup, tetapi eksekusi lolos!");
            }

            return true;
        });

        // ---------------------------------------------------------------------
        // TEST 7: DELETE PRODUKSI & REVERT STOK
        // Hapus catatan produksi (170 pcs, 2 bal)
        // Expected:
        // - Barang jadi: 170 -> 0 (-170 pcs)
        // - Bahan mentah: 48 -> 50 (+2 bal dikembalikan)
        // - Bahan kemas: 830 -> 1000 (+170 lembar dikembalikan)
        // ---------------------------------------------------------------------
        runTest("7. DELETE Produksi: Kembalikan 2 bal bahan mentah dan 170 kemasan ke gudang secara utuh", function() use ($pdo, $barangJadiId, $bahanMentahId, $bahanKemasId) {
            $prodId = 'a6666666-6666-4666-a666-666666666666';

            $pdo->prepare("DELETE FROM public.produksi_harian WHERE id = :id")->execute(['id' => $prodId]);

            $stokFg = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$barangJadiId}'")->fetchColumn();
            $stokRaw = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$bahanMentahId}'")->fetchColumn();
            $stokPkg = (float)$pdo->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$bahanKemasId}'")->fetchColumn();

            if ($stokFg !== 0.00) throw new Exception("Stok barang jadi expected 0.00, got {$stokFg}");
            if ($stokRaw !== 50.00) throw new Exception("Stok bahan mentah expected 50.00 bal (revert utuh 2 bal), got {$stokRaw}");
            if ($stokPkg !== 1000.00) throw new Exception("Stok bahan kemas expected 1000.00 lembar (revert 170 lembar), got {$stokPkg}");

            return true;
        });

    } finally {
        // ALWAYS ROLLBACK: Sesuai aturan no-exception AGENTS.md, zero residual test data!
        $pdo->rollBack();
        echo "\n\033[36m[SANDBOX CLEANUP]\033[0m Transaksi test suite telah di-rollback secara penuh. Database 100% bersih tanpa residu.\n";
    }
}

echo "\n--------------------------------------------------------------------\n";
echo "SUMMARY: Passed: {$passed} | Failed: {$failed}\n";
echo "--------------------------------------------------------------------\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

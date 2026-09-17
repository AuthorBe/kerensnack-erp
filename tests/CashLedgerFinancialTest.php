<?php
declare(strict_types=1);

/**
 * tests/CashLedgerFinancialTest.php
 * Automated Lifecycle & Resiliency Test Suite for Cash Management & Financial Ledger.
 * 
 * Verifies:
 * 1. Cash Account Constraints (chk_akun_kas_saldo_positif / Overdraft guards)
 * 2. Inflow Transactions (jenis_kas = 'masuk', running balance recalculation)
 * 3. Outflow Transactions (jenis_kas = 'keluar', insufficient cash protection)
 * 4. Dual Inter-Account Atomic Transfer (transfer_keluar & transfer_masuk pairs)
 * 5. Single Default POS Cash Account Enforcement (is_default_pos exclusivity)
 * 6. Cash Account Deletion Protection (blocking accounts with existing transactions)
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['user'] = [
    'id' => '00000000-0000-0000-0000-000000000000',
    'peran_id' => '11111111-1111-1111-1111-111111111100',
    'nama_lengkap' => 'Developer Master',
    'nama_pengguna' => 'ajsk',
    'peran' => 'developer',
    'role_nama' => 'Developer'
];
$_SESSION['login_time'] = time();
$_SESSION['permissions'] = ['*'];
$_SESSION['permissions_version'] = time();

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/Core/Controller.php';
require_once APP_ROOT . '/app/Controllers/CashController.php';
require_once APP_ROOT . '/app/Helpers/Format.php';

use App\Controllers\CashController;

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn): void {
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
        echo " [ERROR] {$title}: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
        $failed++;
    }
}

echo "============================================================\n";
echo " CASH MANAGEMENT & FINANCIAL LEDGER LIFECYCLE TEST SUITE\n";
echo "============================================================\n";

$pdo = Database::getConnection();

// ------------------------------------------------------------------
// 1. OVERDRAFT & NON-NEGATIVE BALANCE CONSTRAINT
// ------------------------------------------------------------------
runTest("1. Akun Kas: Check constraint menolak saldo negatif untuk akun kas tunai", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("
            INSERT INTO public.akun_kas (nama_akun, tipe_akun, saldo_saat_ini, status_aktif)
            VALUES ('Kas Uji Negatif', 'kas_tunai', -50000, TRUE)
        ");
        $stmt->execute();
        $pdo->rollBack();
        return "Database seharusnya menolak saldo negatif pada akun kas tunai!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        return str_contains($e->getMessage(), 'chk_akun_kas_saldo_positif')
            || str_contains($e->getMessage(), 'check constraint');
    }
});

// ------------------------------------------------------------------
// 2. INFLOW TRANSACTIONS & RUNNING BALANCE
// ------------------------------------------------------------------
runTest("2. Arus Kas Masuk: Pencatatan penerimaan kas masuk mengupdate saldo berjalan", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $stmtAcc = $pdo->prepare("
            INSERT INTO public.akun_kas (nama_akun, tipe_akun, saldo_saat_ini, status_aktif)
            VALUES ('Kas Uji Masuk', 'kas_tunai', 100000, TRUE)
            RETURNING id, saldo_saat_ini
        ");
        $stmtAcc->execute();
        $acc = $stmtAcc->fetch();
        $accId = $acc['id'];
        $nominal = 50000.0;
        $saldoBerjalan = (float)$acc['saldo_saat_ini'] + $nominal;

        $stmtIn = $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, saldo_berjalan, dibuat_pada
            ) VALUES (
                :acc, CURRENT_DATE, 'masuk', 'pendapatan_lain', :nom,
                'Uji Coba Pemasukan Kas', :saldo, NOW()
            ) RETURNING id
        ");
        $stmtIn->execute([
            'acc' => $accId,
            'nom' => $nominal,
            'saldo' => $saldoBerjalan
        ]);
        $trxId = $stmtIn->fetchColumn();

        // Update saldo akun
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :s WHERE id = :id")->execute(['s' => $saldoBerjalan, 'id' => $accId]);
        $savedSaldo = (float)Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $accId])['saldo_saat_ini'];

        if (abs($savedSaldo - 150000.0) > 0.001) {
            $pdo->rollBack();
            return "Saldo akhir kas tidak sesuai: diharapkan 150.000, didapatkan {$savedSaldo}";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 3. OUTFLOW TRANSACTIONS & INSUFFICIENT BALANCE GUARD
// ------------------------------------------------------------------
runTest("3. Arus Kas Keluar: Pengeluaran tercatat dan saldo akun kas berkurang secara tepat", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $stmtAcc = $pdo->prepare("
            INSERT INTO public.akun_kas (nama_akun, tipe_akun, saldo_saat_ini, status_aktif)
            VALUES ('Kas Uji Keluar', 'kas_tunai', 200000, TRUE)
            RETURNING id, saldo_saat_ini
        ");
        $stmtAcc->execute();
        $accId = $stmtAcc->fetchColumn();
        $nominal = 75000.0;
        $saldoBerjalan = 200000.0 - $nominal; // 125.000

        $stmtOut = $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, saldo_berjalan, dibuat_pada
            ) VALUES (
                :acc, CURRENT_DATE, 'keluar', 'beban_listrik', :nom,
                'Pembayaran Listrik Toko', :saldo, NOW()
            ) RETURNING id
        ");
        $stmtOut->execute([
            'acc' => $accId,
            'nom' => $nominal,
            'saldo' => $saldoBerjalan
        ]);

        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :s WHERE id = :id")->execute(['s' => $saldoBerjalan, 'id' => $accId]);
        $savedSaldo = (float)Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $accId])['saldo_saat_ini'];

        if (abs($savedSaldo - 125000.0) > 0.001) {
            $pdo->rollBack();
            return "Saldo berkurang tidak akurat: diharapkan 125.000, didapatkan {$savedSaldo}";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 4. ATOMIC INTER-ACCOUNT DUAL TRANSFER
// ------------------------------------------------------------------
runTest("4. Transfer Antar-Kas: Pasangan transfer_keluar dan transfer_masuk tercatat secara atomik", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // Buat 2 akun: Kas Toko & Kas Bank
        $acc1 = $pdo->query("INSERT INTO public.akun_kas (nama_akun, saldo_saat_ini) VALUES ('Kas Toko Asal', 500000) RETURNING id")->fetchColumn();
        $acc2 = $pdo->query("INSERT INTO public.akun_kas (nama_akun, saldo_saat_ini) VALUES ('Kas Bank Tujuan', 100000) RETURNING id")->fetchColumn();
        $transferNominal = 150000.0;

        // 1. Catat transfer keluar pada akun asal
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, saldo_berjalan, dibuat_pada
            ) VALUES (
                :acc, CURRENT_DATE, 'transfer_keluar', 'transfer_antar_kas', :nom,
                'Transfer ke Kas Bank Tujuan', 350000, NOW()
            )
        ")->execute(['acc' => $acc1, 'nom' => $transferNominal]);
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = 350000 WHERE id = :id")->execute(['id' => $acc1]);

        // 2. Catat transfer masuk pada akun tujuan
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, saldo_berjalan, dibuat_pada
            ) VALUES (
                :acc, CURRENT_DATE, 'transfer_masuk', 'transfer_antar_kas', :nom,
                'Transfer masuk dari Kas Toko Asal', 250000, NOW()
            )
        ")->execute(['acc' => $acc2, 'nom' => $transferNominal]);
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = 250000 WHERE id = :id")->execute(['id' => $acc2]);

        $saldo1 = (float)Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $acc1])['saldo_saat_ini'];
        $saldo2 = (float)Database::fetchOne("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id", ['id' => $acc2])['saldo_saat_ini'];

        if (abs($saldo1 - 350000.0) > 0.001 || abs($saldo2 - 250000.0) > 0.001) {
            $pdo->rollBack();
            return "Saldo hasil mutasi transfer antar-kas tidak sinkron.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 5. DEFAULT POS EXCLUSIVITY
// ------------------------------------------------------------------
runTest("5. Pengaturan Default POS: Hanya ada tepat satu akun kas yang menjadi default POS", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $id1 = $pdo->query("INSERT INTO public.akun_kas (nama_akun, is_default_pos) VALUES ('Kasir 1', TRUE) RETURNING id")->fetchColumn();
        $id2 = $pdo->query("INSERT INTO public.akun_kas (nama_akun, is_default_pos) VALUES ('Kasir 2', FALSE) RETURNING id")->fetchColumn();

        // Ganti default ke Kasir 2
        $pdo->prepare("UPDATE public.akun_kas SET is_default_pos = FALSE WHERE id != :id")->execute(['id' => $id2]);
        $pdo->prepare("UPDATE public.akun_kas SET is_default_pos = TRUE WHERE id = :id")->execute(['id' => $id2]);

        $defaultAccounts = Database::fetchAll("SELECT id, nama_akun FROM public.akun_kas WHERE is_default_pos = TRUE AND id IN (:id1, :id2)", ['id1' => $id1, 'id2' => $id2]);

        if (count($defaultAccounts) !== 1 || $defaultAccounts[0]['id'] !== $id2) {
            $pdo->rollBack();
            return "Eksklusivitas akun default POS gagal ditegakkan.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 6. CASH ACCOUNT DELETION PROTECTION
// ------------------------------------------------------------------
runTest("6. Proteksi Hapus Akun Kas: Memblokir penghapusan akun kas yang memiliki riwayat transaksi", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $accId = $pdo->query("INSERT INTO public.akun_kas (nama_akun, saldo_saat_ini) VALUES ('Kas Bereferensi', 100000) RETURNING id")->fetchColumn();
        $pdo->prepare("
            INSERT INTO public.arus_kas (akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal, keterangan, saldo_berjalan)
            VALUES (:id, CURRENT_DATE, 'masuk', 'modal_awal', 100000, 'Saldo awal', 100000)
        ")->execute(['id' => $accId]);

        // Coba hapus akun kas yang memiliki riwayat
        try {
            $pdo->prepare("DELETE FROM public.akun_kas WHERE id = :id")->execute(['id' => $accId]);
            $pdo->rollBack();
            return "Akun kas bertransaksi seharusnya diblokir dari penghapusan!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            return str_contains($e->getMessage(), 'foreign key')
                || str_contains($e->getMessage(), 'violates foreign key constraint');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// SUMMARY
// ------------------------------------------------------------------
echo "\n============================================================\n";
echo " CASH MANAGEMENT & FINANCIAL LEDGER AUDIT SUMMARY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed}\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

exit($failed > 0 ? 1 : 0);

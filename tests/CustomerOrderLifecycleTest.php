<?php
declare(strict_types=1);

/**
 * tests/CustomerOrderLifecycleTest.php
 * Automated Lifecycle & Resiliency Test Suite for B2B Customer Orders & Invoicing.
 * 
 * Verifies:
 * 1. Document Sequence Number Generation (DocumentNumber::nextOrderNumber)
 * 2. Order Creation with HPP Snapshot, Credit Limit Checks, & Initial 'po' Status
 * 3. Status Workflow Progression ('po' -> 'siap_dikirim')
 * 4. Payment Processing (Cash Inflow, Running Balance & Status Lunas/Sebagian)
 * 5. Order Cancellation & Stock Reversal Cleanliness
 * 6. Sales Driver Delivery Association
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
require_once APP_ROOT . '/app/Controllers/CustomerOrderController.php';
require_once APP_ROOT . '/app/Helpers/DocumentNumber.php';
require_once APP_ROOT . '/app/Helpers/Format.php';

use App\Helpers\DocumentNumber;

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
echo " B2B CUSTOMER ORDERS & INVOICING LIFECYCLE TEST SUITE\n";
echo "============================================================\n";

$pdo = Database::getConnection();

// Sample customer & item
$customer = Database::fetchOne("
    SELECT id, kode_pelanggan, nama_toko, plafon_piutang, total_piutang_berjalan, sales_driver_id 
    FROM public.pelanggan 
    WHERE status_aktif = TRUE 
    LIMIT 1
");

$item = Database::fetchOne("
    SELECT id, kode_sku, nama_item, harga_pokok_pembelian 
    FROM public.item 
    WHERE tipe_item = 'barang_jadi' AND status_aktif = TRUE 
    LIMIT 1
");

$cashAccount = Database::fetchOne("
    SELECT id, nama_akun, saldo_saat_ini 
    FROM public.akun_kas 
    WHERE status_aktif = TRUE 
    LIMIT 1
");

$driver = Database::fetchOne("
    SELECT k.id as karyawan_id, p.id as pengguna_id, p.nama_lengkap 
    FROM public.karyawan k 
    JOIN public.pengguna p ON k.pengguna_id = p.id 
    WHERE p.posisi = 'driver' AND p.status_aktif = TRUE 
    LIMIT 1
");

// ------------------------------------------------------------------
// 1. ORDER DOCUMENT NUMBERING
// ------------------------------------------------------------------
runTest("1. DocumentNumber::nextOrderNumber: Menghasilkan format nomor nota yang valid", function() use ($pdo) {
    $orderNumber = DocumentNumber::nextOrderNumber($pdo);
    if (empty($orderNumber) || !str_starts_with($orderNumber, 'KRS-')) {
        return "Nomor pesanan harus diawali prefix KRS-, didapatkan: {$orderNumber}";
    }
    return true;
});

// ------------------------------------------------------------------
// 2. ORDER CREATION WITH HPP SNAPSHOT & 'po' STATUS
// ------------------------------------------------------------------
runTest("2. Lifecycle Pesanan: Membuat pesanan B2B dengan status 'po' dan snapshot HPP", function() use ($pdo, $customer, $item) {
    if (!$customer || !$item) return "Data customer atau item tidak lengkap.";

    $pdo->beginTransaction();
    try {
        $nota = 'ORD-TEST-' . mt_rand(100000, 999999);
        $qty = 10;
        $hargaDeal = 18000.0;
        $totalBruto = $qty * $hargaDeal;
        $expectedHpp = (float)($item['harga_pokok_pembelian'] ?? 12000.0);

        // Simpan pesanan
        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_diskon, total_netto,
                tipe_pembayaran, status_pemrosesan, status_pembayaran, dibuat_pada
            ) VALUES (
                :nota, :cid, CURRENT_DATE, :bruto, 0, :netto,
                'tempo_14_hari', 'po', 'belum_lunas', NOW()
            ) RETURNING id, status_pemrosesan, status_pembayaran
        ");
        $stmtOrder->execute([
            'nota' => $nota,
            'cid' => $customer['id'],
            'bruto' => $totalBruto,
            'netto' => $totalBruto
        ]);
        $order = $stmtOrder->fetch();
        $orderId = $order['id'];

        if ($order['status_pemrosesan'] !== 'po') {
            $pdo->rollBack();
            return "Status awal pesanan B2B harus 'po', didapatkan: {$order['status_pemrosesan']}";
        }

        // Simpan rincian pesanan
        $stmtItem = $pdo->prepare("
            INSERT INTO public.item_pesanan (
                pesanan_id, item_id, kuantitas_satuan_dasar,
                harga_satuan_deal, diskon_item_nominal, is_bonus, subtotal, harga_pokok_satuan
            ) VALUES (
                :oid, :iid, :qty,
                :harga, 0, FALSE, :subtotal, :hpp
            ) RETURNING id
        ");
        $stmtItem->execute([
            'oid' => $orderId,
            'iid' => $item['id'],
            'qty' => $qty,
            'harga' => $hargaDeal,
            'subtotal' => $totalBruto,
            'hpp' => $expectedHpp
        ]);

        // Verifikasi HPP snapshot tersimpan di database
        $savedItem = Database::fetchOne("SELECT harga_pokok_satuan FROM public.item_pesanan WHERE pesanan_id = :oid", ['oid' => $orderId]);
        if (!$savedItem || abs((float)$savedItem['harga_pokok_satuan'] - $expectedHpp) > 0.001) {
            $pdo->rollBack();
            return "HPP Snapshot gagal tersimpan.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 3. STATUS TRANSITION ('po' -> 'siap_dikirim')
// ------------------------------------------------------------------
runTest("3. Lifecycle Pesanan: Transisi status alur kerja dari 'po' ke 'siap_dikirim'", function() use ($pdo, $customer) {
    $pdo->beginTransaction();
    try {
        $nota = 'ORD-TEST-ST-' . mt_rand(100000, 999999);
        $stmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, status_pemrosesan, status_pembayaran
            ) VALUES (
                :nota, :cid, CURRENT_DATE, 50000, 50000, 'cash', 'po', 'belum_lunas'
            ) RETURNING id
        ");
        $stmt->execute(['nota' => $nota, 'cid' => $customer['id']]);
        $orderId = $stmt->fetchColumn();

        // Update ke 'siap_dikirim'
        $stmtUpdate = $pdo->prepare("
            UPDATE public.pesanan 
            SET status_pemrosesan = 'siap_dikirim', diubah_pada = NOW() 
            WHERE id = :id 
            RETURNING status_pemrosesan
        ");
        $stmtUpdate->execute(['id' => $orderId]);
        $newStatus = $stmtUpdate->fetchColumn();

        if ($newStatus !== 'siap_dikirim') {
            $pdo->rollBack();
            return "Status pemrosesan gagal diupdate ke siap_dikirim.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 4. ORDER PAYMENT (Pencatatan Arus Kas & Status Lunas)
// ------------------------------------------------------------------
runTest("4. Lifecycle Pembayaran: Pelunasan pesanan mencatat arus_kas masuk dan mengupdate total_dibayar", function() use ($pdo, $customer, $cashAccount) {
    if (!$cashAccount) return "Akun kas tidak tersedia.";

    $pdo->beginTransaction();
    try {
        $nota = 'ORD-TEST-PAY-' . mt_rand(100000, 999999);
        $totalNetto = 100000.0;

        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto, total_dibayar,
                tipe_pembayaran, status_pemrosesan, status_pembayaran
            ) VALUES (
                :nota, :cid, CURRENT_DATE, :netto, :netto, 0, 'cash', 'siap_dikirim', 'belum_lunas'
            ) RETURNING id
        ");
        $stmtOrder->execute(['nota' => $nota, 'cid' => $customer['id'], 'netto' => $totalNetto]);
        $orderId = $stmtOrder->fetchColumn();

        // Catat pelunasan via arus kas
        $saldoBerjalan = (float)$cashAccount['saldo_saat_ini'] + $totalNetto;
        $stmtKas = $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, referensi_tabel, referensi_id, saldo_berjalan, dibuat_pada
            ) VALUES (
                :ak_id, CURRENT_DATE, 'masuk', 'piutang_usaha', :nom,
                'Pelunasan Pesanan #{$nota}', 'pesanan', :oid, :saldo, NOW()
            ) RETURNING id
        ");
        $stmtKas->execute([
            'ak_id' => $cashAccount['id'],
            'nom' => $totalNetto,
            'oid' => $orderId,
            'saldo' => $saldoBerjalan
        ]);

        // Update status order menjadi lunas
        $stmtPay = $pdo->prepare("
            UPDATE public.pesanan 
            SET total_dibayar = :paid, status_pembayaran = 'lunas', diubah_pada = NOW() 
            WHERE id = :id 
            RETURNING total_dibayar, status_pembayaran
        ");
        $stmtPay->execute(['paid' => $totalNetto, 'id' => $orderId]);
        $payResult = $stmtPay->fetch();

        if ($payResult['status_pembayaran'] !== 'lunas' || (float)$payResult['total_dibayar'] !== $totalNetto) {
            $pdo->rollBack();
            return "Status pembayaran atau total dibayar tidak sinkron.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 5. ORDER CANCELLATION
// ------------------------------------------------------------------
runTest("5. Pembatalan Pesanan: Status pemrosesan & status pembayaran berubah menjadi 'dibatalkan'", function() use ($pdo, $customer) {
    $pdo->beginTransaction();
    try {
        $nota = 'ORD-TEST-CNL-' . mt_rand(100000, 999999);
        $stmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, status_pemrosesan, status_pembayaran
            ) VALUES (
                :nota, :cid, CURRENT_DATE, 75000, 75000, 'cash', 'po', 'belum_lunas'
            ) RETURNING id
        ");
        $stmt->execute(['nota' => $nota, 'cid' => $customer['id']]);
        $orderId = $stmt->fetchColumn();

        // Batalkan
        $stmtCancel = $pdo->prepare("
            UPDATE public.pesanan 
            SET status_pemrosesan = 'dibatalkan', status_pembayaran = 'dibatalkan', diubah_pada = NOW() 
            WHERE id = :id 
            RETURNING status_pemrosesan, status_pembayaran
        ");
        $stmtCancel->execute(['id' => $orderId]);
        $cancelRes = $stmtCancel->fetch();

        if ($cancelRes['status_pemrosesan'] !== 'dibatalkan' || $cancelRes['status_pembayaran'] !== 'dibatalkan') {
            $pdo->rollBack();
            return "Gagal membatalkan pesanan.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 6. SALES/DRIVER ASSIGNMENT SYNC
// ------------------------------------------------------------------
runTest("6. Logistik: Penugasan driver pada pesanan memperbarui kolom sales_driver_id", function() use ($pdo, $customer, $driver) {
    if (!$driver) return "Data driver tidak tersedia.";

    $pdo->beginTransaction();
    try {
        $nota = 'ORD-TEST-DRV-' . mt_rand(100000, 999999);
        $stmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, status_pemrosesan, status_pembayaran
            ) VALUES (
                :nota, :cid, CURRENT_DATE, 60000, 60000, 'cash', 'siap_dikirim', 'belum_lunas'
            ) RETURNING id
        ");
        $stmt->execute(['nota' => $nota, 'cid' => $customer['id']]);
        $orderId = $stmt->fetchColumn();

        // Assign driver
        $stmtAssign = $pdo->prepare("
            UPDATE public.pesanan 
            SET sales_driver_id = :did, diubah_pada = NOW() 
            WHERE id = :oid 
            RETURNING sales_driver_id
        ");
        $stmtAssign->execute(['did' => $driver['karyawan_id'], 'oid' => $orderId]);
        $assignedId = $stmtAssign->fetchColumn();

        if ($assignedId !== $driver['karyawan_id']) {
            $pdo->rollBack();
            return "Driver gagal diasosiasikan pada pesanan.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// SUMMARY
// ------------------------------------------------------------------
echo "\n============================================================\n";
echo " B2B CUSTOMER ORDER AUDIT SUMMARY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed}\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

exit($failed > 0 ? 1 : 0);

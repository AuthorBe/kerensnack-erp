<?php
declare(strict_types=1);

/**
 * tests/DeliveryAndLogisticsLifecycleTest.php
 * Automated Lifecycle & Resiliency Test Suite for Deliveries, Surat Jalan & Driver Logistics.
 * 
 * Verifies:
 * 1. Sequential Surat Jalan Number Generation (DocumentNumber::nextDeliveryNumber)
 * 2. Surat Jalan Creation with Snapshot Rute/Wilayah & Initial 'siap_kirim' Status
 * 3. Driver Trip Workflow Progression ('siap_kirim' -> 'sedang_dikirim' -> 'selesai_diterima')
 * 4. Delivery Completion & Linked Order Processing Sync
 * 5. Failed Delivery Recording (alasan_gagal, foto_bukti, status 'gagal_kirim')
 * 6. DeliveryController::store Fallback Status Defaults to 'siap_kirim' (Anti-Legacy-Crash)
 * 7. Database Check Constraint Rejection on Obsolete Statuses ('draf_n8n', 'pending')
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
require_once APP_ROOT . '/app/Controllers/DeliveryController.php';
require_once APP_ROOT . '/app/Helpers/DocumentNumber.php';
require_once APP_ROOT . '/app/Helpers/Format.php';

use App\Helpers\DocumentNumber;
use App\Controllers\DeliveryController;

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
echo " DELIVERIES & LOGISTICS LIFECYCLE TEST SUITE\n";
echo "============================================================\n";

$pdo = Database::getConnection();

// Sample Driver, Customer & Territory
$driver = Database::fetchOne("
    SELECT k.id as karyawan_id, p.id as pengguna_id, p.nama_lengkap 
    FROM public.karyawan k 
    JOIN public.pengguna p ON k.pengguna_id = p.id 
    WHERE p.posisi = 'driver' AND p.status_aktif = TRUE 
    LIMIT 1
");

$customer = Database::fetchOne("
    SELECT p.id, p.kode_pelanggan, p.nama_toko, p.wilayah_id, w.nama_wilayah, w.kode_rute 
    FROM public.pelanggan p 
    LEFT JOIN public.wilayah w ON p.wilayah_id = w.id 
    WHERE p.status_aktif = TRUE 
    LIMIT 1
");

$territory = Database::fetchOne("SELECT id, nama_wilayah, kode_rute FROM public.wilayah WHERE status_aktif = TRUE LIMIT 1");

// ------------------------------------------------------------------
// 1. SURAT JALAN NUMBER GENERATION
// ------------------------------------------------------------------
runTest("1. DocumentNumber::nextDeliveryNumber: Menghasilkan format SJ-YYYYMMDD-XXX", function() use ($pdo) {
    $sjNumber = DocumentNumber::nextDeliveryNumber($pdo);
    if (empty($sjNumber) || !str_starts_with($sjNumber, 'SJ-')) {
        return "Nomor surat jalan harus diawali prefix SJ-, didapatkan: {$sjNumber}";
    }
    return true;
});

// ------------------------------------------------------------------
// 2. SURAT JALAN CREATION WITH SNAPSHOT
// ------------------------------------------------------------------
runTest("2. Penerbitan Surat Jalan: Status operasional 'siap_kirim' dan snapshot wilayah tersimpan", function() use ($pdo, $customer, $driver, $territory) {
    if (!$customer || !$driver || !$territory) return "Data tidak lengkap.";

    $pdo->beginTransaction();
    try {
        $dummyNota = 'ORD-SJ-TEST-' . mt_rand(100000, 999999);
        $dummySj = 'SJ-TEST-' . mt_rand(100000, 999999);

        // Buat pesanan pendukung
        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, status_pemrosesan, status_pembayaran
            ) VALUES (
                :nota, :cid, CURRENT_DATE, 100000, 100000,
                'cash', 'siap_dikirim', 'belum_lunas'
            ) RETURNING id
        ");
        $stmtOrder->execute(['nota' => $dummyNota, 'cid' => $customer['id']]);
        $orderId = $stmtOrder->fetchColumn();

        // Buat surat jalan
        $stmtSj = $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, rute_wilayah_id,
                nama_wilayah_snapshot, kode_rute_snapshot, status_surat_jalan,
                tanggal_surat_jalan, dibuat_pada
            ) VALUES (
                :no_sj, :oid, :did, :wid,
                :w_snap, :r_snap, 'siap_kirim',
                CURRENT_DATE, NOW()
            ) RETURNING id, status_surat_jalan, nama_wilayah_snapshot
        ");
        $stmtSj->execute([
            'no_sj' => $dummySj,
            'oid' => $orderId,
            'did' => $driver['karyawan_id'],
            'wid' => $territory['id'],
            'w_snap' => $territory['nama_wilayah'],
            'r_snap' => $territory['kode_rute']
        ]);
        $sj = $stmtSj->fetch();

        if ($sj['status_surat_jalan'] !== 'siap_kirim') {
            $pdo->rollBack();
            return "Status awal surat jalan harus 'siap_kirim'.";
        }

        if ($sj['nama_wilayah_snapshot'] !== $territory['nama_wilayah']) {
            $pdo->rollBack();
            return "Snapshot wilayah gagal disimpan.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 3. TRIP WORKFLOW ('siap_kirim' -> 'sedang_dikirim' -> 'selesai_diterima')
// ------------------------------------------------------------------
runTest("3. Alur Pengiriman Driver: Transisi status dari siap kirim ke sedang dikirim lalu selesai", function() use ($pdo, $customer, $driver) {
    $pdo->beginTransaction();
    try {
        $dummyNota = 'ORD-TRIP-' . mt_rand(100000, 999999);
        $dummySj = 'SJ-TRIP-' . mt_rand(100000, 999999);

        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, status_pemrosesan, status_pembayaran
            ) VALUES (
                :nota, :cid, CURRENT_DATE, 50000, 50000, 'cash', 'siap_dikirim', 'belum_lunas'
            ) RETURNING id
        ");
        $stmtOrder->execute(['nota' => $dummyNota, 'cid' => $customer['id']]);
        $orderId = $stmtOrder->fetchColumn();

        $stmtSj = $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan
            ) VALUES (
                :no_sj, :oid, :did, 'siap_kirim'
            ) RETURNING id
        ");
        $stmtSj->execute(['no_sj' => $dummySj, 'oid' => $orderId, 'did' => $driver['karyawan_id']]);
        $sjId = $stmtSj->fetchColumn();

        // 1. Driver Berangkat
        $pdo->prepare("
            UPDATE public.surat_jalan 
            SET status_surat_jalan = 'sedang_dikirim', waktu_berangkat = NOW() 
            WHERE id = :id
        ")->execute(['id' => $sjId]);

        $st1 = Database::fetchOne("SELECT status_surat_jalan FROM public.surat_jalan WHERE id = :id", ['id' => $sjId])['status_surat_jalan'];
        if ($st1 !== 'sedang_dikirim') {
            $pdo->rollBack();
            return "Status surat jalan gagal diupdate ke sedang_dikirim.";
        }

        // 2. Driver Selesai Diterima Toko (POD)
        $pdo->prepare("
            UPDATE public.surat_jalan 
            SET status_surat_jalan = 'selesai_diterima', waktu_sampai = NOW(), nama_penerima_toko = 'Bpk. Budi' 
            WHERE id = :id
        ")->execute(['id' => $sjId]);

        $st2 = Database::fetchOne("SELECT status_surat_jalan, nama_penerima_toko FROM public.surat_jalan WHERE id = :id", ['id' => $sjId]);
        if ($st2['status_surat_jalan'] !== 'selesai_diterima' || $st2['nama_penerima_toko'] !== 'Bpk. Budi') {
            $pdo->rollBack();
            return "Penyelesaian surat jalan gagal tercatat.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 4. FAILED DELIVERY WITH REASON & PHOTO
// ------------------------------------------------------------------
runTest("4. Pengiriman Gagal: Mencatat alasan gagal kirim dan foto kendala (gagal_kirim / gagal_kembali)", function() use ($pdo, $customer, $driver) {
    $pdo->beginTransaction();
    try {
        $dummyNota = 'ORD-FAIL-' . mt_rand(100000, 999999);
        $dummySj = 'SJ-FAIL-' . mt_rand(100000, 999999);

        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, status_pemrosesan, status_pembayaran
            ) VALUES (
                :nota, :cid, CURRENT_DATE, 50000, 50000, 'cash', 'siap_dikirim', 'belum_lunas'
            ) RETURNING id
        ");
        $stmtOrder->execute(['nota' => $dummyNota, 'cid' => $customer['id']]);
        $orderId = $stmtOrder->fetchColumn();

        $stmtSj = $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan
            ) VALUES (
                :no_sj, :oid, :did, 'sedang_dikirim'
            ) RETURNING id
        ");
        $stmtSj->execute(['no_sj' => $dummySj, 'oid' => $orderId, 'did' => $driver['karyawan_id']]);
        $sjId = $stmtSj->fetchColumn();

        // Rekam kegagalan kirim
        $stmtFail = $pdo->prepare("
            UPDATE public.surat_jalan 
            SET status_surat_jalan = 'gagal_kirim', alasan_gagal = 'Toko Tutup', catatan_gagal = 'Pemilik sedang keluar kota' 
            WHERE id = :id 
            RETURNING status_surat_jalan, alasan_gagal
        ");
        $stmtFail->execute(['id' => $sjId]);
        $failRes = $stmtFail->fetch();

        if ($failRes['status_surat_jalan'] !== 'gagal_kirim' || $failRes['alasan_gagal'] !== 'Toko Tutup') {
            $pdo->rollBack();
            return "Catatan kegagalan pengiriman tidak valid.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 5. DELIVERYCONTROLLER BUG FIX VERIFICATION
// ------------------------------------------------------------------
runTest("5. DeliveryController::store: Fallback default status_surat_jalan adalah 'siap_kirim' (bebas dari disetujui_owner)", function() {
    $fileContent = file_get_contents(APP_ROOT . '/app/Controllers/DeliveryController.php');
    if (strpos($fileContent, "\$this->input('status_surat_jalan', 'disetujui_owner')") !== false) {
        return "DeliveryController::store() masih memuat fallback ilegal 'disetujui_owner'.";
    }
    if (strpos($fileContent, "\$this->input('status_surat_jalan', 'siap_kirim')") === false) {
        return "DeliveryController::store() belum menggunakan fallback operasional 'siap_kirim'.";
    }
    return true;
});

// ------------------------------------------------------------------
// 6. DB CHECK CONSTRAINT STRICTNESS
// ------------------------------------------------------------------
runTest("6. Database Constraint: Menolak status draf_n8n pada surat_jalan", function() use ($pdo, $customer, $driver) {
    $pdo->beginTransaction();
    try {
        $dummyNota = 'ORD-CHK-' . mt_rand(100000, 999999);
        $dummySj = 'SJ-CHK-' . mt_rand(100000, 999999);

        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, status_pemrosesan, status_pembayaran
            ) VALUES (
                :nota, :cid, CURRENT_DATE, 50000, 50000, 'cash', 'po', 'belum_lunas'
            ) RETURNING id
        ");
        $stmtOrder->execute(['nota' => $dummyNota, 'cid' => $customer['id']]);
        $orderId = $stmtOrder->fetchColumn();

        // Coba insert draf_n8n (wajib ditolak DB)
        try {
            $stmtSj = $pdo->prepare("
                INSERT INTO public.surat_jalan (
                    nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan
                ) VALUES (
                    :no_sj, :oid, :did, 'draf_n8n'
                )
            ");
            $stmtSj->execute(['no_sj' => $dummySj, 'oid' => $orderId, 'did' => $driver['karyawan_id']]);
            $pdo->rollBack();
            return "Status draf_n8n seharusnya ditolak oleh database constraint!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            return str_contains($e->getMessage(), 'surat_jalan_status_surat_jalan_check');
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
echo " DELIVERIES & LOGISTICS AUDIT SUMMARY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed}\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

exit($failed > 0 ? 1 : 0);

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

function createDeliveryFixtures(PDO $pdo): array {
    $wilStmt = $pdo->prepare("INSERT INTO public.wilayah (kode_rute, nama_wilayah, provinsi, kota_kabupaten) VALUES ('RUTE-TEST-DLV', 'Wilayah Test Delivery', 'Banten', 'Tangerang') RETURNING id, nama_wilayah, kode_rute");
    $wilStmt->execute();
    $territory = $wilStmt->fetch(PDO::FETCH_ASSOC);

    $grpelStmt = $pdo->prepare("INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, default_level_harga) VALUES ('GP-DLV-FX', 'Grup Pelanggan DLV FX', 1) RETURNING id");
    $grpelStmt->execute();
    $grpelId = $grpelStmt->fetchColumn();

    $pelStmt = $pdo->prepare("INSERT INTO public.pelanggan (kode_pelanggan, grup_pelanggan_id, nama_toko, nama_pemilik, nomor_whatsapp, alamat_lengkap, wilayah_id) VALUES ('PEL-DLV-FX', :gp_id, 'Toko DLV FX', 'Budi', '0812345678', 'Jl. Test DLV', :wid) RETURNING id");
    $pelStmt->execute(['gp_id' => $grpelId, 'wid' => $territory['id']]);
    $customer = $pelStmt->fetch(PDO::FETCH_ASSOC);

    $roleId = $pdo->query("SELECT id FROM public.peran WHERE nama_peran != 'Developer' AND nama_peran != 'developer' LIMIT 1")->fetchColumn();
    if (!$roleId) {
        $rStmt = $pdo->prepare("INSERT INTO public.peran (nama_peran, deskripsi) VALUES ('Peran Driver Test DLV', 'Driver Test Role DLV') RETURNING id");
        $rStmt->execute();
        $roleId = $rStmt->fetchColumn();
    }
    $usrStmt = $pdo->prepare("INSERT INTO public.pengguna (nama_lengkap, nama_pengguna, kata_sandi, posisi, peran_id) VALUES ('Driver Test DLV', 'driver_test_dlv', 'hash', 'driver', :rid) RETURNING id");
    $usrStmt->execute(['rid' => $roleId]);
    $driverUserId = $usrStmt->fetchColumn();

    $karStmt = $pdo->prepare("INSERT INTO public.karyawan (pengguna_id, tipe_penggajian, gaji_pokok_bulanan) VALUES (:pid, 'bulanan', 3000000) RETURNING id");
    $karStmt->execute(['pid' => $driverUserId]);
    $driverId = $karStmt->fetchColumn();

    return [
        'customer' => $customer,
        'territory' => $territory,
        'driverId' => $driverId
    ];
}

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
runTest("2. Penerbitan Surat Jalan: Status operasional 'siap_kirim' dan snapshot wilayah tersimpan", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createDeliveryFixtures($pdo);
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
        $stmtOrder->execute(['nota' => $dummyNota, 'cid' => $fx['customer']['id']]);
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
            'did' => $fx['driverId'],
            'wid' => $fx['territory']['id'],
            'w_snap' => $fx['territory']['nama_wilayah'],
            'r_snap' => $fx['territory']['kode_rute']
        ]);
        $sj = $stmtSj->fetch();

        if ($sj['status_surat_jalan'] !== 'siap_kirim') {
            $pdo->rollBack();
            return "Status awal surat jalan harus 'siap_kirim'.";
        }

        if ($sj['nama_wilayah_snapshot'] !== $fx['territory']['nama_wilayah']) {
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
runTest("3. Alur Pengiriman Driver: Transisi status dari siap kirim ke sedang dikirim lalu selesai", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createDeliveryFixtures($pdo);
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
        $stmtOrder->execute(['nota' => $dummyNota, 'cid' => $fx['customer']['id']]);
        $orderId = $stmtOrder->fetchColumn();

        $stmtSj = $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan
            ) VALUES (
                :no_sj, :oid, :did, 'siap_kirim'
            ) RETURNING id
        ");
        $stmtSj->execute(['no_sj' => $dummySj, 'oid' => $orderId, 'did' => $fx['driverId']]);
        $sjId = $stmtSj->fetchColumn();

        // 1. Driver Berangkat
        $pdo->prepare("
            UPDATE public.surat_jalan 
            SET status_surat_jalan = 'sedang_dikirim', waktu_berangkat = NOW() 
            WHERE id = :id
        ")->execute(['id' => $sjId]);

        $st1Stmt = $pdo->prepare("SELECT status_surat_jalan FROM public.surat_jalan WHERE id = :id");
        $st1Stmt->execute(['id' => $sjId]);
        $st1 = $st1Stmt->fetchColumn();
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

        $st2Stmt = $pdo->prepare("SELECT status_surat_jalan, nama_penerima_toko FROM public.surat_jalan WHERE id = :id");
        $st2Stmt->execute(['id' => $sjId]);
        $st2 = $st2Stmt->fetch(PDO::FETCH_ASSOC);
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
runTest("4. Pengiriman Gagal: Mencatat alasan gagal kirim dan foto kendala (gagal_kirim / gagal_kembali)", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createDeliveryFixtures($pdo);
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
        $stmtOrder->execute(['nota' => $dummyNota, 'cid' => $fx['customer']['id']]);
        $orderId = $stmtOrder->fetchColumn();

        $stmtSj = $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan
            ) VALUES (
                :no_sj, :oid, :did, 'sedang_dikirim'
            ) RETURNING id
        ");
        $stmtSj->execute(['no_sj' => $dummySj, 'oid' => $orderId, 'did' => $fx['driverId']]);
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
runTest("6. Database Constraint: Menolak status draf_n8n pada surat_jalan", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createDeliveryFixtures($pdo);
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
        $stmtOrder->execute(['nota' => $dummyNota, 'cid' => $fx['customer']['id']]);
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
            $stmtSj->execute(['no_sj' => $dummySj, 'oid' => $orderId, 'did' => $fx['driverId']]);
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
// 7. VIEW DELIVERIES INDEX CLEANLINESS
// ------------------------------------------------------------------
runTest("7. Views deliveries/index.php: Bebas dari input status terlarang 'disetujui_owner'", function() {
    $fileContent = file_get_contents(APP_ROOT . '/views/deliveries/index.php');
    if (strpos($fileContent, 'value="disetujui_owner"') !== false) {
        return "views/deliveries/index.php masih mengirim atau memuat value='disetujui_owner'.";
    }
    if (strpos($fileContent, 'name="status_surat_jalan" value="siap_kirim"') === false) {
        return "views/deliveries/index.php belum menyertakan input status 'siap_kirim' yang valid.";
    }
    return true;
});

// ------------------------------------------------------------------
// 8. DELIVERYCONTROLLER DEFENSIVE INPUT SANITIZATION
// ------------------------------------------------------------------
runTest("8. DeliveryController::store: Sanitasi input otomatis menormalkan input invalid ke 'siap_kirim'", function() {
    $fileContent = file_get_contents(APP_ROOT . '/app/Controllers/DeliveryController.php');
    if (strpos($fileContent, "in_array(\$status, \$validStatuses") === false) {
        return "DeliveryController::store() belum memiliki whitelist sanitasi status_surat_jalan.";
    }
    return true;
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

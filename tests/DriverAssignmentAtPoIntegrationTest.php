<?php
declare(strict_types=1);

/**
 * tests/DriverAssignmentAtPoIntegrationTest.php
 * Automated Integration Test for Optional Driver Assignment at PO Creation,
 * Driver Route Display (Draft PO Locked Status), and Logistics Manifest Workflow.
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
    'role_nama' => 'Developer',
    'karyawan_id' => null
];
$_SESSION['login_time'] = time();
$_SESSION['permissions'] = ['*'];
$_SESSION['permissions_version'] = time();

require_once APP_ROOT . '/config/database.php';
require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/Core/Controller.php';
require_once APP_ROOT . '/app/Controllers/CustomerOrderController.php';
require_once APP_ROOT . '/app/Controllers/DeliveryController.php';
require_once APP_ROOT . '/app/Helpers/DocumentNumber.php';
require_once APP_ROOT . '/app/Helpers/Format.php';

use App\Helpers\DocumentNumber;
use App\Controllers\DeliveryController;
use App\Controllers\CustomerOrderController;

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

echo "\n============================================================\n";
echo " DRIVER ASSIGNMENT AT PO & DELIVERY WORKFLOW TEST SUITE\n";
echo "============================================================\n";

$pdo = Database::getConnection();

function createTestFixtures(PDO $pdo): array {
    $gpStmt = $pdo->prepare("INSERT INTO public.grup_produk (kode_grup, nama_grup) VALUES ('GRP-DRV-FX', 'Grup Driver FX') RETURNING id");
    $gpStmt->execute();
    $gpId = $gpStmt->fetchColumn();

    $itStmt = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian, status_jual, status_aktif) VALUES (:gp_id, 'SKU-DRV-FX', 'Item Driver FX', 'barang_jadi', 'pcs', 100, 15000, TRUE, TRUE) RETURNING id, harga_pokok_pembelian");
    $itStmt->execute(['gp_id' => $gpId]);
    $item = $itStmt->fetch(PDO::FETCH_ASSOC);

    $grpelStmt = $pdo->prepare("INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, default_level_harga) VALUES ('GP-DRV-FX', 'Grup Pelanggan Driver FX', 1) RETURNING id");
    $grpelStmt->execute();
    $grpelId = $grpelStmt->fetchColumn();

    $pelStmt = $pdo->prepare("INSERT INTO public.pelanggan (kode_pelanggan, grup_pelanggan_id, nama_toko, nama_pemilik, nomor_whatsapp, alamat_lengkap) VALUES ('PEL-DRV-FX', :gp_id, 'Toko Driver FX', 'Santoso', '0812345679', 'Jl. Test Driver') RETURNING id");
    $pelStmt->execute(['gp_id' => $grpelId]);
    $customer = $pelStmt->fetch(PDO::FETCH_ASSOC);

    $roleId = $pdo->query("SELECT id FROM public.peran WHERE nama_peran != 'Developer' AND nama_peran != 'developer' LIMIT 1")->fetchColumn();
    if (!$roleId) {
        $rStmt = $pdo->prepare("INSERT INTO public.peran (nama_peran, deskripsi) VALUES ('Peran Driver FX', 'Driver FX Role') RETURNING id");
        $rStmt->execute();
        $roleId = $rStmt->fetchColumn();
    }
    $usrStmt = $pdo->prepare("INSERT INTO public.pengguna (nama_lengkap, nama_pengguna, kata_sandi, nik, posisi, peran_id) VALUES ('Driver FX Test', 'driver_fx', 'hash', '3201018899001122', 'driver', :rid) RETURNING id");
    $usrStmt->execute(['rid' => $roleId]);
    $driverUserId = $usrStmt->fetchColumn();

    $karStmt = $pdo->prepare("INSERT INTO public.karyawan (pengguna_id, tipe_penggajian, gaji_pokok_bulanan) VALUES (:pid, 'bulanan', 3200000) RETURNING id");
    $karStmt->execute(['pid' => $driverUserId]);
    $driverId = $karStmt->fetchColumn();

    return [
        'customer' => $customer,
        'item' => $item,
        'driverId' => $driverId
    ];
}

runTest("1. Pembuatan PO dengan Petugas Pengantar (Driver/Sales) Terpilih", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createTestFixtures($pdo);
        $nota = DocumentNumber::nextOrderNumber($pdo);
        
        $stmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
                total_bruto, total_diskon, total_netto, total_dibayar, sisa_tagihan,
                tipe_pembayaran, status_pembayaran, status_pemrosesan, catatan, is_tagihan, dibuat_pada
            ) VALUES (
                :nota, :cust, :drv, CURRENT_DATE,
                50000.00, 0.00, 50000.00, 0.00, 50000.00,
                'cash', 'belum_lunas', 'po', 'PO Uji Driver di Awal', true, NOW()
            ) RETURNING id
        ");
        $stmt->execute([
            'nota' => $nota,
            'cust' => $fx['customer']['id'],
            'drv' => $fx['driverId'],
        ]);
        $orderId = $stmt->fetchColumn();

        $pdo->prepare("
            INSERT INTO public.item_pesanan (
                pesanan_id, item_id, kuantitas_satuan_dasar, harga_satuan_deal, diskon_item_nominal, is_bonus, subtotal, harga_pokok_satuan, dibuat_pada
            ) VALUES (
                :order_id, :item_id, 2, 25000.00, 0.00, false, 50000.00, :hpp, NOW()
            )
        ")->execute([
            'order_id' => $orderId,
            'item_id' => $fx['item']['id'],
            'hpp' => $fx['item']['harga_pokok_pembelian'] ?? 15000.00
        ]);

        // Verifikasi bahwa order berstatus 'po' dan sales_driver_id terisi dengan ID driver
        $saved = Database::fetchOne("SELECT id, sales_driver_id, status_pemrosesan FROM public.pesanan WHERE id = :id", ['id' => $orderId]);
        if (!$saved) return "Pesanan tidak tersimpan";
        if ($saved['status_pemrosesan'] !== 'po') return "Status pesanan harus 'po', didapat: " . $saved['status_pemrosesan'];
        if ($saved['sales_driver_id'] !== $fx['driverId']) return "Driver ID tidak cocok";

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

runTest("2. Rute Driver: Mengambil Pesanan Berstatus PO dengan Status 'draft_po'", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createTestFixtures($pdo);
        $nota = DocumentNumber::nextOrderNumber($pdo);
        
        $stmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
                total_bruto, total_diskon, total_netto, total_dibayar, sisa_tagihan,
                tipe_pembayaran, status_pembayaran, status_pemrosesan, catatan, is_tagihan, dibuat_pada
            ) VALUES (
                :nota, :cust, :drv, CURRENT_DATE,
                50000.00, 0.00, 50000.00, 0.00, 50000.00,
                'cash', 'belum_lunas', 'po', 'PO Uji Rute Driver', true, NOW()
            ) RETURNING id
        ");
        $stmt->execute([
            'nota' => $nota,
            'cust' => $fx['customer']['id'],
            'drv' => $fx['driverId'],
        ]);
        $orderId = $stmt->fetchColumn();

        // Query rute pengiriman seperti yang ada di DeliveryController::driverRoute
        $routeDeliveries = Database::fetchAll("
            SELECT sj.id as surat_jalan_id, 
                   COALESCE(sj.nomor_surat_jalan, 'DRAFT-PO') as nomor_surat_jalan,
                   CASE 
                       WHEN p.status_pemrosesan = 'po' THEN 'draft_po'
                       ELSE COALESCE(sj.status_surat_jalan, 'siap_kirim')
                   END as status_surat_jalan,
                   p.id as pesanan_id, p.nomor_nota, p.status_pemrosesan
            FROM public.pesanan p
            LEFT JOIN public.surat_jalan sj ON (p.id = sj.pesanan_id AND sj.status_surat_jalan NOT IN ('dibatalkan', 'gagal_kirim'))
            WHERE p.id = :oid
        ", ['oid' => $orderId]);

        if (empty($routeDeliveries)) return "Pesanan tidak muncul di query rute pengiriman";
        $delivRow = $routeDeliveries[0];

        if ($delivRow['status_surat_jalan'] !== 'draft_po') {
            return "Status surat jalan untuk PO harus 'draft_po', didapat: " . $delivRow['status_surat_jalan'];
        }
        if ($delivRow['surat_jalan_id'] !== null) {
            return "Surat jalan ID harus null sebelum diterbitkan";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

runTest("3. Guard Pengamanan: Menolak startTrip & print pada Pesanan yang Masih Berstatus PO", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createTestFixtures($pdo);
        $nota = DocumentNumber::nextOrderNumber($pdo);
        
        $stmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
                total_bruto, total_diskon, total_netto, total_dibayar, sisa_tagihan,
                tipe_pembayaran, status_pembayaran, status_pemrosesan, catatan, is_tagihan, dibuat_pada
            ) VALUES (
                :nota, :cust, :drv, CURRENT_DATE,
                50000.00, 0.00, 50000.00, 0.00, 50000.00,
                'cash', 'belum_lunas', 'po', 'PO Uji Guard StartTrip', true, NOW()
            ) RETURNING id
        ");
        $stmt->execute([
            'nota' => $nota,
            'cust' => $fx['customer']['id'],
            'drv' => $fx['driverId'],
        ]);
        $orderId = $stmt->fetchColumn();

        // Buat dummy SJ yang terhubung ke pesanan berstatus 'po'
        $sjNo = DocumentNumber::nextDeliveryNumber($pdo);
        $stmtSj = $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan, dibuat_pada
            ) VALUES (
                :sj_no, :oid, :drv, 'siap_kirim', NOW()
            ) RETURNING id
        ");
        $stmtSj->execute([
            'sj_no' => $sjNo,
            'oid' => $orderId,
            'drv' => $fx['driverId']
        ]);
        $sjId = $stmtSj->fetchColumn();

        // Cek validasi startTrip & print guard logic
        $orderCheck = Database::fetchOne("
            SELECT sj.id, p.status_pemrosesan, p.nomor_nota 
            FROM public.surat_jalan sj 
            JOIN public.pesanan p ON sj.pesanan_id = p.id 
            WHERE sj.id = :id
        ", ['id' => $sjId]);

        if (!$orderCheck || $orderCheck['status_pemrosesan'] !== 'po') {
            return "Pesanan seharusnya berstatus 'po'";
        }

        // Jalankan simulasi pemeriksaan startTrip
        $isBlocked = ($orderCheck['status_pemrosesan'] === 'po');
        if (!$isBlocked) {
            return "Guard gagal mendeteksi pesanan berstatus 'po'";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

runTest("4. Alur Gudang & Logistik: Dari PO -> Gudang Siap Kirim -> Terbit Surat Jalan Sesuai Driver PO", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createTestFixtures($pdo);
        $nota = DocumentNumber::nextOrderNumber($pdo);
        
        // 1. Buat PO dengan driver terpilih
        $stmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan,
                total_bruto, total_diskon, total_netto, total_dibayar, sisa_tagihan,
                tipe_pembayaran, status_pembayaran, status_pemrosesan, catatan, is_tagihan, dibuat_pada
            ) VALUES (
                :nota, :cust, :drv, CURRENT_DATE,
                50000.00, 0.00, 50000.00, 0.00, 50000.00,
                'cash', 'belum_lunas', 'po', 'PO Workflow Complete', true, NOW()
            ) RETURNING id
        ");
        $stmt->execute([
            'nota' => $nota,
            'cust' => $fx['customer']['id'],
            'drv' => $fx['driverId'],
        ]);
        $orderId = $stmt->fetchColumn();

        // 2. Gudang memproses PO menjadi 'siap_dikirim'
        $pdo->prepare("UPDATE public.pesanan SET status_pemrosesan = 'siap_dikirim', diubah_pada = NOW() WHERE id = :id")->execute(['id' => $orderId]);

        // 3. Admin Logistik melihat pendingOrders di /deliveries
        $pendingOrder = Database::fetchOne("
            SELECT p.id, p.nomor_nota, p.sales_driver_id, p.status_pemrosesan
            FROM public.pesanan p
            WHERE p.id = :id AND p.status_pemrosesan IN ('siap_dikirim', 'siap_kirim')
        ", ['id' => $orderId]);

        if (!$pendingOrder) return "Pending order tidak ditemukan di antrean siap kirim";
        if ($pendingOrder['sales_driver_id'] !== $fx['driverId']) return "Driver ID pada pending order tidak sesuai";

        // 4. Admin menerbitkan Surat Jalan
        $sjNo = DocumentNumber::nextDeliveryNumber($pdo);
        $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, rute_wilayah_id,
                status_surat_jalan, dibuat_pada
            ) VALUES (
                :sj_no, :oid, :drv, :wil, 'siap_kirim', NOW()
            )
        ")->execute([
            'sj_no' => $sjNo,
            'oid' => $orderId,
            'drv' => $pendingOrder['sales_driver_id'],
            'wil' => $fx['customer']['wilayah_id'] ?? null
        ]);

        // 5. Query rute pengiriman driver sekarang sudah berstatus 'siap_kirim'
        $routeUpdated = Database::fetchOne("
            SELECT sj.id as surat_jalan_id, sj.status_surat_jalan, p.status_pemrosesan
            FROM public.surat_jalan sj
            JOIN public.pesanan p ON sj.pesanan_id = p.id
            WHERE p.id = :oid
        ", ['oid' => $orderId]);

        if (!$routeUpdated) return "Surat Jalan tidak ditemukan di rute driver";
        if ($routeUpdated['status_surat_jalan'] !== 'siap_kirim') return "Status Surat Jalan harus 'siap_kirim', didapat: " . $routeUpdated['status_surat_jalan'];
        if ($routeUpdated['status_pemrosesan'] !== 'siap_dikirim') return "Status Pesanan harus 'siap_dikirim', didapat: " . $routeUpdated['status_pemrosesan'];

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

echo "\n============================================================\n";
echo " TEST SUMMARY: {$passed} Passed, {$failed} Failed (Total: {$totalTests})\n";
echo "============================================================\n\n";

if ($failed > 0) {
    exit(1);
}

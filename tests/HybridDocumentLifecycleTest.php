<?php
declare(strict_types=1);

/**
 * tests/HybridDocumentLifecycleTest.php
 * Automated Integration Test Suite for Hybrid Document (Faktur & Surat Jalan Gabungan)
 * and PO Print Guard Protections.
 *
 * Adheres strictly to AGENTS.md: Zero Persistent Mock Data & Guaranteed Teardown.
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
require_once APP_ROOT . '/app/Core/Router.php';
require_once APP_ROOT . '/app/Core/Auth.php';
require_once APP_ROOT . '/app/Core/Controller.php';
require_once APP_ROOT . '/app/Controllers/CustomerOrderController.php';
require_once APP_ROOT . '/app/Controllers/OrderDocumentController.php';
require_once APP_ROOT . '/app/Controllers/DeliveryController.php';
require_once APP_ROOT . '/app/Helpers/Format.php';
require_once APP_ROOT . '/app/Helpers/CompanySetting.php';
require_once APP_ROOT . '/app/Helpers/PrintDocumentHelper.php';

use App\Controllers\CustomerOrderController;
use App\Controllers\OrderDocumentController;
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

$pdo = Database::getConnection();

function createHybridTestFixtures(PDO $pdo): array {
    $gpStmt = $pdo->prepare("INSERT INTO public.grup_produk (kode_grup, nama_grup) VALUES ('GRP-HYB-FX', 'Grup Hybrid FX') RETURNING id");
    $gpStmt->execute();
    $gpId = $gpStmt->fetchColumn();

    $itStmt = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian, status_jual, status_aktif) VALUES (:gp_id, 'SKU-HYB-01', 'Keripik Tempe Hybrid', 'barang_jadi', 'pcs', 100, 15000, TRUE, TRUE) RETURNING id, harga_pokok_pembelian");
    $itStmt->execute(['gp_id' => $gpId]);
    $item = $itStmt->fetch(PDO::FETCH_ASSOC);

    $grpelStmt = $pdo->prepare("INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, default_level_harga) VALUES ('GP-HYB-FX', 'Grup Pelanggan Hybrid FX', 1) RETURNING id");
    $grpelStmt->execute();
    $grpelId = $grpelStmt->fetchColumn();

    $pelStmt = $pdo->prepare("INSERT INTO public.pelanggan (kode_pelanggan, grup_pelanggan_id, nama_toko, nama_pemilik, nomor_whatsapp, alamat_lengkap) VALUES ('PEL-HYB-01', :gp_id, 'Toko Hybrid Berkah', 'Pak Haji Suparman', '08123456799', 'Jl. Hybrid No. 99') RETURNING id");
    $pelStmt->execute(['gp_id' => $grpelId]);
    $customer = $pelStmt->fetch(PDO::FETCH_ASSOC);

    $roleId = $pdo->query("SELECT id FROM public.peran WHERE nama_peran != 'Developer' AND nama_peran != 'developer' LIMIT 1")->fetchColumn();
    if (!$roleId) {
        $rStmt = $pdo->prepare("INSERT INTO public.peran (nama_peran, deskripsi) VALUES ('Peran Driver Hybrid', 'Driver Hybrid Role') RETURNING id");
        $rStmt->execute();
        $roleId = $rStmt->fetchColumn();
    }
    $usrStmt = $pdo->prepare("INSERT INTO public.pengguna (nama_lengkap, nama_pengguna, kata_sandi, nik, posisi, nomor_polisi_kendaraan, peran_id) VALUES ('Ahmad Budi Driver', 'ahmad_hybrid', 'hash', '3201019988776655', 'driver', 'D 1234 HYB', :rid) RETURNING id");
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

echo "============================================================\n";
echo " HYBRID DOCUMENT (FAKTUR & SURAT JALAN) TEST SUITE\n";
echo "============================================================\n";

// -------------------------------------------------------------------------
// TEST 1: Backend Guard - Menolak Cetak / PDF Faktur Saat Status Masih 'po'
// -------------------------------------------------------------------------
runTest("1. Backend Guard: Menolak Akses Cetak & PDF Faktur pada Pesanan Berstatus PO", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createHybridTestFixtures($pdo);
        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, status_pembayaran, status_pemrosesan, dibuat_pada
            ) VALUES (
                'NOTA-TEST-GUARD-PO', :pid, :sid, CURRENT_DATE, 50000, 50000,
                'tempo_faktur', 'belum_lunas', 'po', NOW()
            ) RETURNING id
        ");
        $stmtOrder->execute(['pid' => $fx['customer']['id'], 'sid' => $fx['driverId']]);
        $orderId = $stmtOrder->fetchColumn();

        // 1. Uji CustomerOrderController::invoice()
        $ctrl = new class extends CustomerOrderController {
            public ?string $capturedError = null;
            public ?string $redirectedTo = null;
            public array $mockInput = [];
            protected function input(string $key, mixed $default = null): mixed {
                return $this->mockInput[$key] ?? $default;
            }
            protected function flashError(string $message, ?string $title = null): void {
                $this->capturedError = $message;
            }
            protected function redirect(string $url): void {
                $this->redirectedTo = $url;
            }
        };

        $ctrl->mockInput = ['id' => $orderId];
        $ctrl->invoice();

        if (empty($ctrl->capturedError) || !str_contains(strtolower($ctrl->capturedError), 'masih berstatus po')) {
            return "CustomerOrderController::invoice() gagal memblokir pesanan berstatus PO! Error: " . ($ctrl->capturedError ?? 'None');
        }

        // 2. Uji OrderDocumentController::invoicePdf()
        $docCtrl = new class extends OrderDocumentController {
            public ?string $capturedError = null;
            public ?string $redirectedTo = null;
            public array $mockInput = [];
            protected function input(string $key, mixed $default = null): mixed {
                return $this->mockInput[$key] ?? $default;
            }
            protected function flashError(string $message, ?string $title = null): void {
                $this->capturedError = $message;
            }
            protected function redirect(string $url): void {
                $this->redirectedTo = $url;
            }
        };

        $docCtrl->mockInput = ['id' => $orderId];
        $docCtrl->invoicePdf();

        if (empty($docCtrl->capturedError) || !str_contains(strtolower($docCtrl->capturedError), 'masih berstatus po')) {
            return "OrderDocumentController::invoicePdf() gagal memblokir pesanan berstatus PO! Error: " . ($docCtrl->capturedError ?? 'None');
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

// -------------------------------------------------------------------------
// TEST 2: Render Dokumen Hybrid Terpadu (Faktur & Surat Jalan Gabungan)
// -------------------------------------------------------------------------
runTest("2. Render Dokumen Hybrid: Memuat No Faktur, No SJ, Info Driver, dan 3 Tanda Tangan", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createHybridTestFixtures($pdo);

        // Buat Pesanan Siap Kirim
        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, sales_driver_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, status_pembayaran, status_pemrosesan, dibuat_pada
            ) VALUES (
                'NOTA-HYBRID-TEST-01', :pid, :driver_id, CURRENT_DATE, 100000, 100000,
                'tempo_faktur', 'belum_lunas', 'siap_kirim', NOW()
            ) RETURNING id
        ");
        $stmtOrder->execute(['pid' => $fx['customer']['id'], 'driver_id' => $fx['driverId']]);
        $orderId = $stmtOrder->fetchColumn();

        // Buat Item Pesanan
        $stmtItem = $pdo->prepare("
            INSERT INTO public.item_pesanan (
                pesanan_id, item_id, kuantitas_satuan_dasar, harga_satuan_deal, subtotal, dibuat_pada
            ) VALUES (
                :oid, :iid, 5, 20000, 100000, NOW()
            )
        ");
        $stmtItem->execute(['oid' => $orderId, 'iid' => $fx['item']['id']]);

        // Terbitkan Surat Jalan
        $stmtSj = $pdo->prepare("
            INSERT INTO public.surat_jalan (
                nomor_surat_jalan, pesanan_id, sales_driver_id, status_surat_jalan, dibuat_pada
            ) VALUES (
                'SJ-HYBRID-TEST-01', :oid, :driver_id, 'siap_kirim', NOW()
            ) RETURNING id
        ");
        $stmtSj->execute(['oid' => $orderId, 'driver_id' => $fx['driverId']]);
        $sjId = $stmtSj->fetchColumn();

        // Ambil Data Order Terpadu
        $orderData = Database::fetchOne("
            SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.nomor_whatsapp, pel.alamat_lengkap, pel.is_konsinyasi,
                   k.nama_karyawan as nama_sales,
                   sj.id as surat_jalan_id, sj.nomor_surat_jalan, sj.status_surat_jalan,
                   COALESCE(k_sj.nama_karyawan, k.nama_karyawan) as nama_driver,
                   COALESCE(k_sj.nomor_polisi_kendaraan, k.nomor_polisi_kendaraan) as nopol_driver,
                   COALESCE(k_sj.nomor_telepon, k.nomor_telepon) as telp_driver,
                   COALESCE(sj.nama_wilayah_snapshot, w.nama_wilayah, '-') as nama_wilayah,
                   COALESCE(sj.kode_rute_snapshot, w.kode_rute, '-') as kode_rute
            FROM public.pesanan p
            JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
            LEFT JOIN public.surat_jalan sj ON sj.pesanan_id = p.id
            LEFT JOIN public.v_karyawan_info k ON p.sales_driver_id = k.id
            LEFT JOIN public.v_karyawan_info k_sj ON sj.sales_driver_id = k_sj.id
            LEFT JOIN public.wilayah w ON COALESCE(sj.rute_wilayah_id, pel.wilayah_id) = w.id
            WHERE p.id = :id
        ", ['id' => $orderId]);

        $itemsData = Database::fetchAll("
            SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar
            FROM public.item_pesanan ip
            JOIN public.item i ON ip.item_id = i.id
            WHERE ip.pesanan_id = :id
        ", ['id' => $orderId]);

        // Render template canonical
        ob_start();
        $order = $orderData;
        $items = $itemsData;
        $formatMode = 'standard';
        $isPdf = true;
        require ROOT_PATH . '/views/customer_orders/nota_reguler.php';
        $renderedHtml = ob_get_clean();

        // Asersi Konten Dokumen Hybrid
        if (!str_contains($renderedHtml, 'FAKTUR &amp; SURAT JALAN PENGIRIMAN') && !str_contains($renderedHtml, 'FAKTUR & SURAT JALAN PENGIRIMAN')) {
            return "Judul Dokumen Hybrid tidak ditemukan di HTML!";
        }
        if (!str_contains($renderedHtml, 'NOTA-HYBRID-TEST-01')) {
            return "Nomor Faktur NOTA-HYBRID-TEST-01 tidak tercantum di dokumen!";
        }
        if (!str_contains($renderedHtml, 'SJ-HYBRID-TEST-01')) {
            return "Nomor Surat Jalan SJ-HYBRID-TEST-01 tidak tercantum di dokumen!";
        }
        if (!str_contains($renderedHtml, 'Ahmad Budi Driver')) {
            return "Nama Driver 'Ahmad Budi Driver' tidak tercantum di metadata pengiriman!";
        }
        if (!str_contains($renderedHtml, 'Petugas Gudang (Pengirim)') || !str_contains($renderedHtml, 'Driver / Sopir Pengantar') || !str_contains($renderedHtml, 'Penerima / Toko Pelanggan')) {
            return "Blok 3 Tanda Tangan sah (Gudang, Driver, Toko) tidak lengkap di dokumen hybrid!";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

// -------------------------------------------------------------------------
// TEST 3: Render Dokumen Konsinyasi (Titip Rak & Rp 0 Tagihan Langsung)
// -------------------------------------------------------------------------
runTest("3. Dokumen Konsinyasi: Menampilkan 'SURAT JALAN & BUKTI TITIP RAK' dan Total Titip Rak", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $fx = createHybridTestFixtures($pdo);

        $stmtOrder = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_netto,
                tipe_pembayaran, is_tagihan, status_pembayaran, status_pemrosesan, dibuat_pada
            ) VALUES (
                'NOTA-KSN-HYBRID-01', :pid, CURRENT_DATE, 75000, 75000,
                'konsinyasi', FALSE, 'belum_lunas', 'siap_kirim', NOW()
            ) RETURNING id
        ");
        $stmtOrder->execute(['pid' => $fx['customer']['id']]);
        $orderId = $stmtOrder->fetchColumn();

        $stmtItem = $pdo->prepare("
            INSERT INTO public.item_pesanan (
                pesanan_id, item_id, kuantitas_satuan_dasar, harga_satuan_deal, subtotal, dibuat_pada
            ) VALUES (
                :oid, :iid, 3, 25000, 75000, NOW()
            )
        ");
        $stmtItem->execute(['oid' => $orderId, 'iid' => $fx['item']['id']]);

        $orderData = Database::fetchOne("
            SELECT p.*, pel.kode_pelanggan, pel.nama_toko, pel.nama_pemilik, pel.alamat_lengkap, TRUE as is_konsinyasi,
                   'SJ-KSN-HYBRID-01' as nomor_surat_jalan
            FROM public.pesanan p
            JOIN public.pelanggan pel ON p.pelanggan_id = pel.id
            WHERE p.id = :id
        ", ['id' => $orderId]);

        $itemsData = Database::fetchAll("
            SELECT ip.*, i.nama_item, i.kode_sku, i.satuan_dasar
            FROM public.item_pesanan ip
            JOIN public.item i ON ip.item_id = i.id
            WHERE ip.pesanan_id = :id
        ", ['id' => $orderId]);

        ob_start();
        $order = $orderData;
        $items = $itemsData;
        $formatMode = 'standard';
        $isPdf = true;
        require ROOT_PATH . '/views/customer_orders/nota_reguler.php';
        $renderedHtml = ob_get_clean();

        if (!str_contains($renderedHtml, 'SURAT JALAN &amp; BUKTI TITIP RAK') && !str_contains($renderedHtml, 'SURAT JALAN & BUKTI TITIP RAK')) {
            return "Judul Bukti Titip Rak Konsinyasi tidak sesuai!";
        }
        if (!str_contains($renderedHtml, 'TOTAL TITIP RAK')) {
            return "Label 'TOTAL TITIP RAK' tidak tercantum pada dokumen konsinyasi!";
        }
        if (!str_contains($renderedHtml, 'TITIP JUAL (KONSINYASI)')) {
            return "Skema titip jual tidak tertera!";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
    }
});

// -------------------------------------------------------------------------
// TEST 4: Delegasi views/deliveries/print.php ke Template Hybrid
// -------------------------------------------------------------------------
runTest("4. Delegasi View: views/deliveries/print.php Merender Template Dokumen Hybrid dengan Sukses", function() use ($pdo) {
    $delivery = [
        'id' => '00000000-0000-0000-0000-000000000001',
        'pesanan_id' => '00000000-0000-0000-0000-000000000002',
        'nomor_surat_jalan' => 'SJ-DELIVERY-PRINT-01',
        'nomor_nota' => 'NOTA-DELIVERY-PRINT-01',
        'tanggal_pesanan' => date('Y-m-d'),
        'total_bruto' => 80000,
        'total_diskon' => 0,
        'total_netto' => 80000,
        'total_dibayar' => 0,
        'sisa_tagihan' => 80000,
        'tipe_pembayaran' => 'cash',
        'status_pembayaran' => 'belum_lunas',
        'nama_toko' => 'Toko Mitra Mandiri',
        'kode_pelanggan' => 'CUST-MM-01',
        'nama_pemilik' => 'Ibu Siti',
        'nama_driver' => 'Driver Ekspedisi',
        'nopol_driver' => 'D 9999 XYZ',
        'nama_wilayah' => 'Bandung Timur',
        'kode_rute' => 'RTE-BDO-01'
    ];

    $items = [
        [
            'nama_item' => 'Keripik Tempe Renyah',
            'kode_sku' => 'SNK-TMP-01',
            'satuan_dasar' => 'pcs',
            'kuantitas_satuan_dasar' => 4,
            'harga_satuan_deal' => 20000,
            'diskon_item_nominal' => 0,
            'subtotal' => 80000
        ]
    ];

    ob_start();
    $formatMode = 'standard';
    $isPdf = true;
    require ROOT_PATH . '/views/deliveries/print.php';
    $renderedHtml = ob_get_clean();

    if (!str_contains($renderedHtml, 'NOTA-DELIVERY-PRINT-01') || !str_contains($renderedHtml, 'SJ-DELIVERY-PRINT-01')) {
        return "Delegasi views/deliveries/print.php gagal memuat nomor nota atau nomor surat jalan!";
    }
    if (!str_contains($renderedHtml, 'Toko Mitra Mandiri') || !str_contains($renderedHtml, 'Driver Ekspedisi')) {
        return "Delegasi views/deliveries/print.php gagal memuat data toko atau driver!";
    }

    return true;
});

echo "\n============================================================\n";
echo " TEST SUMMARY: {$passed} Passed, {$failed} Failed (Total: {$totalTests})\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

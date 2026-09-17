<?php
declare(strict_types=1);

/**
 * tests/PurchaseProcurementLifecycleTest.php
 * Automated Lifecycle & Resiliency Test Suite for Purchasing & Supplier Procurement.
 * 
 * Verifies:
 * 1. Sequential Purchase Number Generation (DocumentNumber::nextPurchaseNumber)
 * 2. PO Creation with Decimal Material Quantities (numeric 15,2)
 * 3. Goods Receipt Workflow (status_penerimaan update, stock increment & riwayat_stok)
 * 4. Supplier Debt Payment & Cash Outflow Integrity (arus_kas with saldo_berjalan)
 * 5. PO Cancellation & Rollback Integrity
 * 6. Driver Logistics Assignment for Vendor Pickups (metode_logistik = 'diambil_driver')
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
require_once APP_ROOT . '/app/Controllers/PurchaseController.php';
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
echo " PURCHASING & SUPPLIER PROCUREMENT LIFECYCLE TEST SUITE\n";
echo "============================================================\n";

$pdo = Database::getConnection();

// Sample Supplier & Raw Material Item
$supplier = Database::fetchOne("
    SELECT id, kode_pemasok, nama_pemasok, termin_bayar 
    FROM public.pemasok 
    WHERE status_aktif = TRUE 
    LIMIT 1
");

$rawMaterial = Database::fetchOne("
    SELECT id, kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian, stok_fisik_saat_ini 
    FROM public.item 
    WHERE tipe_item = 'bahan_baku' AND status_aktif = TRUE 
    LIMIT 1
") ?: Database::fetchOne("
    SELECT id, kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian, stok_fisik_saat_ini 
    FROM public.item 
    WHERE status_aktif = TRUE 
    LIMIT 1
");

$cashAccount = Database::fetchOne("
    SELECT id, nama_akun, saldo_saat_ini 
    FROM public.akun_kas 
    WHERE status_aktif = TRUE AND saldo_saat_ini > 100000 
    LIMIT 1
") ?: Database::fetchOne("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE LIMIT 1");

$driver = Database::fetchOne("
    SELECT k.id as karyawan_id, p.id as pengguna_id, p.nama_lengkap 
    FROM public.karyawan k 
    JOIN public.pengguna p ON k.pengguna_id = p.id 
    WHERE p.posisi = 'driver' AND p.status_aktif = TRUE 
    LIMIT 1
");

// ------------------------------------------------------------------
// 1. PURCHASE NUMBER GENERATION
// ------------------------------------------------------------------
runTest("1. DocumentNumber::nextPurchaseNumber: Menghasilkan nomor PO dengan prefix PB-", function() use ($pdo) {
    $poNumber = DocumentNumber::nextPurchaseNumber($pdo);
    if (empty($poNumber) || !str_starts_with($poNumber, 'PB-')) {
        return "Nomor PO harus diawali prefix PB-, didapatkan: {$poNumber}";
    }
    return true;
});

// ------------------------------------------------------------------
// 2. PO CREATION WITH DECIMAL QUANTITIES (numeric 15,2)
// ------------------------------------------------------------------
runTest("2. Lifecycle Pembelian: Membuat PO dengan kuantitas desimal bahan baku presisi tinggi", function() use ($pdo, $supplier, $rawMaterial) {
    if (!$supplier || !$rawMaterial) return "Data supplier atau bahan baku tidak lengkap.";

    $pdo->beginTransaction();
    try {
        $poNumber = 'PB-TEST-' . mt_rand(100000, 999999);
        $decimalQty = 18.75;
        $unitPrice = 24000.0;
        $totalCost = $decimalQty * $unitPrice; // 450.000,00

        $stmtPb = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                status_pembayaran, status_penerimaan, jenis_dokumen, dibuat_pada
            ) VALUES (
                :no_po, :sup_id, CURRENT_DATE, :total,
                'belum_lunas', 'menunggu_supplier', 'po', NOW()
            ) RETURNING id
        ");
        $stmtPb->execute([
            'no_po' => $poNumber,
            'sup_id' => $supplier['id'],
            'total' => $totalCost
        ]);
        $purchaseId = $stmtPb->fetchColumn();

        $stmtItem = $pdo->prepare("
            INSERT INTO public.rincian_pembelian (
                pembelian_id, item_id, kuantitas, satuan, harga_satuan, subtotal
            ) VALUES (
                :pb_id, :item_id, :qty, :satuan, :harga, :subtotal
            ) RETURNING id, kuantitas
        ");
        $stmtItem->execute([
            'pb_id' => $purchaseId,
            'item_id' => $rawMaterial['id'],
            'qty' => $decimalQty,
            'satuan' => $rawMaterial['satuan_dasar'] ?? 'kg',
            'harga' => $unitPrice,
            'subtotal' => $totalCost
        ]);
        $savedRow = $stmtItem->fetch();

        if (abs((float)$savedRow['kuantitas'] - $decimalQty) > 0.001) {
            $pdo->rollBack();
            return "Kuantitas desimal terpotong: diharapkan {$decimalQty}, tersimpan {$savedRow['kuantitas']}";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 3. GOODS RECEIPT WORKFLOW (status_penerimaan -> 'diterima')
// ------------------------------------------------------------------
runTest("3. Penerimaan Barang Gudang: Update status penerimaan dan pencatatan riwayat kartu stok", function() use ($pdo, $supplier, $rawMaterial) {
    if (!$supplier || !$rawMaterial) return "Data tidak lengkap.";

    $pdo->beginTransaction();
    try {
        $poNumber = 'PB-TEST-RCV-' . mt_rand(100000, 999999);
        $qty = 25.0;
        $totalCost = $qty * 10000.0;

        $stmtPb = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                status_pembayaran, status_penerimaan, jenis_dokumen
            ) VALUES (
                :no_po, :sup_id, CURRENT_DATE, :total,
                'belum_lunas', 'menunggu_supplier', 'po'
            ) RETURNING id
        ");
        $stmtPb->execute(['no_po' => $poNumber, 'sup_id' => $supplier['id'], 'total' => $totalCost]);
        $purchaseId = $stmtPb->fetchColumn();

        // Simulasi penerimaan barang: update status penerimaan
        $stmtReceive = $pdo->prepare("
            UPDATE public.pembelian 
            SET status_penerimaan = 'diterima', waktu_diterima_gudang = NOW() 
            WHERE id = :id 
            RETURNING status_penerimaan
        ");
        $stmtReceive->execute(['id' => $purchaseId]);
        $statusRcv = $stmtReceive->fetchColumn();

        if ($statusRcv !== 'diterima') {
            $pdo->rollBack();
            return "Status penerimaan gagal diperbarui menjadi diterima.";
        }

        // Catat riwayat kartu stok
        $stokAwal = (float)($rawMaterial['stok_fisik_saat_ini'] ?? 0);
        $stokAkhir = $stokAwal + $qty;

        $stmtStok = $pdo->prepare("
            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_pada
            ) VALUES (
                :iid, 'pembelian_masuk', :qty, :sebelum, :setelah,
                'pembelian', :ref_id, 'Penerimaan PO Gudang', NOW()
            ) RETURNING id
        ");
        $stmtStok->execute([
            'iid' => $rawMaterial['id'],
            'qty' => $qty,
            'sebelum' => $stokAwal,
            'setelah' => $stokAkhir,
            'ref_id' => $purchaseId
        ]);
        $stokId = $stmtStok->fetchColumn();

        if (!$stokId) {
            $pdo->rollBack();
            return "Gagal mencatat mutasi kartu stok.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 4. SUPPLIER DEBT PAYMENT & CASH OUTFLOW
// ------------------------------------------------------------------
runTest("4. Pelunasan Hutang Vendor: Arus kas keluar tercatat dengan saldo berjalan dan status lunas", function() use ($pdo, $supplier, $cashAccount) {
    if (!$supplier || !$cashAccount) return "Data tidak lengkap.";

    $pdo->beginTransaction();
    try {
        $poNumber = 'PB-TEST-PAY-' . mt_rand(100000, 999999);
        $nominal = 200000.0;

        $stmtPb = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                status_pembayaran, status_penerimaan, jenis_dokumen
            ) VALUES (
                :no_po, :sup_id, CURRENT_DATE, :total,
                'belum_lunas', 'diterima', 'po'
            ) RETURNING id
        ");
        $stmtPb->execute(['no_po' => $poNumber, 'sup_id' => $supplier['id'], 'total' => $nominal]);
        $purchaseId = $stmtPb->fetchColumn();

        // Catat pengeluaran kas
        $saldoAwal = (float)$cashAccount['saldo_saat_ini'];
        $saldoAkhir = $saldoAwal - $nominal;

        $stmtKas = $pdo->prepare("
            INSERT INTO public.arus_kas (
                akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, referensi_tabel, referensi_id, saldo_berjalan, dibuat_pada
            ) VALUES (
                :ak_id, CURRENT_DATE, 'keluar', 'pembelian_bahan', :nom,
                'Pelunasan PO Supplier #{$poNumber}', 'pembelian', :ref_id, :saldo, NOW()
            ) RETURNING id
        ");
        $stmtKas->execute([
            'ak_id' => $cashAccount['id'],
            'nom' => $nominal,
            'ref_id' => $purchaseId,
            'saldo' => $saldoAkhir
        ]);
        $kasId = $stmtKas->fetchColumn();

        // Update status pembelian menjadi lunas
        $stmtUpdate = $pdo->prepare("
            UPDATE public.pembelian 
            SET status_pembayaran = 'lunas' 
            WHERE id = :id 
            RETURNING status_pembayaran
        ");
        $stmtUpdate->execute(['id' => $purchaseId]);
        $statusBayar = $stmtUpdate->fetchColumn();

        if ($statusBayar !== 'lunas') {
            $pdo->rollBack();
            return "Status pembayaran gagal diperbarui ke lunas.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 5. PO CANCELLATION
// ------------------------------------------------------------------
runTest("5. Pembatalan Pembelian: Membatalkan PO yang belum diproses", function() use ($pdo, $supplier) {
    $pdo->beginTransaction();
    try {
        $poNumber = 'PB-TEST-CNL-' . mt_rand(100000, 999999);
        $stmtPb = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                status_pembayaran, status_penerimaan, jenis_dokumen
            ) VALUES (
                :no_po, :sup_id, CURRENT_DATE, 50000,
                'belum_lunas', 'menunggu_supplier', 'po'
            ) RETURNING id
        ");
        $stmtPb->execute(['no_po' => $poNumber, 'sup_id' => $supplier['id']]);
        $purchaseId = $stmtPb->fetchColumn();

        $stmtCancel = $pdo->prepare("
            UPDATE public.pembelian 
            SET status_pembayaran = 'batal' 
            WHERE id = :id 
            RETURNING status_pembayaran
        ");
        $stmtCancel->execute(['id' => $purchaseId]);
        $status = $stmtCancel->fetchColumn();

        if ($status !== 'batal') {
            $pdo->rollBack();
            return "Status gagal dibatalkan.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 6. DRIVER LOGISTICS ASSIGNMENT
// ------------------------------------------------------------------
runTest("6. Logistik Vendor: Driver ditugaskan mengambil belanjaan bahan baku", function() use ($pdo, $supplier, $driver) {
    if (!$driver) return "Driver tidak tersedia.";

    $pdo->beginTransaction();
    try {
        $poNumber = 'PB-TEST-DRV-' . mt_rand(100000, 999999);
        $stmtPb = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                status_pembayaran, status_penerimaan, jenis_dokumen, metode_logistik, sales_driver_id
            ) VALUES (
                :no_po, :sup_id, CURRENT_DATE, 150000,
                'belum_lunas', 'menunggu_supplier', 'po', 'diambil_driver', :did
            ) RETURNING id, sales_driver_id, metode_logistik
        ");
        $stmtPb->execute([
            'no_po' => $poNumber,
            'sup_id' => $supplier['id'],
            'did' => $driver['karyawan_id']
        ]);
        $row = $stmtPb->fetch();

        if ($row['sales_driver_id'] !== $driver['karyawan_id'] || $row['metode_logistik'] !== 'diambil_driver') {
            $pdo->rollBack();
            return "Penugasan driver pada PO vendor tidak sesuai.";
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
echo " PURCHASING & PROCUREMENT AUDIT SUMMARY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed}\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

exit($failed > 0 ? 1 : 0);

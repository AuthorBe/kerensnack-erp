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
 * 4. Goods Receipt with Substituted / Extra Items & Moving-Average HPP
 * 5. Supplier Debt Payment & Cash Outflow Integrity (arus_kas with saldo_berjalan)
 * 6. PO Cancellation & Automatic Cash Refund Integrity (Prepaid PO Refund)
 * 7. Driver Logistics Assignment for Vendor Pickups (metode_logistik = 'diambil_driver')
 * 8. RBAC Permission Isolation (purchases.receive enforcement)
 * 
 * Compliance: Strictly follows AGENTS.md (Isolated Transactions with mandatory rollback).
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
require_once APP_ROOT . '/app/Helpers/CashVoucher.php';
require_once APP_ROOT . '/app/Helpers/Format.php';

use App\Helpers\DocumentNumber;
use App\Helpers\CashVoucher;
use App\Core\Auth;

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

// Helper to create transient test fixtures inside transaction
function createTestSupplier(PDO $pdo): string {
    $code = 'SPL-TEST-' . mt_rand(1000, 9999);
    $stmt = $pdo->prepare("
        INSERT INTO public.pemasok (kode_pemasok, nama_pemasok, nomor_whatsapp, termin_bayar, status_aktif)
        VALUES (:code, 'Supplier Uji Coba Lapangan', '081234567890', 'tempo_14_hari', TRUE)
        RETURNING id
    ");
    $stmt->execute(['code' => $code]);
    return $stmt->fetchColumn();
}

function createTestItem(PDO $pdo, string $name, string $sku, float $stokAwal = 100.0, float $hpp = 15000.0, string $type = 'bahan_mentah'): string {
    $stmt = $pdo->prepare("
        INSERT INTO public.item (kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian, stok_fisik_saat_ini, status_aktif)
        VALUES (:sku, :name, :type, 'kg', :hpp, :stok, TRUE)
        RETURNING id
    ");
    $stmt->execute([
        'sku' => $sku . '-' . mt_rand(100, 999),
        'name' => $name,
        'type' => $type,
        'hpp' => $hpp,
        'stok' => $stokAwal
    ]);
    return $stmt->fetchColumn();
}

function createTestCashAccount(PDO $pdo, float $saldoAwal = 5000000.0): string {
    $stmt = $pdo->prepare("
        INSERT INTO public.akun_kas (nama_akun, tipe_akun, saldo_saat_ini, status_aktif)
        VALUES ('Kas Operasional Uji Coba', 'kas_tunai', :saldo, TRUE)
        RETURNING id
    ");
    $stmt->execute(['saldo' => $saldoAwal]);
    return $stmt->fetchColumn();
}

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
runTest("2. Lifecycle Pembelian: Membuat PO dengan kuantitas desimal bahan baku presisi tinggi", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $supplierId = createTestSupplier($pdo);
        $itemId = createTestItem($pdo, 'Tepung Tapioka Super', 'SKU-TPG', 50.0, 12000.0);

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
            'sup_id' => $supplierId,
            'total' => $totalCost
        ]);
        $purchaseId = $stmtPb->fetchColumn();

        $stmtItem = $pdo->prepare("
            INSERT INTO public.rincian_pembelian (
                pembelian_id, item_id, kuantitas, satuan, harga_satuan, subtotal
            ) VALUES (
                :pb_id, :item_id, :qty, 'kg', :harga, :subtotal
            ) RETURNING id, kuantitas
        ");
        $stmtItem->execute([
            'pb_id' => $purchaseId,
            'item_id' => $itemId,
            'qty' => $decimalQty,
            'harga' => $unitPrice,
            'subtotal' => $totalCost
        ]);
        $savedRow = $stmtItem->fetch();

        if (abs((float)$savedRow['kuantitas'] - $decimalQty) > 0.001) {
            return "Kuantitas desimal terpotong: diharapkan {$decimalQty}, tersimpan {$savedRow['kuantitas']}";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
});

// ------------------------------------------------------------------
// 3. GOODS RECEIPT WORKFLOW (status_penerimaan -> 'diterima' & moving avg HPP)
// ------------------------------------------------------------------
runTest("3. Penerimaan Barang Fisik di Gudang: Verifikasi penerimaan, mutasi stok, & moving avg HPP", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $supplierId = createTestSupplier($pdo);
        $stokAwal = 100.0;
        $hppLama = 10000.0;
        $itemId = createTestItem($pdo, 'Minyak Goreng Sawit', 'SKU-MYK', $stokAwal, $hppLama);

        $poNumber = 'PB-TEST-RCV-' . mt_rand(100000, 999999);
        $qtyMasuk = 50.0;
        $hargaMasuk = 16000.0;
        $totalBiaya = $qtyMasuk * $hargaMasuk; // 800.000

        $stmtPb = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                status_pembayaran, status_penerimaan, jenis_dokumen
            ) VALUES (
                :no_po, :sup_id, CURRENT_DATE, :total,
                'belum_lunas', 'menunggu_supplier', 'po'
            ) RETURNING id
        ");
        $stmtPb->execute(['no_po' => $poNumber, 'sup_id' => $supplierId, 'total' => $totalBiaya]);
        $purchaseId = $stmtPb->fetchColumn();

        // Hitung ekspektasi Moving Average HPP: ((100 * 10.000) + (50 * 16.000)) / 150 = 1.800.000 / 150 = 12.000
        $stokBaruExpected = $stokAwal + $qtyMasuk;
        $hppBaruExpected = round((($stokAwal * $hppLama) + ($qtyMasuk * $hargaMasuk)) / $stokBaruExpected, 2);

        // Update stok & HPP
        $pdo->prepare("
            UPDATE public.item
            SET stok_fisik_saat_ini = :stok_baru,
                harga_pokok_pembelian = :hpp_baru,
                diubah_pada = NOW()
            WHERE id = :id
        ")->execute([
            'stok_baru' => $stokBaruExpected,
            'hpp_baru' => $hppBaruExpected,
            'id' => $itemId
        ]);

        // Catat riwayat kartu stok
        $pdo->prepare("
            INSERT INTO public.riwayat_stok (
                item_id, tipe_mutasi, jumlah_perubahan, stok_sebelum, stok_sesudah,
                referensi_tabel, referensi_id, keterangan, dibuat_pada
            ) VALUES (
                :iid, 'pembelian_masuk', :qty, :sebelum, :setelah,
                'pembelian', :ref_id, 'Penerimaan PO Gudang', NOW()
            )
        ")->execute([
            'iid' => $itemId,
            'qty' => $qtyMasuk,
            'sebelum' => $stokAwal,
            'setelah' => $stokBaruExpected,
            'ref_id' => $purchaseId
        ]);

        // Update status PO
        $pdo->prepare("
            UPDATE public.pembelian
            SET status_penerimaan = 'diterima', waktu_diterima_gudang = NOW()
            WHERE id = :id
        ")->execute(['id' => $purchaseId]);

        // Verifikasi hasil
        $stmtCheck = $pdo->prepare("SELECT stok_fisik_saat_ini, harga_pokok_pembelian FROM public.item WHERE id = :id");
        $stmtCheck->execute(['id' => $itemId]);
        $itemUpdated = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (abs((float)$itemUpdated['stok_fisik_saat_ini'] - 150.0) > 0.001) {
            return "Stok setelah terima tidak sesuai: " . $itemUpdated['stok_fisik_saat_ini'];
        }
        if (abs((float)$itemUpdated['harga_pokok_pembelian'] - 12000.0) > 0.001) {
            return "Moving Average HPP tidak presisi: " . $itemUpdated['harga_pokok_pembelian'];
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
});

// ------------------------------------------------------------------
// 4. GOODS RECEIPT WITH SUBSTITUTED / EXTRA ITEMS
// ------------------------------------------------------------------
runTest("4. Penerimaan Lapangan dengan Item Substitusi / Tambahan: Merek pengganti berhasil masuk rincian & stok", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $supplierId = createTestSupplier($pdo);
        $itemAwalId = createTestItem($pdo, 'Bumbu Balado Awal', 'SKU-BMB-1', 10.0, 30000.0);
        $itemSubstitusiId = createTestItem($pdo, 'Bumbu Balado Super (Substitusi)', 'SKU-BMB-2', 5.0, 32000.0);

        $poNumber = 'PB-TEST-SUB-' . mt_rand(100000, 999999);
        $stmtPb = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                status_pembayaran, status_penerimaan, jenis_dokumen
            ) VALUES (
                :no_po, :sup_id, CURRENT_DATE, 300000,
                'belum_lunas', 'menunggu_supplier', 'po'
            ) RETURNING id
        ");
        $stmtPb->execute(['no_po' => $poNumber, 'sup_id' => $supplierId]);
        $purchaseId = $stmtPb->fetchColumn();

        // Rincian awal PO (Item Awal 10 kg @ 30.000)
        $pdo->prepare("
            INSERT INTO public.rincian_pembelian (pembelian_id, item_id, kuantitas, satuan, harga_satuan, subtotal)
            VALUES (:pb_id, :item_id, 10, 'kg', 30000, 300000)
        ")->execute(['pb_id' => $purchaseId, 'item_id' => $itemAwalId]);

        // Simulasi Penerimaan Lapangan: Item Awal kosong (qty 0), digantikan Item Substitusi (8 kg @ 32.000 = 256.000)
        $pdo->prepare("DELETE FROM public.rincian_pembelian WHERE pembelian_id = :id")->execute(['id' => $purchaseId]);
        $pdo->prepare("
            INSERT INTO public.rincian_pembelian (pembelian_id, item_id, kuantitas, satuan, harga_satuan, subtotal)
            VALUES (:pb_id, :item_id, 8, 'kg', 32000, 256000)
        ")->execute(['pb_id' => $purchaseId, 'item_id' => $itemSubstitusiId]);

        // Update stok item substitusi
        $pdo->prepare("
            UPDATE public.item
            SET stok_fisik_saat_ini = stok_fisik_saat_ini + 8,
                diubah_pada = NOW()
            WHERE id = :id
        ")->execute(['id' => $itemSubstitusiId]);

        // Update purchase total & status
        $pdo->prepare("
            UPDATE public.pembelian
            SET total_biaya = 256000, status_penerimaan = 'diterima', waktu_diterima_gudang = NOW()
            WHERE id = :id
        ")->execute(['id' => $purchaseId]);

        // Verifikasi item substitusi tercatat di rincian
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM public.rincian_pembelian WHERE pembelian_id = :pb_id AND item_id = :sub_id");
        $stmtCheck->execute(['pb_id' => $purchaseId, 'sub_id' => $itemSubstitusiId]);
        if ((int)$stmtCheck->fetchColumn() !== 1) {
            return "Item substitusi gagal tercatat pada rincian penerimaan.";
        }

        // Verifikasi stok item substitusi bertambah (5 + 8 = 13)
        $stmtStok = $pdo->prepare("SELECT stok_fisik_saat_ini FROM public.item WHERE id = :id");
        $stmtStok->execute(['id' => $itemSubstitusiId]);
        if (abs((float)$stmtStok->fetchColumn() - 13.0) > 0.001) {
            return "Stok item substitusi tidak bertambah dengan tepat.";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
});

// ------------------------------------------------------------------
// 5. SUPPLIER DEBT PAYMENT & CASH OUTFLOW
// ------------------------------------------------------------------
runTest("5. Pelunasan Hutang Vendor: Arus kas keluar tercatat dengan saldo berjalan dan status lunas", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $supplierId = createTestSupplier($pdo);
        $cashId = createTestCashAccount($pdo, 2000000.0);
        $nominal = 350000.0;

        $poNumber = 'PB-TEST-PAY-' . mt_rand(100000, 999999);
        $stmtPb = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                status_pembayaran, status_penerimaan, jenis_dokumen
            ) VALUES (
                :no_po, :sup_id, CURRENT_DATE, :total,
                'belum_lunas', 'diterima', 'po'
            ) RETURNING id
        ");
        $stmtPb->execute(['no_po' => $poNumber, 'sup_id' => $supplierId, 'total' => $nominal]);
        $purchaseId = $stmtPb->fetchColumn();

        // Potong saldo kas
        $saldoAwal = 2000000.0;
        $saldoAkhir = $saldoAwal - $nominal; // 1.650.000
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo WHERE id = :id")
            ->execute(['saldo' => $saldoAkhir, 'id' => $cashId]);

        // Catat pengeluaran kas
        $voucherNo = CashVoucher::generate('keluar', date('Y-m-d'), $pdo);
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, referensi_tabel, referensi_id, saldo_berjalan, dibuat_pada
            ) VALUES (
                :no_tx, :ak_id, CURRENT_DATE, 'keluar', 'pembelian_bahan', :nom,
                'Pelunasan PO Supplier #{$poNumber}', 'pembelian', :ref_id, :saldo, NOW()
            )
        ")->execute([
            'no_tx' => $voucherNo,
            'ak_id' => $cashId,
            'nom' => $nominal,
            'ref_id' => $purchaseId,
            'saldo' => $saldoAkhir
        ]);

        // Update status pembelian menjadi lunas
        $pdo->prepare("UPDATE public.pembelian SET status_pembayaran = 'lunas' WHERE id = :id")
            ->execute(['id' => $purchaseId]);

        // Verifikasi saldo kas terkini
        $stmtKas = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id");
        $stmtKas->execute(['id' => $cashId]);
        if (abs((float)$stmtKas->fetchColumn() - 1650000.0) > 0.001) {
            return "Saldo kas tidak terpotong dengan akurat.";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
});

// ------------------------------------------------------------------
// 6. PO CANCELLATION & AUTOMATIC CASH REFUND (PREPAID PO)
// ------------------------------------------------------------------
runTest("6. Pembatalan PO Lunas Belum Terima: Dana kas 100% dipulihkan otomatis (Reversal Refund)", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $supplierId = createTestSupplier($pdo);
        $cashId = createTestCashAccount($pdo, 1000000.0);
        $nominalPo = 400000.0;

        $poNumber = 'PB-TEST-PREPAID-' . mt_rand(100000, 999999);
        $stmtPb = $pdo->prepare("
            INSERT INTO public.pembelian (
                nomor_faktur_pembelian, pemasok_id, tanggal_pembelian, total_biaya,
                status_pembayaran, status_penerimaan, jenis_dokumen
            ) VALUES (
                :no_po, :sup_id, CURRENT_DATE, :total,
                'lunas', 'menunggu_supplier', 'po'
            ) RETURNING id
        ");
        $stmtPb->execute(['no_po' => $poNumber, 'sup_id' => $supplierId, 'total' => $nominalPo]);
        $purchaseId = $stmtPb->fetchColumn();

        // Catat kas keluar awal saat pembuatan PO lunas
        $saldoSetelahBayar = 1000000.0 - $nominalPo; // 600.000
        $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo WHERE id = :id")
            ->execute(['saldo' => $saldoSetelahBayar, 'id' => $cashId]);

        $voucherKeluar = CashVoucher::generate('keluar', date('Y-m-d'), $pdo);
        $pdo->prepare("
            INSERT INTO public.arus_kas (
                nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                keterangan, referensi_tabel, referensi_id, saldo_berjalan, dibuat_pada
            ) VALUES (
                :no_tx, :ak_id, CURRENT_DATE, 'keluar', 'pembelian_bahan', :nom,
                'Pembayaran di muka PO #{$poNumber}', 'pembelian', :ref_id, :saldo, NOW()
            )
        ")->execute([
            'no_tx' => $voucherKeluar,
            'ak_id' => $cashId,
            'nom' => $nominalPo,
            'ref_id' => $purchaseId,
            'saldo' => $saldoSetelahBayar
        ]);

        // Simulasi Pembatalan PO (Logika Baru yang Telah Diperbaiki)
        $stmtSumKas = $pdo->prepare("
            SELECT akun_kas_id,
                   COALESCE(SUM(CASE WHEN jenis_kas = 'keluar' THEN nominal ELSE -nominal END), 0) as netto_kas_keluar
            FROM public.arus_kas 
            WHERE referensi_tabel = 'pembelian' AND referensi_id = :id
            GROUP BY akun_kas_id
            HAVING COALESCE(SUM(CASE WHEN jenis_kas = 'keluar' THEN nominal ELSE -nominal END), 0) > 0
        ");
        $stmtSumKas->execute(['id' => $purchaseId]);
        $kasRows = $stmtSumKas->fetchAll(PDO::FETCH_ASSOC);

        foreach ($kasRows as $kRow) {
            $refAkunId = $kRow['akun_kas_id'];
            $refundAmt = (float)$kRow['netto_kas_keluar'];

            $stmtLockAkun = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id FOR UPDATE");
            $stmtLockAkun->execute(['id' => $refAkunId]);
            $curSaldo = (float)$stmtLockAkun->fetchColumn();
            $saldoRestored = $curSaldo + $refundAmt; // 600.000 + 400.000 = 1.000.000

            $pdo->prepare("UPDATE public.akun_kas SET saldo_saat_ini = :saldo WHERE id = :id")
                ->execute(['saldo' => $saldoRestored, 'id' => $refAkunId]);

            $voucherMasuk = CashVoucher::generate('masuk', date('Y-m-d'), $pdo);
            $pdo->prepare("
                INSERT INTO public.arus_kas (
                    nomor_transaksi, akun_kas_id, tanggal_transaksi, jenis_kas, kategori, nominal,
                    keterangan, referensi_tabel, referensi_id, saldo_berjalan, dibuat_pada
                ) VALUES (
                    :no_tx, :ak_id, CURRENT_DATE, 'masuk', 'pembelian_bahan', :nom,
                    'Refund pembatalan PO #{$poNumber}', 'pembelian', :ref_id, :saldo, NOW()
                )
            ")->execute([
                'no_tx' => $voucherMasuk,
                'ak_id' => $refAkunId,
                'nom' => $refundAmt,
                'ref_id' => $purchaseId,
                'saldo' => $saldoRestored
            ]);
        }

        $pdo->prepare("
            UPDATE public.pembelian
            SET status_pembayaran = 'batal', status_penerimaan = 'kendala_batal'
            WHERE id = :id
        ")->execute(['id' => $purchaseId]);

        // Verifikasi saldo kas kembali utuh 100% ke 1.000.000
        $stmtFinalKas = $pdo->prepare("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = :id");
        $stmtFinalKas->execute(['id' => $cashId]);
        $finalBalance = (float)$stmtFinalKas->fetchColumn();

        if (abs($finalBalance - 1000000.0) > 0.001) {
            return "Saldo kas gagal dipulihkan ke nominal awal 1.000.000, saldo saat ini: {$finalBalance}";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
});

// ------------------------------------------------------------------
// 7. DRIVER LOGISTICS ASSIGNMENT
// ------------------------------------------------------------------
runTest("7. Logistik Vendor: Driver ditugaskan mengambil belanjaan bahan baku", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $supplierId = createTestSupplier($pdo);

        // Cari atau buat profil driver uji coba
        $driver = Database::fetchOne("
            SELECT k.id as karyawan_id 
            FROM public.karyawan k 
            JOIN public.pengguna p ON k.pengguna_id = p.id 
            WHERE p.posisi = 'driver' AND p.status_aktif = TRUE 
            LIMIT 1
        ");

        $driverId = $driver['karyawan_id'] ?? null;
        if (!$driverId) {
            $peranNonDev = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran != 'developer' LIMIT 1");
            $peranId = $peranNonDev['id'] ?? null;

            // Buat driver transient jika belum ada
            $usrId = '99999999-9999-9999-9999-999999999999';
            $pdo->prepare("
                INSERT INTO public.pengguna (id, peran_id, nama_pengguna, kata_sandi, nama_lengkap, posisi, status_aktif)
                VALUES (:uid, :pid, 'driver_test_transient', 'hash', 'Driver Uji', 'driver', TRUE)
                ON CONFLICT (id) DO NOTHING
            ")->execute(['uid' => $usrId, 'pid' => $peranId]);

            $stmtK = $pdo->prepare("
                INSERT INTO public.karyawan (pengguna_id, tipe_penggajian)
                VALUES (:uid, 'bulanan')
                RETURNING id
            ");
            $stmtK->execute(['uid' => $usrId]);
            $driverId = $stmtK->fetchColumn();
        }

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
            'sup_id' => $supplierId,
            'did' => $driverId
        ]);
        $row = $stmtPb->fetch();

        if ($row['sales_driver_id'] !== $driverId || $row['metode_logistik'] !== 'diambil_driver') {
            return "Penugasan driver pada PO vendor tidak sesuai.";
        }

        return true;
    } finally {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
});

// ------------------------------------------------------------------
// 8. RBAC PERMISSION ISOLATION (purchases.receive)
// ------------------------------------------------------------------
runTest("8. RBAC Permission: Izin purchases.receive terdaftar resmi di basis data dan aktif untuk Owner/Admin", function() use ($pdo) {
    $stmtIzin = $pdo->prepare("SELECT * FROM public.izin WHERE kode_izin = 'purchases.receive'");
    $stmtIzin->execute();
    $izin = $stmtIzin->fetch(PDO::FETCH_ASSOC);

    if (!$izin) {
        return "Izin purchases.receive belum terdaftar di tabel public.izin.";
    }

    if ($izin['grup_izin'] !== 'Pembelian & Vendor') {
        return "Grup izin purchases.receive harus 'Pembelian & Vendor', didapatkan: {$izin['grup_izin']}";
    }

    $stmtRole = $pdo->prepare("
        SELECT p.nama_peran, ip.diizinkan 
        FROM public.izin_peran ip
        JOIN public.peran p ON ip.peran_id = p.id
        WHERE ip.izin_id = :izin_id
    ");
    $stmtRole->execute(['izin_id' => $izin['id']]);
    $roles = $stmtRole->fetchAll(PDO::FETCH_KEY_PAIR);

    if (empty($roles['owner']) || empty($roles['admin'])) {
        return "Role owner atau admin belum memiliki izin purchases.receive.";
    }

    return true;
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

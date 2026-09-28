<?php
declare(strict_types=1);

/**
 * tests/ConsignmentBillingAndAdjustmentTest.php
 * Automated Test Suite for Consignment Billing Gateway, Payment Ledger,
 * Credit Adjustments (Potongan Retur Susulan), and Multi-Invoice Settlement.
 *
 * Kepatuhan Mutlak AGENTS.md:
 * 1. Transaksi terisolasi dengan auto-rollback di blok finally.
 * 2. Zero Persistent Mock Data (tidak meninggalkan sampah di database riil).
 * 3. Single Source of Truth terdaftar di TestRunnerService.
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
require_once APP_ROOT . '/app/Controllers/ConsignmentController.php';
require_once APP_ROOT . '/app/Helpers/Format.php';

use App\Controllers\ConsignmentController;

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
echo " CONSIGNMENT BILLING & ADJUSTMENT AUTOMATED TEST SUITE\n";
echo "============================================================\n";

$pdo = Database::getConnection();

$pdo->beginTransaction();
try {
    // 1. Setup Isolated Test Fixtures
    $gpStmt = $pdo->prepare("INSERT INTO public.grup_produk (kode_grup, nama_grup) VALUES ('GRP-TEST-BILL', 'Grup Test Billing') RETURNING id");
    $gpStmt->execute();
    $gpId = $gpStmt->fetchColumn();

    $itStmt = $pdo->prepare("INSERT INTO public.item (grup_id, kode_sku, nama_item, tipe_item, satuan_dasar, harga_pokok_pembelian, status_jual, status_aktif) VALUES (:gp_id, 'SKU-TEST-BILL-1', 'Item Test Bill 1', 'barang_jadi', 'pcs', 8000, TRUE, TRUE) RETURNING id");
    $itStmt->execute(['gp_id' => $gpId]);
    $itemId = $itStmt->fetchColumn();

    $grpelStmt = $pdo->prepare("INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, default_level_harga) VALUES ('GP-BILL-TEST', 'Grup Pelanggan Bill Test', 1) RETURNING id");
    $grpelStmt->execute();
    $grpelId = $grpelStmt->fetchColumn();

    $custStmt = $pdo->prepare("
        INSERT INTO public.pelanggan (
            grup_pelanggan_id, nama_toko, kode_pelanggan, nama_pemilik, nomor_whatsapp, alamat_lengkap,
            is_konsinyasi, tipe_konsinyasi, total_piutang_berjalan, status_aktif
        ) VALUES (
            :gpid, 'Toko Mitra Test Bill Gateway', 'TOKO-BILL-TEST', 'Budi Mitra', '081234567890', 'Jl. Mitra Konsin No. 123',
            TRUE, 'kolektif_tagihan', 0.00, TRUE
        ) RETURNING id
    ");
    $custStmt->execute(['gpid' => $grpelId]);
    $customerId = $custStmt->fetchColumn();

    $kasStmt = $pdo->prepare("
        INSERT INTO public.akun_kas (
            nama_akun, tipe_akun, nomor_rekening, saldo_saat_ini, status_aktif, is_default_pos
        ) VALUES (
            'Kas Bank Test Pelunasan', 'kas_bank', '9988776655', 5000000.00, TRUE, FALSE
        ) RETURNING id
    ");
    $kasStmt->execute();
    $kasId = $kasStmt->fetchColumn();

    $adminId = $pdo->query("SELECT id FROM public.pengguna WHERE status_aktif = TRUE LIMIT 1")->fetchColumn();

    // -------------------------------------------------------------------------
    // TEST 1: Kunjungan Opname Backlog & fn_buat_tagihan_konsinyasi
    // -------------------------------------------------------------------------
    runTest('Test 1: Buat Tagihan Batch dari Kunjungan Opname (Unbilled Backlog)', function() use ($pdo, $customerId, $adminId, $itemId) {
        $visitStmt = $pdo->prepare("
            INSERT INTO public.kunjungan_konsinyasi (
                nomor_kunjungan, pelanggan_id, tanggal_kunjungan, total_laku_nominal, catatan, dibuat_oleh, dibuat_pada
            ) VALUES (
                'KONSIN-TEST-001', :pid, CURRENT_DATE - INTERVAL '10 days', 500000.00, 'Kunjungan 10 hari lalu', :uid, NOW() - INTERVAL '10 days'
            ) RETURNING id
        ");
        $visitStmt->execute(['pid' => $customerId, 'uid' => $adminId]);
        $visitId = $visitStmt->fetchColumn();

        $rincStmt = $pdo->prepare("
            INSERT INTO public.rincian_kunjungan_konsinyasi (
                kunjungan_id, item_id, stok_titip_awal, tambah_titip_baru, sisa_fisik_di_rak,
                jumlah_laku_terjual, harga_satuan_deal, subtotal_laku, retur_rusak, retur_bagus, selisih_qty
            ) VALUES (
                :kid, :item_id, 100, 0, 50, 50, 10000.00, 500000.00, 0, 0, 0
            )
        ");
        $rincStmt->execute(['kid' => $visitId, 'item_id' => $itemId]);

        // Verifikasi unbilled visit backlog terdeteksi
        $unbilled = $pdo->query("
            SELECT kk.id, kk.total_laku_nominal, (CURRENT_DATE - kk.tanggal_kunjungan) as days_pending
            FROM public.kunjungan_konsinyasi kk
            WHERE kk.id = '{$visitId}'
              AND NOT EXISTS (SELECT 1 FROM public.tagihan_kunjungan tk WHERE tk.kunjungan_id = kk.id)
        ")->fetch(PDO::FETCH_ASSOC);

        if (!$unbilled || (float)$unbilled['total_laku_nominal'] != 500000.00) {
            return "Kunjungan unbilled tidak terdeteksi di database";
        }
        if ((int)$unbilled['days_pending'] < 7) {
            return "Days pending backlog tidak sesuai (>7 hari)";
        }

        // Generate tagihan via DB procedure
        $genRes = $pdo->query("SELECT public.fn_buat_tagihan_konsinyasi('{{$visitId}}'::uuid[], '{$adminId}'::uuid) as res")->fetchColumn();
        $genJson = json_decode($genRes, true);

        if (empty($genJson['success'])) {
            return "fn_buat_tagihan_konsinyasi gagal dieksekusi";
        }

        $pesananId = $genJson['pesanan_id'];
        $order = $pdo->query("SELECT * FROM public.pesanan WHERE id = '{$pesananId}'")->fetch(PDO::FETCH_ASSOC);

        if (!$order || (float)$order['total_netto'] != 500000.00 || (float)$order['sisa_tagihan'] != 500000.00 || $order['status_pembayaran'] !== 'belum_lunas') {
            return "Faktur pesanan yang digenerate tidak valid nilainya";
        }

        $cust = $pdo->query("SELECT total_piutang_berjalan FROM public.pelanggan WHERE id = '{$customerId}'")->fetch(PDO::FETCH_ASSOC);
        if ((float)$cust['total_piutang_berjalan'] != 500000.00) {
            return "Piutang berjalan pelanggan tidak bertambah menjadi Rp 500.000";
        }

        return true;
    });

    // -------------------------------------------------------------------------
    // TEST 2: Pembayaran Bertahap / Cicilan & Payment Ledger Integrity
    // -------------------------------------------------------------------------
    runTest('Test 2: Cicilan Pembayaran & Jejak Audit Transaksi Kas Ledger', function() use ($pdo, $customerId, $adminId, $kasId) {
        $pesStmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_diskon, total_netto,
                total_dibayar, sisa_tagihan, tipe_pembayaran, status_pembayaran, status_pemrosesan,
                is_tagihan, dibuat_oleh, dibuat_pada
            ) VALUES (
                'INV-TEST-CICIL-01', :pid, CURRENT_DATE, 1000000.00, 0.00, 1000000.00,
                0.00, 1000000.00, 'konsinyasi', 'belum_lunas', 'selesai',
                TRUE, :uid, NOW()
            ) RETURNING id
        ");
        $pesStmt->execute(['pid' => $customerId, 'uid' => $adminId]);
        $pesananId = $pesStmt->fetchColumn();

        // Tambah piutang pelanggan
        $pdo->exec("UPDATE public.pelanggan SET total_piutang_berjalan = total_piutang_berjalan + 1000000.00 WHERE id = '{$customerId}'");

        // Cicilan 1: Rp 400.000
        $pay1Res = $pdo->query("SELECT public.fn_catat_pembayaran_konsinyasi('{$pesananId}', '{$kasId}', 400000.00, '{$adminId}', 'Cicilan 1 Transfer', CURRENT_DATE, 0.00, NULL) as res")->fetchColumn();
        $pay1Json = json_decode($pay1Res, true);

        if (empty($pay1Json['success']) || $pay1Json['status_pembayaran'] !== 'sebagian' || (float)$pay1Json['sisa_tagihan'] != 600000.00) {
            return "Cicilan 1 gagal diproses dengan status sebagian";
        }

        // Cicilan 2: Rp 300.000
        $pay2Res = $pdo->query("SELECT public.fn_catat_pembayaran_konsinyasi('{$pesananId}', '{$kasId}', 300000.00, '{$adminId}', 'Cicilan 2 Kasir', CURRENT_DATE, 0.00, NULL) as res")->fetchColumn();
        $pay2Json = json_decode($pay2Res, true);

        if (empty($pay2Json['success']) || $pay2Json['status_pembayaran'] !== 'sebagian' || (float)$pay2Json['sisa_tagihan'] != 300000.00) {
            return "Cicilan 2 gagal diproses dengan sisa Rp 300.000";
        }

        // Verifikasi Riwayat Kas Ledger
        $ledger = $pdo->query("
            SELECT COUNT(*) as cnt, SUM(nominal) as total_masuk
            FROM public.arus_kas
            WHERE referensi_tabel = 'pesanan' AND referensi_id = '{$pesananId}'
        ")->fetch(PDO::FETCH_ASSOC);

        if ((int)$ledger['cnt'] !== 2 || (float)$ledger['total_masuk'] != 700000.00) {
            return "Riwayat transaksi kas ledger tidak mencatat 2 cicilan dengan total Rp 700.000";
        }

        return true;
    });

    // -------------------------------------------------------------------------
    // TEST 3: Potongan Retur Susulan / Adjustment Resmi (Zero Fictitious Debt)
    // -------------------------------------------------------------------------
    runTest('Test 3: Potongan Retur Susulan / Adjustment & Penutupan Status Lunas', function() use ($pdo, $customerId, $adminId, $kasId) {
        $pesStmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_diskon, total_netto,
                total_dibayar, sisa_tagihan, tipe_pembayaran, status_pembayaran, status_pemrosesan,
                is_tagihan, dibuat_oleh, dibuat_pada
            ) VALUES (
                'INV-TEST-ADJUST-01', :pid, CURRENT_DATE, 1500000.00, 0.00, 1500000.00,
                0.00, 1500000.00, 'konsinyasi', 'belum_lunas', 'selesai',
                TRUE, :uid, NOW()
            ) RETURNING id
        ");
        $pesStmt->execute(['pid' => $customerId, 'uid' => $adminId]);
        $pesananId = $pesStmt->fetchColumn();

        $pdo->exec("UPDATE public.pelanggan SET total_piutang_berjalan = total_piutang_berjalan + 1500000.00 WHERE id = '{$customerId}'");

        // Kas Masuk Rp 1.400.000 + Potongan Retur Susulan Rp 100.000 = Total Rp 1.500.000 (Lunas Sempurna)
        $adjRes = $pdo->query("
            SELECT public.fn_catat_pembayaran_konsinyasi(
                '{$pesananId}', '{$kasId}', 1400000.00, '{$adminId}',
                'Pelunasan faktur potong retur', CURRENT_DATE,
                100000.00, 'Retur susulan 10 bungkus sobek'
            ) as res
        ")->fetchColumn();
        $adjJson = json_decode($adjRes, true);

        if (empty($adjJson['success'])) {
            return "fn_catat_pembayaran_konsinyasi dengan potongan gagal";
        }

        if ($adjJson['status_pembayaran'] !== 'lunas' || (float)$adjJson['sisa_tagihan'] != 0.00) {
            return "Faktur tidak berstatus LUNAS atau sisa tagihan bukan 0";
        }

        $order = $pdo->query("SELECT * FROM public.pesanan WHERE id = '{$pesananId}'")->fetch(PDO::FETCH_ASSOC);
        if ((float)$order['total_dibayar'] != 1400000.00 || (float)$order['total_diskon'] != 100000.00 || (float)$order['sisa_tagihan'] != 0.00) {
            return "Nilai diskon/dibayar pada pesanan tidak tersinkronisasi";
        }

        // Verifikasi bahwa uang kas hanya bertambah Rp 1.400.000 (bukan 1.5jt)
        $lastKas = $pdo->query("
            SELECT nominal, keterangan 
            FROM public.arus_kas 
            WHERE referensi_tabel = 'pesanan' AND referensi_id = '{$pesananId}'
            ORDER BY dibuat_pada DESC LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        if ((float)$lastKas['nominal'] != 1400000.00 || !str_contains($lastKas['keterangan'], 'Retur susulan 10 bungkus sobek')) {
            return "Nominal kas masuk transaksi kas atau keterangan potongan tidak tercatat benar";
        }

        return true;
    });

    // -------------------------------------------------------------------------
    // TEST 4: Pelunasan Massal Multi-Faktur 1 Transfer (FIFO Batch Settlement)
    // -------------------------------------------------------------------------
    runTest('Test 4: Pelunasan Massal Multi-Faktur Sekaligus via Satu Setoran Bank', function() use ($pdo, $customerId, $adminId, $kasId) {
        $inv1Stmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_diskon, total_netto,
                total_dibayar, sisa_tagihan, tipe_pembayaran, status_pembayaran, status_pemrosesan,
                is_tagihan, dibuat_oleh, dibuat_pada
            ) VALUES (
                'INV-BATCH-01', :pid, CURRENT_DATE - INTERVAL '3 days', 300000.00, 0.00, 300000.00,
                0.00, 300000.00, 'konsinyasi', 'belum_lunas', 'selesai',
                TRUE, :uid, NOW()
            ) RETURNING id
        ");
        $inv1Stmt->execute(['pid' => $customerId, 'uid' => $adminId]);
        $inv1Id = $inv1Stmt->fetchColumn();

        $inv2Stmt = $pdo->prepare("
            INSERT INTO public.pesanan (
                nomor_nota, pelanggan_id, tanggal_pesanan, total_bruto, total_diskon, total_netto,
                total_dibayar, sisa_tagihan, tipe_pembayaran, status_pembayaran, status_pemrosesan,
                is_tagihan, dibuat_oleh, dibuat_pada
            ) VALUES (
                'INV-BATCH-02', :pid, CURRENT_DATE - INTERVAL '1 days', 700000.00, 0.00, 700000.00,
                0.00, 700000.00, 'konsinyasi', 'belum_lunas', 'selesai',
                TRUE, :uid, NOW()
            ) RETURNING id
        ");
        $inv2Stmt->execute(['pid' => $customerId, 'uid' => $adminId]);
        $inv2Id = $inv2Stmt->fetchColumn();

        $pdo->exec("UPDATE public.pelanggan SET total_piutang_berjalan = total_piutang_berjalan + 1000000.00 WHERE id = '{$customerId}'");

        // Simulasi transfer lump-sum Rp 800.000 untuk melunasi INV-BATCH-01 (Rp 300.000 Lunas) & INV-BATCH-02 (Rp 500.000 Sisa 200rb)
        $totalTransfer = 800000.00;
        $orderIds = [$inv1Id, $inv2Id];

        $stmt = $pdo->prepare("
            SELECT id, nomor_nota, sisa_tagihan
            FROM public.pesanan
            WHERE id IN ('{$inv1Id}', '{$inv2Id}')
            ORDER BY tanggal_pesanan ASC
        ");
        $stmt->execute();
        $ordersToSettle = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sisaAlokasi = $totalTransfer;
        foreach ($ordersToSettle as $ord) {
            if ($sisaAlokasi <= 0) break;
            $alokasi = min($sisaAlokasi, (float)$ord['sisa_tagihan']);
            $res = $pdo->query("
                SELECT public.fn_catat_pembayaran_konsinyasi(
                    '{$ord['id']}', '{$kasId}', {$alokasi}, '{$adminId}',
                    'Alokasi Transfer Multi Faktur', CURRENT_DATE, 0.00, NULL
                ) as res
            ")->fetchColumn();
            $sisaAlokasi -= $alokasi;
        }

        $inv1 = $pdo->query("SELECT * FROM public.pesanan WHERE id = '{$inv1Id}'")->fetch(PDO::FETCH_ASSOC);
        $inv2 = $pdo->query("SELECT * FROM public.pesanan WHERE id = '{$inv2Id}'")->fetch(PDO::FETCH_ASSOC);

        if ($inv1['status_pembayaran'] !== 'lunas' || (float)$inv1['sisa_tagihan'] != 0.00) {
            return "Faktur INV-BATCH-01 tidak terlunasi secara FIFO";
        }
        if ($inv2['status_pembayaran'] !== 'sebagian' || (float)$inv2['sisa_tagihan'] != 200000.00) {
            return "Faktur INV-BATCH-02 tidak mencatat alokasi sisa Rp 200.000";
        }

        return true;
    });

} finally {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
        echo "\n[INFO] Transaksi database test berhasil di-rollback sepenuhnya (100% Zero Contamination).\n";
    }
}

echo "\n============================================================\n";
echo " TEST RUN RESULTS: {$passed} PASSED, {$failed} FAILED OF {$totalTests} TESTS\n";
echo "============================================================\n";

if ($failed > 0) {
    exit(1);
}

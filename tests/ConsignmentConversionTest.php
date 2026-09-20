<?php
declare(strict_types=1);

/**
 * tests/audit_consignment_conversion_extreme.php
 * Automated Extreme Test Suite for Interactive Consignment Stock Conversion:
 * 1. Opsi 1 (Retur Fisik): Saldo rak 0, stok fisik gudang bertambah, riwayat_stok tercatat 'konsinyasi_retur_masuk'.
 * 2. Opsi 2A (Beli Putus Lunas): Saldo rak 0, faktur pesanan terbit lunas, akun kas bertambah, arus kas tercatat.
 * 3. Opsi 2B (Beli Putus Tempo): Saldo rak 0, faktur pesanan terbit tempo, total_piutang_berjalan toko bertambah.
 * 4. Proteksi Konversi: Tolak konversi non-konsinyasi jika ada stok rak aktif dan opsi resolusi tidak dipilih.
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

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
    if (file_exists($file)) require_once $file;
});

function runTest(string $title, callable $fn): void {
    echo "\n------------------------------------------------------------\n";
    echo "[TEST] {$title}...\n";
    try {
        $res = $fn();
        if ($res === true) {
            echo " \033[32m[PASS]\033[0m {$title}\n";
        } else {
            echo " \033[31m[FAIL]\033[0m {$title}: " . (is_string($res) ? $res : 'Test failed') . "\n";
            exit(1);
        }
    } catch (Throwable $e) {
        echo " \033[31m[ERROR]\033[0m {$title}: " . $e->getMessage() . "\n";
        echo $e->getTraceAsString() . "\n";
        exit(1);
    }
}

function createMockCustomerController(array $postData): App\Controllers\CustomerController {
    return new class($postData) extends App\Controllers\CustomerController {
        public ?string $capturedError = null;
        public ?string $capturedSuccess = null;
        public ?string $redirectUrl = null;
        private array $postData;

        public function __construct(array $postData) {
            $this->postData = $postData;
        }

        protected function input(?string $key = null, mixed $default = null): mixed {
            if ($key === null) return $this->postData;
            return $this->postData[$key] ?? $default;
        }

        protected function flashError(string $message, ?string $title = null): void {
            $this->capturedError = $message;
            $_SESSION['flash_error'] = $message;
        }

        protected function flashSuccess(string $message, ?string $title = null): void {
            $this->capturedSuccess = $message;
            $_SESSION['flash_success'] = $message;
        }

        protected function redirect(string $url): void {
            $this->redirectUrl = $url;
        }
    };
}

$db = Database::getConnection();

$transientIds = ['item' => [], 'pelanggan' => [], 'pesanan' => [], 'grup_produk' => []];

register_shutdown_function(function() use ($db, &$transientIds) {
    if (!empty($transientIds['pesanan'])) {
        $in = "'" . implode("','", $transientIds['pesanan']) . "'";
        $db->exec("DELETE FROM public.riwayat_stok WHERE referensi_tabel = 'pesanan' AND referensi_id IN ($in)");
        $db->exec("DELETE FROM public.arus_kas WHERE referensi_tabel = 'pesanan' AND referensi_id IN ($in)");
        $db->exec("DELETE FROM public.item_pesanan WHERE pesanan_id IN ($in)");
        $db->exec("DELETE FROM public.pesanan WHERE id IN ($in)");
    }
    if (!empty($transientIds['pelanggan'])) {
        $in = "'" . implode("','", $transientIds['pelanggan']) . "'";
        $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id IN ($in)");
        $db->exec("DELETE FROM public.pelanggan WHERE id IN ($in)");
    }
    if (!empty($transientIds['item'])) {
        $in = "'" . implode("','", $transientIds['item']) . "'";
        $db->exec("DELETE FROM public.item WHERE id IN ($in)");
    }
    if (!empty($transientIds['grup_produk'])) {
        $in = "'" . implode("','", $transientIds['grup_produk']) . "'";
        $db->exec("DELETE FROM public.grup_produk_harga_level WHERE grup_produk_id IN ($in)");
        $db->exec("DELETE FROM public.item WHERE grup_id IN ($in)");
        $db->exec("DELETE FROM public.grup_produk WHERE id IN ($in)");
    }
});

$grupId = $db->query("SELECT id FROM public.grup_pelanggan WHERE default_level_harga = 1 LIMIT 1")->fetchColumn()
    ?: $db->query("SELECT id FROM public.grup_pelanggan LIMIT 1")->fetchColumn();
$wilId = $db->query("SELECT id FROM public.wilayah LIMIT 1")->fetchColumn();
$item = $db->query("SELECT id, nama_item, kode_sku, stok_fisik_saat_ini, harga_pokok_pembelian FROM public.item WHERE tipe_item = 'barang_jadi' AND status_aktif = TRUE LIMIT 1")->fetch(PDO::FETCH_ASSOC);
$akunKas = $db->query("SELECT id, nama_akun, saldo_saat_ini FROM public.akun_kas WHERE status_aktif = TRUE LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    $tmpItemId = 'e0000000-0000-0000-0000-000000000010';
    $tmpGrupId = 'a0000000-0000-0000-0000-000000000010';
    $db->exec("
        INSERT INTO public.grup_produk (id, kode_grup, nama_grup, status_aktif)
        VALUES ('{$tmpGrupId}', 'GRP-CONV-TMP', 'Grup Konversi Transien', TRUE)
        ON CONFLICT (id) DO NOTHING
    ");
    $db->exec("
        INSERT INTO public.item (id, kode_sku, nama_item, tipe_item, grup_id, satuan_dasar, stok_fisik_saat_ini, harga_pokok_pembelian, status_aktif)
        VALUES ('{$tmpItemId}', 'SKU-CONV-TMP', 'Item Konversi Transien', 'barang_jadi', '{$tmpGrupId}', 'pcs', 100, 5000, TRUE)
        ON CONFLICT (id) DO NOTHING
    ");
    $db->exec("
        INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, harga_jual_pcs)
        VALUES ('{$tmpGrupId}', 1, 10000.00)
        ON CONFLICT (grup_produk_id, level_harga) DO NOTHING
    ");
    $transientIds['grup_produk'][] = $tmpGrupId;
    $transientIds['item'][] = $tmpItemId;
    $item = [
        'id' => $tmpItemId,
        'nama_item' => 'Item Konversi Transien',
        'kode_sku' => 'SKU-CONV-TMP',
        'stok_fisik_saat_ini' => 100,
        'harga_pokok_pembelian' => 5000
    ];
}

if (!$akunKas) {
    echo "Akun kas tidak tersedia untuk pengujian.\n";
    exit(1);
}

// TEST 1
runTest('Block conversion if active consignment stock exists and no resolution option selected', function() use ($db, $grupId, $wilId, $item) {
    $custId = 'c0000000-0000-0000-0000-000000000010';
    $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}'");
    $db->exec("DELETE FROM public.pelanggan WHERE id = '{$custId}'");

    $db->exec("
        INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, grup_pelanggan_id, wilayah_id, is_konsinyasi, tipe_pembayaran_default, status_aktif, alamat_lengkap)
        VALUES ('{$custId}', 'CUST-TEST-10', 'Toko Titip Test Guard', '{$grupId}', '{$wilId}', TRUE, 'konsinyasi', TRUE, 'Jl. Test No. 10')
    ");

    $db->exec("
        INSERT INTO public.stok_konsinyasi_toko (id, pelanggan_id, item_id, stok_titip_saat_ini)
        VALUES (gen_random_uuid(), '{$custId}', '{$item['id']}', 20)
    ");

    $ctrl = createMockCustomerController([
        'id' => $custId,
        'nama_toko' => 'Toko Titip Test Guard',
        'grup_pelanggan_id' => $grupId,
        'wilayah_id' => $wilId,
        'tipe_pembayaran_default' => 'cash',
        'is_konsinyasi' => false,
        'konversi_konsinyasi_opsi' => ''
    ]);
    $ctrl->update();

    $checkCust = $db->query("SELECT is_konsinyasi FROM public.pelanggan WHERE id = '{$custId}'")->fetch(PDO::FETCH_ASSOC);
    
    $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}'");
    $db->exec("DELETE FROM public.pelanggan WHERE id = '{$custId}'");

    if (!$checkCust['is_konsinyasi']) {
        return "Toko berhasil diubah ke non-konsinyasi tanpa memilih opsi penyelesaian!";
    }
    if (empty($ctrl->capturedError) || !str_contains($ctrl->capturedError, 'stok konsinyasi')) {
        return "Pesan error tidak sesuai: " . var_export($ctrl->capturedError, true);
    }
    return true;
});

// TEST 2
runTest('Opsi 1 (Retur Fisik): Returns stock to warehouse, zeroes shelf stock, and writes riwayat_stok', function() use ($db, $grupId, $wilId, $item) {
    $custId = 'c0000000-0000-0000-0000-000000000020';
    $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}'");
    $db->exec("DELETE FROM public.pelanggan WHERE id = '{$custId}'");

    $stokGudangAwal = (float)$db->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$item['id']}'")->fetchColumn();
    $qtyRetur = 25;

    $db->exec("
        INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, grup_pelanggan_id, wilayah_id, is_konsinyasi, tipe_pembayaran_default, status_aktif, alamat_lengkap)
        VALUES ('{$custId}', 'CUST-TEST-20', 'Toko Retur Test', '{$grupId}', '{$wilId}', TRUE, 'konsinyasi', TRUE, 'Jl. Test No. 20')
    ");

    $db->exec("
        INSERT INTO public.stok_konsinyasi_toko (id, pelanggan_id, item_id, stok_titip_saat_ini)
        VALUES (gen_random_uuid(), '{$custId}', '{$item['id']}', {$qtyRetur})
    ");

    $ctrl = createMockCustomerController([
        'id' => $custId,
        'nama_toko' => 'Toko Retur Test',
        'grup_pelanggan_id' => $grupId,
        'wilayah_id' => $wilId,
        'tipe_pembayaran_default' => 'cash',
        'is_konsinyasi' => false,
        'konversi_konsinyasi_opsi' => 'retur',
        'catatan_konversi' => 'BAST-TEST-001'
    ]);
    $ctrl->update();

    $checkCust = $db->query("SELECT is_konsinyasi, tipe_pembayaran_default FROM public.pelanggan WHERE id = '{$custId}'")->fetch(PDO::FETCH_ASSOC);
    $checkRak = (int)$db->query("SELECT stok_titip_saat_ini FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}' AND item_id = '{$item['id']}'")->fetchColumn();
    $stokGudangAkhir = (float)$db->query("SELECT stok_fisik_saat_ini FROM public.item WHERE id = '{$item['id']}'")->fetchColumn();
    $checkMutasi = $db->query("SELECT * FROM public.riwayat_stok WHERE referensi_tabel = 'pelanggan' AND referensi_id = '{$custId}' ORDER BY dibuat_pada DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $db->exec("UPDATE public.item SET stok_fisik_saat_ini = {$stokGudangAwal} WHERE id = '{$item['id']}'");
    $db->exec("DELETE FROM public.riwayat_stok WHERE referensi_tabel = 'pelanggan' AND referensi_id = '{$custId}'");
    $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}'");
    $db->exec("DELETE FROM public.pelanggan WHERE id = '{$custId}'");

    if ($checkCust['is_konsinyasi'] === true) return "is_konsinyasi masih true setelah retur!";
    if ($checkRak !== 0) return "Stok rak tidak nol, masih: {$checkRak}";
    if ($stokGudangAkhir != ($stokGudangAwal + $qtyRetur)) return "Stok gudang tidak bertambah!";
    if (!$checkMutasi || $checkMutasi['tipe_mutasi'] !== 'konsinyasi_retur_masuk' || (int)$checkMutasi['jumlah_perubahan'] !== $qtyRetur) {
        return "Riwayat stok konsinyasi_retur_masuk tidak valid!";
    }

    return true;
});

// TEST 3
runTest('Opsi 2A (Beli Putus Lunas): Zeroes shelf stock, creates paid pesanan, increases cash balance, records arus_kas', function() use ($db, $grupId, $wilId, $item, $akunKas) {
    $custId = 'c0000000-0000-0000-0000-000000000030';
    $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}'");
    $db->exec("DELETE FROM public.pelanggan WHERE id = '{$custId}'");

    $qtyBeli = 10;
    $saldoKasAwal = (float)$db->query("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = '{$akunKas['id']}'")->fetchColumn();

    $db->exec("
        INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, grup_pelanggan_id, wilayah_id, is_konsinyasi, tipe_pembayaran_default, status_aktif, alamat_lengkap)
        VALUES ('{$custId}', 'CUST-TEST-30', 'Toko Beli Lunas Test', '{$grupId}', '{$wilId}', TRUE, 'konsinyasi', TRUE, 'Jl. Test No. 30')
    ");

    $db->exec("
        INSERT INTO public.stok_konsinyasi_toko (id, pelanggan_id, item_id, stok_titip_saat_ini)
        VALUES (gen_random_uuid(), '{$custId}', '{$item['id']}', {$qtyBeli})
    ");

    $hargaInfoRaw = $db->query("SELECT public.fn_hitung_harga_jual_item('{$item['id']}', '{$custId}') as info")->fetchColumn();
    $hargaData = json_decode((string)$hargaInfoRaw, true) ?: [];
    $hargaPcsNetto = (float)($hargaData['harga_pcs_netto'] ?? 0);
    $totalExpected = $hargaPcsNetto * $qtyBeli;

    $ctrl = createMockCustomerController([
        'id' => $custId,
        'nama_toko' => 'Toko Beli Lunas Test',
        'grup_pelanggan_id' => $grupId,
        'wilayah_id' => $wilId,
        'tipe_pembayaran_default' => 'cash',
        'is_konsinyasi' => false,
        'konversi_konsinyasi_opsi' => 'beli_putus',
        'metode_beli_putus' => 'lunas',
        'akun_kas_id' => $akunKas['id'],
        'catatan_konversi' => 'Test Beli Putus Lunas'
    ]);
    $ctrl->update();

    $checkCust = $db->query("SELECT is_konsinyasi, tipe_pembayaran_default FROM public.pelanggan WHERE id = '{$custId}'")->fetch(PDO::FETCH_ASSOC);
    $checkRak = (int)$db->query("SELECT stok_titip_saat_ini FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}' AND item_id = '{$item['id']}'")->fetchColumn();
    $saldoKasAkhir = (float)$db->query("SELECT saldo_saat_ini FROM public.akun_kas WHERE id = '{$akunKas['id']}'")->fetchColumn();
    
    $pesanan = $db->query("SELECT * FROM public.pesanan WHERE pelanggan_id = '{$custId}' ORDER BY dibuat_pada DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $itemPesanan = $pesanan ? $db->query("SELECT * FROM public.item_pesanan WHERE pesanan_id = '{$pesanan['id']}'")->fetch(PDO::FETCH_ASSOC) : null;
    $arusKas = $pesanan ? $db->query("SELECT * FROM public.arus_kas WHERE referensi_tabel = 'pesanan' AND referensi_id = '{$pesanan['id']}'")->fetch(PDO::FETCH_ASSOC) : null;

    if ($pesanan) {
        $db->exec("DELETE FROM public.arus_kas WHERE referensi_tabel = 'pesanan' AND referensi_id = '{$pesanan['id']}'");
        $db->exec("DELETE FROM public.riwayat_stok WHERE referensi_tabel = 'pesanan' AND referensi_id = '{$pesanan['id']}'");
        $db->exec("DELETE FROM public.item_pesanan WHERE pesanan_id = '{$pesanan['id']}'");
        $db->exec("DELETE FROM public.pesanan WHERE id = '{$pesanan['id']}'");
    }
    $db->exec("UPDATE public.akun_kas SET saldo_saat_ini = {$saldoKasAwal} WHERE id = '{$akunKas['id']}'");
    $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}'");
    $db->exec("DELETE FROM public.pelanggan WHERE id = '{$custId}'");

    if ($checkCust['is_konsinyasi'] === true) return "is_konsinyasi masih true!";
    if ($checkRak !== 0) return "Stok rak tidak nol!";
    if (!$pesanan) return "Faktur pesanan tidak terbentuk!";
    if ($pesanan['status_pembayaran'] !== 'lunas') return "Status pesanan bukan lunas!";
    if ((float)$pesanan['total_netto'] != $totalExpected) return "Total netto pesanan tidak sesuai: {$pesanan['total_netto']} vs {$totalExpected}";
    if (!$itemPesanan || (int)$itemPesanan['kuantitas_satuan_dasar'] !== $qtyBeli) return "Item pesanan tidak sesuai!";
    if ($saldoKasAkhir != ($saldoKasAwal + $totalExpected)) return "Saldo kas tidak bertambah dengan benar!";
    if (!$arusKas || (float)$arusKas['nominal'] != $totalExpected) return "Arus kas masuk tidak valid!";

    return true;
});

// TEST 4
runTest('Opsi 2B (Beli Putus Tempo): Zeroes shelf stock, creates pending pesanan, increases total_piutang_berjalan', function() use ($db, $grupId, $wilId, $item) {
    $custId = 'c0000000-0000-0000-0000-000000000040';
    $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}'");
    $db->exec("DELETE FROM public.pelanggan WHERE id = '{$custId}'");

    $qtyBeli = 12;

    $db->exec("
        INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, grup_pelanggan_id, wilayah_id, is_konsinyasi, tipe_pembayaran_default, total_piutang_berjalan, plafon_piutang, status_aktif, alamat_lengkap)
        VALUES ('{$custId}', 'CUST-TEST-40', 'Toko Beli Tempo Test', '{$grupId}', '{$wilId}', TRUE, 'konsinyasi', 100000, 5000000, TRUE, 'Jl. Test No. 40')
    ");

    $db->exec("
        INSERT INTO public.stok_konsinyasi_toko (id, pelanggan_id, item_id, stok_titip_saat_ini)
        VALUES (gen_random_uuid(), '{$custId}', '{$item['id']}', {$qtyBeli})
    ");

    $hargaInfoRaw = $db->query("SELECT public.fn_hitung_harga_jual_item('{$item['id']}', '{$custId}') as info")->fetchColumn();
    $hargaData = json_decode((string)$hargaInfoRaw, true) ?: [];
    $hargaPcsNetto = (float)($hargaData['harga_pcs_netto'] ?? 0);
    $totalExpected = $hargaPcsNetto * $qtyBeli;

    $ctrl = createMockCustomerController([
        'id' => $custId,
        'nama_toko' => 'Toko Beli Tempo Test',
        'grup_pelanggan_id' => $grupId,
        'wilayah_id' => $wilId,
        'tipe_pembayaran_default' => 'tempo_14_hari',
        'is_konsinyasi' => false,
        'konversi_konsinyasi_opsi' => 'beli_putus',
        'metode_beli_putus' => 'tempo',
        'catatan_konversi' => 'Test Beli Putus Tempo'
    ]);
    $ctrl->update();

    $checkCust = $db->query("SELECT is_konsinyasi, tipe_pembayaran_default, total_piutang_berjalan FROM public.pelanggan WHERE id = '{$custId}'")->fetch(PDO::FETCH_ASSOC);
    $checkRak = (int)$db->query("SELECT stok_titip_saat_ini FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}' AND item_id = '{$item['id']}'")->fetchColumn();
    
    $pesanan = $db->query("SELECT * FROM public.pesanan WHERE pelanggan_id = '{$custId}' ORDER BY dibuat_pada DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if ($pesanan) {
        $db->exec("DELETE FROM public.riwayat_stok WHERE referensi_tabel = 'pesanan' AND referensi_id = '{$pesanan['id']}'");
        $db->exec("DELETE FROM public.item_pesanan WHERE pesanan_id = '{$pesanan['id']}'");
        $db->exec("DELETE FROM public.pesanan WHERE id = '{$pesanan['id']}'");
    }
    $db->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = '{$custId}'");
    $db->exec("DELETE FROM public.pelanggan WHERE id = '{$custId}'");

    if ($checkCust['is_konsinyasi'] === true) return "is_konsinyasi masih true!";
    if ($checkRak !== 0) return "Stok rak tidak nol!";
    if (!$pesanan) return "Faktur pesanan tidak terbentuk!";
    if ($pesanan['status_pembayaran'] !== 'belum_lunas') return "Status pesanan tempo seharusnya belum_lunas!";
    if (empty($pesanan['tanggal_jatuh_tempo'])) return "Tanggal jatuh tempo tidak diisi!";
    
    $expectedPiutang = 100000 + $totalExpected;
    if ((float)$checkCust['total_piutang_berjalan'] != $expectedPiutang) {
        return "Piutang berjalan tidak bertambah sesuai tagihan: {$checkCust['total_piutang_berjalan']} vs {$expectedPiutang}";
    }

    return true;
});

echo "\n============================================================\n";
echo "CONSIGNMENT CONVERSION AUDIT RESULTS: ALL 4 TESTS PASSED (100%)\n";
echo "============================================================\n";

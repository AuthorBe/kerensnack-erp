<?php
declare(strict_types=1);

/**
 * tests/ImportDataLifecycleTest.php
 * Automated Extreme Test Suite: MASTER DATA IMPORT, DIFFING ENGINE & FULL-SYNC RECONCILIATION
 * 
 * Pengujian komprehensif untuk:
 * 1. SmartReader Engine: Pembersihan string, alias matching, parsing angka Rupiah/desimal, boolean, dan fuzzy Levenshtein
 * 2. Handler Registry & Interface Contract: Kelengkapan 10 handler master data
 * 3. Template Generator: Pembuatan template Excel kosong dan export data live database
 * 4. Foreign Key Resolver: Lookup cerdas nama/kode relasi dan proteksi invalid FK
 * 5. Diffing Engine: Klasifikasi status baris (INSERT, UPDATE, NO_CHANGE / 100% Sinkron, ERROR)
 * 6. Sensor Transaksi & Full-Sync: Proteksi data dengan riwayat (soft-deactivate vs hard delete)
 * 7. Transaksional Atomic & Rollback: Eksekusi transaksi PDO dan pembersihan temporary file
 * 8. RBAC & Security: Verifikasi izin system.import_data pada tabel izin & peran
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set session Developer untuk pengujian CLI
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

// Load composer vendor jika ada (PhpSpreadsheet)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
}

// Autoloader untuk class App\*
spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = APP_ROOT . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use App\Services\Import\SmartReader;
use App\Services\Import\ImportProcessor;
use App\Services\Import\TemplateGenerator;
use App\Services\Import\Handlers\EntityImportHandlerInterface;
use App\Services\Import\Handlers\CustomerImportHandler;
use App\Services\Import\Handlers\ProductItemImportHandler;
use App\Services\Import\Handlers\MaterialItemImportHandler;
use App\Services\Import\Handlers\PricingMatrixImportHandler;
use App\Services\Import\Handlers\EmployeeImportHandler;
use App\Services\Import\Handlers\SupplierImportHandler;

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn): void {
    global $passed, $failed, $totalTests;
    $totalTests++;
    echo "Testing: [{$totalTests}] {$title} ... ";
    try {
        $result = $fn();
        if ($result === true || $result === null) {
            echo "\033[32m[PASS]\033[0m\n";
            $passed++;
        } else {
            echo "\033[31m[FAIL]\033[0m (" . (is_string($result) ? $result : 'Assertion returned false') . ")\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "\033[31m[ERROR]\033[0m: " . $e->getMessage() . "\n";
        echo "  Line: " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "  TEST SUITE: MASTER DATA IMPORT, DIFFING & RECONCILIATION LIFECYCLE\n";
echo "====================================================================\n\n";

$pdo = Database::getConnection();

// ==================================================================
// 1. SMART READER UTILITIES & DATA NORMALIZER
// ==================================================================
echo "--- 1. SMART READER UTILITIES ---\n";

runTest("1.1 - SmartReader::cleanHeader membersihkan spasi, simbol, dan casing", function() {
    $rawHeaders = [" Nama Pelanggan * ", "Kode-SKU", "Harga Jual (PCS)", "Status_Aktif? "];
    $cleaned = array_map([SmartReader::class, 'cleanHeader'], $rawHeaders);
    
    return $cleaned[0] === 'nama_pelanggan' &&
           $cleaned[1] === 'kode_sku' &&
           $cleaned[2] === 'harga_jual_pcs' &&
           $cleaned[3] === 'status_aktif';
});

runTest("1.2 - SmartReader::findBestMatch mencocokkan alias exact dan partial", function() {
    $fileHeaders = ['no', 'nama_konsumen', 'kontak_hp', 'alamat_lengkap'];
    
    $idxNama = SmartReader::findBestMatch($fileHeaders, ['nama_pelanggan', 'nama_toko', 'nama_konsumen', 'nama']);
    $idxTelp = SmartReader::findBestMatch($fileHeaders, ['telepon', 'kontak_hp', 'no_hp']);
    $idxNone = SmartReader::findBestMatch($fileHeaders, ['email', 'surel']);
    
    return $idxNama === 1 && $idxTelp === 2 && $idxNone === null;
});

runTest("1.3 - SmartReader::cleanNumber menangani format Rupiah, pemisah titik/koma, dan minus", function() {
    $t1 = SmartReader::cleanNumber('Rp 1.500.000,50');
    $t2 = SmartReader::cleanNumber('1,500,000.50');
    $t3 = SmartReader::cleanNumber('Rp 25.000');
    $t4 = SmartReader::cleanNumber('-500');
    $t5 = SmartReader::cleanNumber('-');
    $t6 = SmartReader::cleanNumber('');
    $t7 = SmartReader::cleanNumber(null);

    return abs($t1 - 1500000.50) < 0.001 &&
           abs($t2 - 1500000.50) < 0.001 &&
           abs($t3 - 25000.0) < 0.001 &&
           abs($t4 - (-500.0)) < 0.001 &&
           $t5 === 0.0 &&
           $t6 === 0.0 &&
           $t7 === 0.0;
});

runTest("1.4 - SmartReader::cleanBoolean menangani teks truthy dan falsy Indonesia/Inggris", function() {
    $trueValues = ['1', 1, 'true', 'TRUE', 'ya', 'Ya', 'YES', 'aktif', 'Aktif', 'y'];
    $falseValues = ['0', 0, 'false', 'FALSE', 'tidak', 'Tidak', 'NO', 'nonaktif', 'Nonaktif', 'n'];

    foreach ($trueValues as $val) {
        if (SmartReader::cleanBoolean($val, false) !== true) {
            return "Expected true for: " . var_export($val, true);
        }
    }

    foreach ($falseValues as $val) {
        if (SmartReader::cleanBoolean($val, true) !== false) {
            return "Expected false for: " . var_export($val, true);
        }
    }

    // Default fallback check
    if (SmartReader::cleanBoolean('', true) !== true || SmartReader::cleanBoolean(null, true) !== true) {
        return "Expected default true for empty/null";
    }
    if (SmartReader::cleanBoolean('', false) !== false || SmartReader::cleanBoolean(null, false) !== false) {
        return "Expected default false for empty/null";
    }

    return true;
});

runTest("1.5 - SmartReader::extractCodeFromName mengekstrak kode dalam kurung siku atau awalan", function() {
    $c1 = SmartReader::extractCodeFromName('[CUST-001] Toko Berkah Jaya');
    $c2 = SmartReader::extractCodeFromName('SKU-1002 - Keripik Singkong Original');
    $c3 = SmartReader::extractCodeFromName('Toko Sumber Rejeki (CUST-003)');
    $c4 = SmartReader::extractCodeFromName('Grup Retail Umum');

    return $c1 === 'CUST-001' &&
           $c2 === 'SKU-1002' &&
           $c3 === 'CUST-003' &&
           $c4 === null;
});

runTest("1.6 - SmartReader::isSimilarName mendeteksi kemiripan nama dengan toleransi typo", function() {
    $sim1 = SmartReader::isSimilarName('Keripik Singkong Balado', 'Keripik Singkong Balado');
    $sim2 = SmartReader::isSimilarName('Keripik Singkong Balado', 'keripik singkong balado ');
    $sim3 = SmartReader::isSimilarName('Toko Berkah Abadi', 'Toko Berkah Abad'); // typo 1 huruf
    $sim4 = SmartReader::isSimilarName('Bahan Baku Tepung', 'Kardus Karton Box'); // sama sekali beda

    return $sim1 === true &&
           $sim2 === true &&
           $sim3 === true &&
           $sim4 === false;
});

runTest("1.7 - SmartReader::extractSmartHeader & filterSmartDataRows menyaring header dan footer", function() {
    $rawSheet = [
        ['CATATAN: File Ekspor ERP Keren Snack', '', ''],
        ['Silakan isi data di bawah ini', '', ''],
        ['Kode Pelanggan', 'Nama Toko', 'Alamat Lengkap'], // Baris Header Sebenarnya (index 2)
        ['PEL-001', 'Toko A', 'Jl. Merdeka 1'],
        ['PEL-002', 'Toko B', 'Jl. Sudirman 2'],
        ['', '', ''], // Baris Kosong
        ['Total: 2 Pelanggan', '', ''], // Baris Footer
    ];

    $extracted = SmartReader::extractSmartHeader($rawSheet, [
        ['nama_toko', 'nama_pelanggan'],
        ['alamat_lengkap', 'alamat']
    ]);

    if ($extracted['index'] !== 2) {
        return "Header index expected 2, got: " . $extracted['index'];
    }

    $rowsRaw = array_slice($rawSheet, $extracted['index'] + 1);
    $filteredRows = SmartReader::filterSmartDataRows($rowsRaw);

    return count($filteredRows) === 2 &&
           $filteredRows[0][0] === 'PEL-001' &&
           $filteredRows[1][0] === 'PEL-002';
});

runTest("1.8 - CustomerImportHandler::normalizePaymentType menangani spasi, huruf besar-kecil, dan default", function() {
    $tests = [
        'Tempo Faktur'   => 'tempo_faktur',
        'tempo faktur'   => 'tempo_faktur',
        'TEMPO_FAKTUR'   => 'tempo_faktur',
        'Tempo Tanggal'  => 'tempo_tanggal',
        'tempo-tanggal'  => 'tempo_tanggal',
        'Konsinyasi'     => 'konsinyasi',
        'titip jual'     => 'konsinyasi',
        'Transfer'       => 'transfer',
        'TRF BANK'       => 'transfer',
        'QRIS'           => 'qris',
        'qris bayar'     => 'qris',
        'Cash'           => 'cash',
        'Tunai Langsung' => 'cash',
        ''               => 'cash',
    ];

    foreach ($tests as $input => $expected) {
        $actual = CustomerImportHandler::normalizePaymentType($input, false);
        if ($actual !== $expected) {
            return "Failed for '{$input}': expected '{$expected}', got '{$actual}'";
        }
    }

    if (CustomerImportHandler::normalizePaymentType('', true) !== 'konsinyasi') {
        return "Failed default konsinyasi";
    }

    return true;
});

// ==================================================================
// 2. HANDLER REGISTRY & INTERFACE CONTRACT
// ==================================================================
echo "\n--- 2. HANDLER REGISTRY & CONTRACTS ---\n";

$expectedEntities = [
    'brands', 'customers', 'customer_groups', 'territories', 'suppliers', 'employees',
    'product_groups', 'products', 'materials', 'pricing_matrix', 'piece_rates'
];

runTest("2.1 - ImportProcessor::getHandlers mengembalikan tepat 11 handler master data", function() use ($expectedEntities) {
    $handlers = ImportProcessor::getHandlers();
    if (count($handlers) !== 11) {
        return "Expected 11 handlers, got " . count($handlers);
    }
    foreach ($expectedEntities as $key) {
        if (!isset($handlers[$key])) {
            return "Missing handler for: {$key}";
        }
    }
    return true;
});

runTest("2.2 - Seluruh handler mengimplementasikan EntityImportHandlerInterface secara sah", function() {
    $handlers = ImportProcessor::getHandlers();
    foreach ($handlers as $key => $handler) {
        if (!($handler instanceof EntityImportHandlerInterface)) {
            return "Handler {$key} does not implement EntityImportHandlerInterface";
        }
    }
    return true;
});

runTest("2.3 - Seluruh handler memiliki metadata template (headers & widths) yang seimbang", function() {
    $handlers = ImportProcessor::getHandlers();
    foreach ($handlers as $key => $handler) {
        $headers = $handler->getTemplateHeaders();
        $widths = $handler->getTemplateWidths();
        if (empty($headers)) {
            return "Handler {$key} has empty template headers";
        }
        if (count($headers) !== count($widths)) {
            return "Handler {$key} headers count (" . count($headers) . ") != widths count (" . count($widths) . ")";
        }
        if (empty($handler->getRequiredHeaderGroups())) {
            return "Handler {$key} has empty required header groups";
        }
    }
    return true;
});

runTest("2.4 - Seluruh handler memiliki contoh baris dan catatan panduan", function() {
    $handlers = ImportProcessor::getHandlers();
    foreach ($handlers as $key => $handler) {
        $examples = $handler->getTemplateExamples();
        $notes = $handler->getTemplateNotes();
        if (empty($examples) || !is_array($examples)) {
            return "Handler {$key} has invalid template examples";
        }
        if (empty($notes) || !is_array($notes)) {
            return "Handler {$key} has invalid template notes";
        }
    }
    return true;
});

// ==================================================================
// 3. TEMPLATE GENERATOR ENGINE
// ==================================================================
echo "\n--- 3. TEMPLATE GENERATOR ENGINE ---\n";

runTest("3.1 - TemplateGenerator mode kosong (contoh) menghasilkan spreadsheet valid untuk 11 handler", function() use ($expectedEntities, $pdo) {
    foreach ($expectedEntities as $type) {
        $spreadsheet = TemplateGenerator::generate($type, false, $pdo);
        $sheet = $spreadsheet->getActiveSheet();
        if ($sheet->getTitle() === '') {
            return "Spreadsheet title empty for {$type}";
        }
        $valA1 = $sheet->getCell('A1')->getValue();
        if (empty($valA1)) {
            return "Cell A1 empty in template for {$type}";
        }
    }
    return true;
});

runTest("3.2 - Seluruh 11 handler getCurrentDataRows query valid terhadap live database", function() use ($pdo) {
    $handlers = ImportProcessor::getHandlers();
    foreach ($handlers as $key => $handler) {
        $rows = $handler->getCurrentDataRows($pdo);
        if (!is_array($rows)) {
            return "Handler {$key} getCurrentDataRows did not return array";
        }
    }
    return true;
});

// ==================================================================
// 4. FOREIGN KEY RESOLUTION & VALIDATION
// ==================================================================
echo "\n--- 4. FOREIGN KEY RESOLUTION & VALIDATION ---\n";

runTest("4.1 - CustomerImportHandler: Resolusi foreign key grup, wilayah, dan sales", function() use ($pdo) {
    $handler = new CustomerImportHandler();

    // Dapatkan data master riil yang ada di database
    $grupRow = $pdo->query("SELECT id, kode_grup, nama_grup FROM public.grup_pelanggan LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $wilayahRow = $pdo->query("SELECT id, nama_wilayah FROM public.wilayah LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    $header = $handler->getTemplateHeaders();

    $testRow = [
        'TEST-PEL-999',
        'Toko Test Lookup Cerdas',
        'Bpk Test',
        $grupRow ? $grupRow['nama_grup'] : '',
        $wilayahRow ? $wilayahRow['nama_wilayah'] : '',
        'Reguler',
        'Jl. Pengujian No. 123',
        '081122334455',
        'Tempo Faktur',
        '1000000',
        '',
        '',
        '',
        '',
        'Aktif'
    ];

    $preview = $handler->previewRows([$testRow], $header, $pdo, 'append');
    if (empty($preview)) {
        return "Preview result empty";
    }

    $first = $preview[0];
    if ($first['action'] === 'ERROR') {
        return "Unexpected error in preview: " . ($first['error_msg'] ?? '');
    }

    if ($grupRow && ($first['data']['grup_pelanggan_id'] ?? null) !== $grupRow['id']) {
        return "Failed to resolve grup_pelanggan_id";
    }
    if ($wilayahRow && ($first['data']['wilayah_id'] ?? null) !== $wilayahRow['id']) {
        return "Failed to resolve wilayah_id";
    }
    if (($first['data']['tipe_pembayaran_default'] ?? '') !== 'tempo_faktur') {
        return "Failed to normalize payment type 'Tempo Faktur' to 'tempo_faktur'";
    }

    return true;
});

runTest("4.2 - CustomerImportHandler: FK yang tidak ada di database menghasilkan status ERROR", function() use ($pdo) {
    $handler = new CustomerImportHandler();
    $header = $handler->getTemplateHeaders();

    $testRow = [
        'TEST-PEL-ERR',
        'Toko Fiktif FK Salah',
        'Bpk Salah',
        'GRUP_YANG_TIDAK_PERNAH_ADA_DI_DATABASE_12345',
        '',
        'Reguler',
        'Jl. Hantu No. 404',
        '081111111',
        'Cash',
        '0',
        '',
        '',
        '',
        '',
        'Aktif'
    ];

    $preview = $handler->previewRows([$testRow], $header, $pdo, 'append');
    $first = $preview[0];

    return $first['action'] === 'ERROR' &&
           !empty($first['error_msg']) &&
           str_contains($first['error_msg'], 'GRUP_YANG_TIDAK_PERNAH_ADA');
});

runTest("4.3 - ProductItemImportHandler: Resolusi relasi grup produk & upah borongan", function() use ($pdo) {
    $handler = new ProductItemImportHandler();
    $grupProduk = $pdo->query("SELECT id, nama_grup FROM public.grup_produk WHERE status_aktif = TRUE LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if (!$grupProduk) {
        return true; // Lewati jika tidak ada grup produk
    }

    $header = [
        'Kode SKU', 'Nama Varian / Item', 'Grup Produk', 'Satuan Dasar',
        'Harga Pokok (HPP)', 'Upah Borongan / Pcs',
        'Kelompok Upah Borongan', 'Stok Minimum Peringatan', 'Status Jual', 'Status Aktif'
    ];

    $testRow = [
        'SKU-TEST-RESOLVE-1',
        'Item Uji Resolusi',
        $grupProduk['nama_grup'],
        'pcs',
        '10000',
        '500',
        '',
        '10',
        'Dijual',
        'Aktif'
    ];

    $preview = $handler->previewRows([$testRow], $header, $pdo, 'append');
    $first = $preview[0];

    return $first['action'] === 'INSERT' &&
           ($first['data']['grup_id'] ?? null) === $grupProduk['id'];
});

// ==================================================================
// 5. DIFFING ENGINE STATE CLASSIFICATION
// ==================================================================
echo "\n--- 5. DIFFING ENGINE STATE CLASSIFICATION ---\n";

runTest("5.1 - Diffing Engine: Baris dengan kode baru menghasilkan status INSERT", function() use ($pdo) {
    $handler = new CustomerImportHandler();
    $header = $handler->getTemplateHeaders();
    $wilayah = $pdo->query("SELECT nama_wilayah FROM public.wilayah WHERE status_aktif = TRUE LIMIT 1")->fetchColumn() ?: 'Kota Tangerang';

    $row = [
        'CUST-NONEXISTENT-' . time(),
        'Toko Baru Lahir',
        'Owner Baru',
        '', $wilayah, 'Reguler', 'Jl. Baru No. 1', '0812345', 'Tempo 7 Hari', 0, '', '', '', '', 'Aktif'
    ];

    $preview = $handler->previewRows([$row], $header, $pdo, 'append');
    return !empty($preview) && $preview[0]['action'] === 'INSERT';
});

runTest("5.2 - Diffing Engine: Baris dengan kode sama tetapi field diubah menghasilkan status UPDATE", function() use ($pdo) {
    $handler = new CustomerImportHandler();
    $existing = $pdo->query("SELECT p.id, p.kode_pelanggan, p.nama_toko, p.alamat_lengkap, COALESCE(w.nama_wilayah, '') as nama_wilayah FROM public.pelanggan p LEFT JOIN public.wilayah w ON w.id = p.wilayah_id WHERE p.wilayah_id IS NOT NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        return true;
    }

    $header = $handler->getTemplateHeaders();
    $updatedAlamat = 'Alamat Modifikasi Test ' . time();

    $row = [
        $existing['kode_pelanggan'],
        $existing['nama_toko'],
        '', '', $existing['nama_wilayah'], 'Reguler', $updatedAlamat, '', 'Cash', 0, '', '', '', '', 'Aktif'
    ];

    $preview = $handler->previewRows([$row], $header, $pdo, 'append');
    if (empty($preview)) {
        return "Preview returned empty for modified row";
    }
    $first = $preview[0];

    return $first['action'] === 'UPDATE' &&
           $first['data']['alamat_lengkap'] === $updatedAlamat &&
           $first['old_data']['alamat_lengkap'] !== $updatedAlamat;
});

runTest("5.3 - Diffing Engine: Baris dengan seluruh field identik tidak menghasilkan entri diff (100% sinkron)", function() use ($pdo) {
    $handler = new CustomerImportHandler();
    $header = $handler->getTemplateHeaders();
    $currentRows = $handler->getCurrentDataRows($pdo);

    if (empty($currentRows)) {
        return true;
    }

    $row = null;
    foreach ($currentRows as $cr) {
        if (!empty($cr[4])) { // index 4 is nama_wilayah
            $row = array_values($cr);
            break;
        }
    }
    if (!$row) {
        return true;
    }

    $preview = $handler->previewRows([$row], $header, $pdo, 'append');
    // Jika data 100% sinkron dengan database, preview harus kosong (0 diff)
    return count($preview) === 0;
});

runTest("5.4 - Diffing Engine: Baris tanpa field wajib (nama kosong) menghasilkan status ERROR", function() use ($pdo) {
    $handler = new CustomerImportHandler();
    $header = $handler->getTemplateHeaders();

    $row = [
        'CUST-NO-NAME',
        '', // NAMA TOKO KOSONG (Wajib)
        'Owner', '', 'Kota Tangerang', 'Reguler', 'Jl. Ada', '', 'Cash', 0, '', '', '', '', 'Aktif'
    ];

    $preview = $handler->previewRows([$row], $header, $pdo, 'append');
    return !empty($preview) &&
           $preview[0]['action'] === 'ERROR' &&
           !empty($preview[0]['error_msg']);
});

runTest("5.5 - SupplierImportHandler: Baris dengan wilayah kosong / tidak terdaftar menghasilkan status ERROR", function() use ($pdo) {
    $handler = new SupplierImportHandler();
    $header = $handler->getTemplateHeaders();

    // 1. Wilayah kosong
    $rowEmpty = ['', 'Vendor Uji Wilayah Kosong', 'PIC', '', 'Jl. Raya', '08123', '', 'cash', 'BCA', '123', 'PT', '', 'Aktif'];
    $prevEmpty = $handler->previewRows([$rowEmpty], $header, $pdo, 'append');
    if (empty($prevEmpty) || $prevEmpty[0]['action'] !== 'ERROR') {
        return "Empty territory was not rejected with ERROR";
    }

    // 2. Wilayah tidak terdaftar
    $rowFake = ['', 'Vendor Uji Wilayah Fiktif', 'PIC', 'WILAYAH_TIDAK_TERDAFTAR_99999', 'Jl. Raya', '08123', '', 'cash', 'BCA', '123', 'PT', '', 'Aktif'];
    $prevFake = $handler->previewRows([$rowFake], $header, $pdo, 'append');
    if (empty($prevFake) || $prevFake[0]['action'] !== 'ERROR') {
        return "Unregistered territory was not rejected with ERROR";
    }

    return true;
});

// ==================================================================
// 6. SENSOR RIWAYAT TRANSAKSI & FULL-SYNC RECONCILIATION
// ==================================================================
echo "\n--- 6. SENSOR RIWAYAT TRANSAKSI & FULL-SYNC RECONCILIATION ---\n";

runTest("6.1 - CustomerImportHandler::hasTransactionHistory mendeteksi relasi riwayat transaksi", function() use ($pdo) {
    $handler = new CustomerImportHandler();

    // Cari pelanggan yang punya riwayat pesanan jika ada
    $activeCustId = $pdo->query("
        SELECT pelanggan_id FROM (
            SELECT pelanggan_id FROM public.pesanan WHERE pelanggan_id IS NOT NULL
            UNION
            SELECT pelanggan_id FROM public.stok_konsinyasi_toko WHERE pelanggan_id IS NOT NULL
            UNION
            SELECT pelanggan_id FROM public.kunjungan_konsinyasi WHERE pelanggan_id IS NOT NULL
        ) t LIMIT 1
    ")->fetchColumn();

    if ($activeCustId) {
        $hasHistory = $handler->hasTransactionHistory((string)$activeCustId, $pdo);
        if (!$hasHistory) {
            return "Expected true for active customer with transactions";
        }
    }

    // Buat dummy id acak yang pasti tidak punya riwayat
    $fakeId = 'ffffffff-ffff-ffff-ffff-ffffffffffff';
    $noHistory = $handler->hasTransactionHistory($fakeId, $pdo);

    return $noHistory === false;
});

runTest("6.2 - ProductItemImportHandler::hasTransactionHistory mendeteksi relasi mutasi stok/penjualan", function() use ($pdo) {
    $handler = new ProductItemImportHandler();

    $activeItemId = $pdo->query("
        SELECT item_id FROM (
            SELECT item_id FROM public.riwayat_stok WHERE item_id IS NOT NULL
            UNION
            SELECT item_id FROM public.item_pesanan WHERE item_id IS NOT NULL
            UNION
            SELECT item_id FROM public.stok_konsinyasi_toko WHERE item_id IS NOT NULL
        ) t LIMIT 1
    ")->fetchColumn();

    if ($activeItemId) {
        $hasHistory = $handler->hasTransactionHistory((string)$activeItemId, $pdo);
        if (!$hasHistory) {
            return "Expected true for active item with ledger movements";
        }
    }

    $fakeId = 'ffffffff-ffff-ffff-ffff-ffffffffffff';
    return $handler->hasTransactionHistory($fakeId, $pdo) === false;
});

runTest("6.3 - Full-Sync: Mode full_sync menandai data database yang hilang di file Excel sebagai DELETE & melindungi CUST-001", function() use ($pdo) {
    $handler = new CustomerImportHandler();
    $header = $handler->getTemplateHeaders();

    // Buat pelanggan sementara untuk uji full-sync
    $tmpCustId = 'a1b2c3d4-e5f6-7890-abcd-ef1234567890';
    $grupId = $pdo->query("SELECT id FROM public.grup_pelanggan LIMIT 1")->fetchColumn();
    $pdo->exec("INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, alamat_lengkap, grup_pelanggan_id) 
                VALUES ('{$tmpCustId}', 'CUST-TMP-SYNC', 'Toko Uji Sync', 'Jl. Sync No. 1', '{$grupId}')");

    try {
        // Jalankan preview mode full_sync dengan file kosong (seolah-olah user menghapus semua baris di file)
        $preview = $handler->previewRows([], $header, $pdo, 'full_sync');

        $foundTmp = false;
        $foundCust001 = false;
        foreach ($preview as $r) {
            if ($r['action'] !== 'DELETE') {
                return "Expected action DELETE for missing rows, got: " . $r['action'];
            }
            if (($r['data']['kode_pelanggan'] ?? '') === 'CUST-TMP-SYNC') {
                $foundTmp = true;
            }
            if (($r['data']['kode_pelanggan'] ?? '') === 'CUST-001') {
                $foundCust001 = true;
            }
        }

        if (!$foundTmp) {
            return "Expected temporary customer CUST-TMP-SYNC to be marked as DELETE in full-sync";
        }
        if ($foundCust001) {
            return "CUST-001 (default walk-in customer) must NEVER be marked as DELETE in full-sync";
        }
    } finally {
        $pdo->exec("DELETE FROM public.pelanggan WHERE id = '{$tmpCustId}'");
    }

    return true;
});

// ==================================================================
// 7. ATOMIC TRANSACTION & ROLLBACK SAFETY
// ==================================================================
echo "\n--- 7. ATOMIC TRANSACTION & ROLLBACK SAFETY ---\n";

runTest("7.1 - applySyncFromPreview menolak eksekusi jika terdapat baris berstatus ERROR atau FATAL", function() use ($pdo) {
    // 1. Uji penolakan baris ERROR
    $tmpFileErr = sys_get_temp_dir() . '/ks_test_err_' . uniqid() . '.json';
    $mockPreviewErr = [
        [
            'action' => 'ERROR',
            'error_msg' => 'Kolom nama wajib diisi',
            'data' => []
        ]
    ];
    file_put_contents($tmpFileErr, json_encode($mockPreviewErr));

    $rejectedErr = false;
    try {
        ImportProcessor::applySyncFromPreview($tmpFileErr, 'customers', $pdo);
    } catch (RuntimeException $e) {
        $rejectedErr = str_contains($e->getMessage(), 'ERROR') || str_contains($e->getMessage(), 'FATAL');
    } finally {
        if (file_exists($tmpFileErr)) @unlink($tmpFileErr);
    }

    // 2. Uji penolakan baris FATAL (Konflik Identitas)
    $tmpFileFatal = sys_get_temp_dir() . '/ks_test_fatal_' . uniqid() . '.json';
    $mockPreviewFatal = [
        [
            'action' => 'INSERT',
            'is_fatal' => true,
            'fatal_reason' => 'Kode bentrok di database',
            'data' => [
                'kode_grup' => 'GRP-001',
                'nama_grup' => 'Nama Sangat Berbeda'
            ]
        ]
    ];
    file_put_contents($tmpFileFatal, json_encode($mockPreviewFatal));

    $rejectedFatal = false;
    try {
        ImportProcessor::applySyncFromPreview($tmpFileFatal, 'product_groups', $pdo);
    } catch (RuntimeException $e) {
        $rejectedFatal = str_contains($e->getMessage(), 'FATAL') || str_contains($e->getMessage(), 'ERROR');
    } finally {
        if (file_exists($tmpFileFatal)) @unlink($tmpFileFatal);
    }

    return $rejectedErr && $rejectedFatal;
});

runTest("7.2 - Eksekusi applySync terisolasi di dalam sub-transaksi berhasil di-rollback tanpa polusi data", function() use ($pdo) {
    $handler = new CustomerImportHandler();
    $testKode = 'TEST-ROLLBACK-' . uniqid();

    $mockRow = [
        'action' => 'INSERT',
        'data' => [
            'id' => null,
            'kode_pelanggan' => $testKode,
            'nama_toko' => 'Toko Rollback Verification',
            'nama_pemilik' => 'Tester',
            'grup_pelanggan_id' => null,
            'wilayah_id' => null,
            'rute_pengiriman_id' => null,
            'is_konsinyasi' => false,
            'alamat_lengkap' => 'Jl. Rollback No. 99',
            'nomor_whatsapp' => '08999999',
            'tipe_pembayaran_default' => 'cash',
            'plafon_piutang' => 0.0,
            'sales_driver_id' => null,
            'nama_bank' => null,
            'nomor_rekening' => null,
            'atas_nama_rekening' => null,
            'status_aktif' => true
        ]
    ];

    $pdo->beginTransaction();
    try {
        $stats = $handler->applySync([$mockRow], $pdo);
        if ($stats['insert'] !== 1) {
            $pdo->rollBack();
            return "Expected 1 insert in stats";
        }

        // Verifikasi data sempat masuk di dalam transaksi
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM public.pelanggan WHERE kode_pelanggan = ?");
        $stmt->execute([$testKode]);
        $countInside = (int)$stmt->fetchColumn();

        if ($countInside !== 1) {
            $pdo->rollBack();
            return "Data not found inside transaction";
        }

        // Selalu rollback agar database live bersih kembali
        $pdo->rollBack();

        // Verifikasi setelah rollback data sudah lenyap
        $stmt->execute([$testKode]);
        $countOutside = (int)$stmt->fetchColumn();

        return $countOutside === 0;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
});

runTest("7.3 - Pembersihan file preview temporary JSON berjalan otomatis setelah eksekusi", function() use ($pdo) {
    $tmpFile = sys_get_temp_dir() . '/ks_test_clean_' . uniqid() . '.json';
    $testKode = 'TEST-CLEANUP-' . uniqid();
    $mockPreview = [
        [
            'action' => 'INSERT',
            'data' => [
                'id' => null,
                'kode_pelanggan' => $testKode,
                'nama_toko' => 'Toko Cleanup Test',
                'alamat_lengkap' => 'Jl. Bersih No. 1',
                'status_aktif' => true
            ]
        ]
    ];
    file_put_contents($tmpFile, json_encode($mockPreview));

    if (!file_exists($tmpFile)) {
        return "Failed to create temp file";
    }

    try {
        $res = ImportProcessor::applySyncFromPreview($tmpFile, 'customers', $pdo);
        return !file_exists($tmpFile) && isset($res['stats']);
    } finally {
        $pdo->exec("DELETE FROM public.pelanggan WHERE kode_pelanggan = " . $pdo->quote($testKode));
        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
    }
});

// ==================================================================
// 8. RBAC & PERMISSION SYSTEM VERIFICATION
// ==================================================================
echo "\n--- 8. RBAC & PERMISSION VERIFICATION ---\n";

runTest("8.1 - Izin 'system.import_data' terdaftar sah di tabel public.izin", function() use ($pdo) {
    $stmt = $pdo->prepare("SELECT kode_izin, grup_izin, nama_izin FROM public.izin WHERE kode_izin = ?");
    $stmt->execute(['system.import_data']);
    $perm = $stmt->fetch(PDO::FETCH_ASSOC);

    return !empty($perm) &&
           $perm['kode_izin'] === 'system.import_data' &&
           $perm['grup_izin'] === 'Hak Akses & Sistem';
});

runTest("8.2 - Izin 'system.import_data' terpasang pada peran Developer, Owner, dan Admin", function() use ($pdo) {
    $stmt = $pdo->prepare("
        SELECT p.nama_peran
        FROM public.izin_peran ip
        JOIN public.izin i ON i.id = ip.izin_id
        JOIN public.peran p ON p.id = ip.peran_id
        WHERE i.kode_izin = ?
    ");
    $stmt->execute(['system.import_data']);
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $hasDev = in_array('developer', $roles, true);
    $hasOwner = in_array('owner', $roles, true);
    $hasAdmin = in_array('admin', $roles, true);

    return $hasDev && $hasOwner && $hasAdmin;
});

// ==================================================================
// REKAPITULASI HASIL TEST
// ==================================================================
echo "\n====================================================================\n";
echo "  HASIL REKAPITULASI PENGUJIAN IMPOR DATA\n";
echo "  Total: {$totalTests} | Passed: \033[32m{$passed}\033[0m | Failed: \033[31m{$failed}\033[0m\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);

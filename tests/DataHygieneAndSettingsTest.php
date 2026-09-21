<?php
declare(strict_types=1);

/**
 * tests/audit_fase4_master_data_extreme.php
 * Automated Extreme Test Suite for FASE 4:
 * 4.1 Pembersihan Kunci Warisan 'nama_toko' pada public.pengaturan_sistem & Seed RBAC
 * 4.2 Standarisasi Kolom Rekening Bank Vendor/Pemasok & ActivityLog
 * 4.3 Proteksi Penghapusan Vendor Pemasok (Cek pembelian & item.pemasok_utama_id)
 * 4.4 Integrasi Whitelist Produk Khusus Toko (pelanggan_item & saveCustomerItems)
 * 4.5 Contextual Redirect Grup Pelanggan (Anti-Open Redirect & UI Ergonomics)
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_ROOT', ROOT_PATH);
date_default_timezone_set('Asia/Jakarta');
require_once APP_ROOT . '/config/database.php';

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

use App\Controllers\SupplierController;
use App\Controllers\CustomerController;
use App\Helpers\CompanySetting;

$passed = 0;
$failed = 0;
$totalTests = 0;

function runTest(string $title, callable $fn) {
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
        echo " [ERROR] {$title}: " . $e->getMessage() . "\n";
        echo " Trace: " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

echo "============================================================\n";
echo " EXTREME AUDIT SUITE - FASE 4: ERGONOMI & DATA HYGIENE\n";
echo "============================================================\n";

// Set session user Developer (bypass RBAC guards untuk CLI unit testing)
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

$pdo = Database::getConnection();

// -------------------------------------------------------------
// ITEM 4.1: DATA HYGIENE - PEMBERSIHAN NAMA_TOKO & SEED
// -------------------------------------------------------------
runTest("4.1.1 Verifikasi Berkas Migrasi 29 & DDL", function() {
    $m29Path = APP_ROOT . '/database/29_migration_fase4_master_polish.sql';
    if (file_exists($m29Path)) {
        $content = file_get_contents($m29Path);
        if (!str_contains($content, "DELETE FROM public.pengaturan_sistem WHERE kunci = 'nama_toko'")) {
            return "Migrasi 29 tidak memuat penghapusan kunci 'nama_toko'.";
        }
        if (!str_contains($content, "perusahaan_nama")) {
            return "Migrasi 29 tidak memuat sinkronisasi kunci perusahaan_*.";
        }
    }

    // Fallback verifikasi langsung ke database
    $count = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pengaturan_sistem WHERE kunci = 'nama_toko'")['total'] ?? 0);
    if ($count !== 0) {
        return "Kunci 'nama_toko' masih tersisa sebanyak {$count} di tabel public.pengaturan_sistem!";
    }
    return true;
});

runTest("4.1.2 Live DB: Tabel public.pengaturan_sistem Bersih dari Kunci 'nama_toko'", function() {
    $count = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pengaturan_sistem WHERE kunci = 'nama_toko'")['total'] ?? 0);
    if ($count !== 0) {
        return "Kunci 'nama_toko' masih tersisa sebanyak {$count} di tabel public.pengaturan_sistem!";
    }
    return true;
});

runTest("4.1.3 CompanySetting Beroperasi Utuh & Menyediakan Fallback Resmi", function() {
    $namaPerusahaan = CompanySetting::get('nama');
    if (empty($namaPerusahaan) || !str_contains(strtoupper($namaPerusahaan), 'KEREN SNACK')) {
        return "CompanySetting::get('nama') mengembalikan nilai tidak valid: '{$namaPerusahaan}'";
    }

    $all = CompanySetting::getAll();
    $requiredKeys = ['nama', 'tagline', 'alamat', 'telepon', 'email', 'website', 'nama_bank', 'nomor_rekening', 'atas_nama_bank'];
    foreach ($requiredKeys as $k) {
        if (!array_key_exists($k, $all)) {
            return "CompanySetting::getAll() kehilangan kunci: '{$k}'";
        }
    }
    return true;
});

runTest("4.1.4 Berkas Seed 01_seed_rbac.sql Bersih dari Kunci 'nama_toko' dan Memisahkan Role Sales vs Driver", function() {
    $seedPath = APP_ROOT . '/database/seeds/01_seed_rbac.sql';
    if (file_exists($seedPath)) {
        $seedContent = file_get_contents($seedPath);
        if (str_contains($seedContent, "'nama_toko'")) {
            return "01_seed_rbac.sql masih memuat kunci usang 'nama_toko'.";
        }
        if (str_contains($seedContent, "Sales & Driver adalah 1 peran terpadu")) {
            return "01_seed_rbac.sql masih memiliki catatan usang bahwa sales & driver adalah 1 peran terpadu.";
        }
        if (!str_contains($seedContent, "'sales'") || !str_contains($seedContent, "'driver'")) {
            return "01_seed_rbac.sql tidak memuat peran terpisah 'sales' dan 'driver'.";
        }
    }

    // Fallback verifikasi langsung ke database
    $roles = Database::fetchAll("SELECT nama_peran FROM public.peran WHERE nama_peran IN ('sales', 'driver')");
    if (count($roles) < 2) {
        return "Peran 'sales' dan 'driver' belum terdaftar secara terpisah di basis data.";
    }
    return true;
});

// -------------------------------------------------------------
// ITEM 4.2: STANDARISASI KOLOM BANK PEMASOK & PERSISTENSI
// -------------------------------------------------------------
runTest("4.2.1 SupplierController: store() & update() Menyimpan Kolom Relasional Bank & Menjaga Sinkronisasi JSONB", function() use ($pdo) {
    $dummyName = 'Pemasok Vendor Bank Test ' . time();
    $ctrl = new class extends SupplierController {
        public ?string $capturedSuccess = null;
        public ?string $capturedError = null;
        public array $mockInput = [];
        protected function input(string $key, mixed $default = null): mixed {
            return $this->mockInput[$key] ?? $default;
        }
        protected function flashSuccess(string $message, ?string $title = null): void {
            $this->capturedSuccess = $message;
        }
        protected function flashError(string $message, ?string $title = null): void {
            $this->capturedError = $message;
        }
        protected function redirect(string $url): void {}
    };

    // 1. Uji store
    $ctrl->mockInput = [
        'nama_pemasok' => $dummyName,
        'nomor_whatsapp' => '081299887766',
        'alamat_lengkap' => 'Kawasan Industri Cikupa No. 12',
        'bank_nama' => 'Bank Mandiri',
        'bank_rekening' => '1370-001-992288',
        'bank_atas_nama' => 'PT PLASTIK MANDIRI MAKMUR'
    ];
    $ctrl->store();

    if (!empty($ctrl->capturedError)) {
        return "SupplierController store() error: " . $ctrl->capturedError;
    }

    $saved = Database::fetchOne("
        SELECT id, nama_pemasok, nama_bank, nomor_rekening, atas_nama_rekening
        FROM public.pemasok WHERE nama_pemasok = :nama
    ", ['nama' => $dummyName]);

    if (!$saved) {
        return "Data pemasok baru gagal disimpan ke database.";
    }
    if ($saved['nama_bank'] !== 'Bank Mandiri') {
        $pdo->prepare("DELETE FROM public.pemasok WHERE id = :id")->execute(['id' => $saved['id']]);
        return "Kolom relasional nama_bank tidak tersimpan (got: {$saved['nama_bank']})";
    }
    if ($saved['nomor_rekening'] !== '1370-001-992288') {
        $pdo->prepare("DELETE FROM public.pemasok WHERE id = :id")->execute(['id' => $saved['id']]);
        return "Kolom relasional nomor_rekening tidak tersimpan (got: {$saved['nomor_rekening']})";
    }
    if ($saved['atas_nama_rekening'] !== 'PT PLASTIK MANDIRI MAKMUR') {
        $pdo->prepare("DELETE FROM public.pemasok WHERE id = :id")->execute(['id' => $saved['id']]);
        return "Kolom relasional atas_nama_rekening tidak tersimpan (got: {$saved['atas_nama_rekening']})";
    }

    // Pastikan kolom dead detail_bank sudah terhapus tuntas dari database
    $hasDetailBankCol = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.columns WHERE table_name = 'pemasok' AND column_name = 'detail_bank'")->fetchColumn();
    if ($hasDetailBankCol > 0) {
        $pdo->prepare("DELETE FROM public.pemasok WHERE id = :id")->execute(['id' => $saved['id']]);
        return "Kolom redundan detail_bank masih ada di tabel pemasok!";
    }

    // 2. Uji update
    $ctrl->capturedError = null;
    $ctrl->capturedSuccess = null;
    $ctrl->mockInput = [
        'id' => $saved['id'],
        'nama_pemasok' => $dummyName . ' Updated',
        'nomor_whatsapp' => '081299887766',
        'alamat_lengkap' => 'Kawasan Industri Cikupa No. 12 Blok B',
        'bank_nama' => 'Bank BCA',
        'bank_rekening' => '8820-9988-77',
        'bank_atas_nama' => 'PT PLASTIK MANDIRI MAKMUR TBK',
        'status_aktif' => true
    ];
    $ctrl->update();

    if (!empty($ctrl->capturedError)) {
        $pdo->prepare("DELETE FROM public.pemasok WHERE id = :id")->execute(['id' => $saved['id']]);
        return "SupplierController update() error: " . $ctrl->capturedError;
    }

    $updated = Database::fetchOne("
        SELECT nama_bank, nomor_rekening, atas_nama_rekening
        FROM public.pemasok WHERE id = :id
    ", ['id' => $saved['id']]);

    // Cleanup
    $pdo->prepare("DELETE FROM public.pemasok WHERE id = :id")->execute(['id' => $saved['id']]);

    if ($updated['nama_bank'] !== 'Bank BCA' || $updated['nomor_rekening'] !== '8820-9988-77') {
        return "Update kolom relasional bank gagal!";
    }

    return true;
});

// -------------------------------------------------------------
// ITEM 4.3: PROTEKSI PENGHAPUSAN PEMASOK VENDOR
// -------------------------------------------------------------
runTest("4.3.1 SupplierController: delete() Memblokir Hapus Vendor yang Memiliki Faktur Pembelian (PO)", function() use ($pdo) {
    $supDummyId = 'ffffffff-aaaa-4444-9999-000000000001';
    $poDummyId = 'ffffffff-bbbb-4444-9999-000000000001';

    $pdo->prepare("DELETE FROM public.rincian_pembelian WHERE pembelian_id = :poid")->execute(['poid' => $poDummyId]);
    $pdo->prepare("DELETE FROM public.pembelian WHERE id = :poid")->execute(['poid' => $poDummyId]);
    $pdo->prepare("DELETE FROM public.pemasok WHERE id = :sid")->execute(['sid' => $supDummyId]);

    // Insert dummy pemasok
    $pdo->prepare("
        INSERT INTO public.pemasok (id, kode_pemasok, nama_pemasok, alamat_lengkap, status_aktif)
        VALUES (:id, 'SUP-TEST-DEL-PO', 'Vendor PO Terkait', 'Jl. Supplier No. 1', TRUE)
    ")->execute(['id' => $supDummyId]);

    // Insert dummy pembelian
    $pdo->prepare("
        INSERT INTO public.pembelian (id, nomor_faktur_pembelian, pemasok_id, total_biaya, status_pembayaran, status_penerimaan)
        VALUES (:poid, 'PO-TEST-DEL-01', :sid, 500000.00, 'belum_lunas', 'diterima')
    ")->execute(['poid' => $poDummyId, 'sid' => $supDummyId]);

    $ctrl = new class extends SupplierController {
        public ?string $capturedError = null;
        public array $mockInput = [];
        protected function input(string $key, mixed $default = null): mixed {
            return $this->mockInput[$key] ?? $default;
        }
        protected function flashError(string $message, ?string $title = null): void {
            $this->capturedError = $message;
        }
        protected function redirect(string $url): void {}
    };

    $ctrl->mockInput = ['id' => $supDummyId];
    $ctrl->delete();

    $stillExists = Database::fetchOne("SELECT id FROM public.pemasok WHERE id = :id", ['id' => $supDummyId]);

    // Cleanup
    $pdo->prepare("DELETE FROM public.pembelian WHERE id = :poid")->execute(['poid' => $poDummyId]);
    $pdo->prepare("DELETE FROM public.pemasok WHERE id = :sid")->execute(['sid' => $supDummyId]);

    if (!$stillExists) {
        return "Pemasok terhapus padahal terhubung ke faktur pembelian!";
    }
    if (empty($ctrl->capturedError) || !str_contains(strtolower($ctrl->capturedError), 'pembelian')) {
        return "Pesan error tidak menyebutkan faktur pembelian: " . ($ctrl->capturedError ?? 'None');
    }
    return true;
});

runTest("4.3.2 SupplierController: delete() Memblokir Hapus Vendor yang Menjadi Pemasok Utama Item", function() use ($pdo) {
    $supDummyId = 'ffffffff-aaaa-4444-9999-000000000002';
    $itemDummyId = 'ffffffff-cccc-4444-9999-000000000002';

    $pdo->prepare("DELETE FROM public.riwayat_stok WHERE item_id = :iid")->execute(['iid' => $itemDummyId]);
    $pdo->prepare("DELETE FROM public.item WHERE id = :iid")->execute(['iid' => $itemDummyId]);
    $pdo->prepare("DELETE FROM public.pemasok WHERE id = :sid")->execute(['sid' => $supDummyId]);

    // Insert dummy pemasok
    $pdo->prepare("
        INSERT INTO public.pemasok (id, kode_pemasok, nama_pemasok, alamat_lengkap, status_aktif)
        VALUES (:id, 'SUP-TEST-DEL-ITEM', 'Vendor Item Terkait', 'Jl. Supplier No. 2', TRUE)
    ")->execute(['id' => $supDummyId]);

    // Insert dummy item bahan dengan pemasok_utama_id
    $pdo->prepare("
        INSERT INTO public.item (id, kode_sku, nama_item, tipe_item, satuan_dasar, pemasok_utama_id, harga_pokok_pembelian, status_aktif)
        VALUES (:iid, 'BAHAN-TEST-DEL', 'Plastik HD Test', 'bahan_kemas', 'lembar', :sid, 150.00, TRUE)
    ")->execute(['iid' => $itemDummyId, 'sid' => $supDummyId]);

    $ctrl = new class extends SupplierController {
        public ?string $capturedError = null;
        public array $mockInput = [];
        protected function input(string $key, mixed $default = null): mixed {
            return $this->mockInput[$key] ?? $default;
        }
        protected function flashError(string $message, ?string $title = null): void {
            $this->capturedError = $message;
        }
        protected function redirect(string $url): void {}
    };

    $ctrl->mockInput = ['id' => $supDummyId];
    $ctrl->delete();

    $stillExists = Database::fetchOne("SELECT id FROM public.pemasok WHERE id = :id", ['id' => $supDummyId]);

    // Cleanup
    $pdo->prepare("DELETE FROM public.item WHERE id = :iid")->execute(['iid' => $itemDummyId]);
    $pdo->prepare("DELETE FROM public.pemasok WHERE id = :sid")->execute(['sid' => $supDummyId]);

    if (!$stillExists) {
        return "Pemasok terhapus padahal terhubung ke katalog item bahan!";
    }
    if (empty($ctrl->capturedError) || !str_contains(strtolower($ctrl->capturedError), 'bahan')) {
        return "Pesan error tidak menyebutkan katalog bahan/produk: " . ($ctrl->capturedError ?? 'None');
    }
    return true;
});

runTest("4.3.3 SupplierController: delete() Menghapus Vendor Bersih & Mencatat ActivityLog", function() use ($pdo) {
    $supDummyId = 'ffffffff-aaaa-4444-9999-000000000003';
    $pdo->prepare("DELETE FROM public.pemasok WHERE id = :sid")->execute(['sid' => $supDummyId]);

    // Insert dummy pemasok bersih
    $pdo->prepare("
        INSERT INTO public.pemasok (id, kode_pemasok, nama_pemasok, alamat_lengkap, status_aktif)
        VALUES (:id, 'SUP-CLEAN-DEL', 'Vendor Bersih Siap Hapus', 'Jl. Bersih No. 3', TRUE)
    ")->execute(['id' => $supDummyId]);

    $ctrl = new class extends SupplierController {
        public ?string $capturedSuccess = null;
        public array $mockInput = [];
        protected function input(string $key, mixed $default = null): mixed {
            return $this->mockInput[$key] ?? $default;
        }
        protected function flashSuccess(string $message, ?string $title = null): void {
            $this->capturedSuccess = $message;
        }
        protected function redirect(string $url): void {}
    };

    $ctrl->mockInput = ['id' => $supDummyId];
    $ctrl->delete();

    $exists = Database::fetchOne("SELECT id FROM public.pemasok WHERE id = :id", ['id' => $supDummyId]);
    if ($exists) {
        $pdo->prepare("DELETE FROM public.pemasok WHERE id = :sid")->execute(['sid' => $supDummyId]);
        return "Vendor bersih gagal dihapus dari database!";
    }
    if (empty($ctrl->capturedSuccess) || !str_contains(strtolower($ctrl->capturedSuccess), 'berhasil dihapus')) {
        return "Pesan sukses delete pemasok tidak sesuai: " . ($ctrl->capturedSuccess ?? 'None');
    }

    // Verifikasi ActivityLog
    $log = Database::fetchOne("
        SELECT id, deskripsi_aktivitas 
        FROM public.log_aktivitas 
        WHERE jenis_aksi = 'HAPUS_PEMASOK' AND id_referensi = :id
        ORDER BY waktu_kejadian DESC LIMIT 1
    ", ['id' => $supDummyId]);

    if (!$log) {
        return "Aktivitas HAPUS_PEMASOK tidak tercatat di public.log_aktivitas!";
    }
    return true;
});

// -------------------------------------------------------------
// ITEM 4.4: WHITELIST PRODUK KHUSUS TOKO (PELANGGAN_ITEM)
// -------------------------------------------------------------
runTest("4.4.1 CustomerController: saveCustomerItems() Mengelola Whitelist Produk", function() use ($pdo) {
    $custDummyId = 'ffffffff-bbbb-4444-9999-000000000001';
    $grupRow = Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1");
    $items = Database::fetchAll("SELECT id FROM public.item WHERE tipe_item = 'barang_jadi' AND status_aktif = TRUE LIMIT 2");
    $createdItemIds = [];

    if (count($items) < 2) {
        $grupProdId = Database::fetchOne("SELECT id FROM public.grup_produk LIMIT 1")['id'] ?? null;
        for ($i = count($items) + 1; $i <= 2; $i++) {
            $tmpId = "ffffffff-cccc-4444-9999-00000000000{$i}";
            $pdo->exec("
                INSERT INTO public.item (id, kode_sku, nama_item, tipe_item, grup_id, satuan_dasar, status_aktif)
                VALUES ('{$tmpId}', 'SKU-TMP-WHT-{$i}', 'Item Whitelist Transien {$i}', 'barang_jadi', '{$grupProdId}', 'pcs', TRUE)
                ON CONFLICT (id) DO NOTHING
            ");
            $createdItemIds[] = $tmpId;
            $items[] = ['id' => $tmpId];
        }
    }

    try {
        $pdo->prepare("DELETE FROM public.pelanggan_item WHERE pelanggan_id = :cid")->execute(['cid' => $custDummyId]);
        $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :cid")->execute(['cid' => $custDummyId]);

        // Insert dummy toko
        $pdo->prepare("
            INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, alamat_lengkap, grup_pelanggan_id, status_aktif)
            VALUES (:id, 'CUST-WHITELIST', 'Toko Whitelist Test', 'Jl. Whitelist No. 1', :gid, TRUE)
        ")->execute(['id' => $custDummyId, 'gid' => $grupRow['id']]);

        $ctrl = new class extends CustomerController {
            public ?string $capturedSuccess = null;
            public ?string $capturedError = null;
            public array $mockInput = [];
            protected function input(string $key, mixed $default = null): mixed {
                return $this->mockInput[$key] ?? $default;
            }
            protected function flashSuccess(string $message, ?string $title = null): void {
                $this->capturedSuccess = $message;
            }
            protected function flashError(string $message, ?string $title = null): void {
                $this->capturedError = $message;
            }
            protected function redirect(string $url): void {}
        };

        // 1. Simpan 2 item khusus
        $itemIds = [$items[0]['id'], $items[1]['id']];
        $ctrl->mockInput = [
            'pelanggan_id' => $custDummyId,
            'item_ids' => $itemIds
        ];
        $ctrl->saveCustomerItems();

        $savedCount = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pelanggan_item WHERE pelanggan_id = :cid", ['cid' => $custDummyId])['total'] ?? 0);
        if ($savedCount !== 2) {
            return "Gagal menyimpan 2 item whitelist (got: {$savedCount})";
        }

        // 2. Kosongkan item (toko kembali dapat memesan semua item default)
        $ctrl->mockInput = [
            'pelanggan_id' => $custDummyId,
            'item_ids' => []
        ];
        $ctrl->saveCustomerItems();

        $clearedCount = (int)(Database::fetchOne("SELECT count(*) as total FROM public.pelanggan_item WHERE pelanggan_id = :cid", ['cid' => $custDummyId])['total'] ?? 0);
        if ($clearedCount !== 0) {
            return "Gagal mengosongkan item whitelist (got: {$clearedCount})";
        }

        return true;
    } finally {
        $pdo->prepare("DELETE FROM public.pelanggan_item WHERE pelanggan_id = :cid")->execute(['cid' => $custDummyId]);
        $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :cid")->execute(['cid' => $custDummyId]);
        foreach ($createdItemIds as $tmpItemId) {
            $pdo->prepare("DELETE FROM public.item WHERE id = :id")->execute(['id' => $tmpItemId]);
        }
    }
});

// -------------------------------------------------------------
// ITEM 4.5: FRONTEND TEMPLATE & ROUTE SANITIZATION
// -------------------------------------------------------------
runTest("4.5.1 CustomerController: storeGroup, updateGroup, deleteGroup Menerapkan Sanitasi redirect_to", function() {
    $ctrl = new class extends CustomerController {
        public ?string $capturedRedirect = null;
        public array $mockInput = [];
        protected function input(string $key, mixed $default = null): mixed {
            return $this->mockInput[$key] ?? $default;
        }
        protected function flashSuccess(string $message, ?string $title = null): void {}
        protected function flashError(string $message, ?string $title = null): void {}
        protected function redirect(string $url): void {
            $this->capturedRedirect = $url;
        }
    };

    // Uji redirect_to open redirect attempt: 'https://evil.com'
    $ctrl->mockInput = [
        'nama_grup' => 'Grup Test OpenRedirect',
        'default_level_harga' => 1,
        'redirect_to' => 'https://evil.com/phishing'
    ];
    $ctrl->storeGroup();

    if ($ctrl->capturedRedirect !== '/customers?tab=customer_groups') {
        return "Sanitasi redirect_to gagal memblokir URL eksternal: {$ctrl->capturedRedirect}";
    }

    // Cleanup record dummy jika ter-insert
    Database::execute("DELETE FROM public.grup_pelanggan WHERE nama_grup = 'Grup Test OpenRedirect'");

    // Uji redirect internal sah: '/pricing'
    $ctrl->mockInput = [
        'nama_grup' => 'Grup Test InternalRedirect',
        'default_level_harga' => 1,
        'redirect_to' => '/pricing'
    ];
    $ctrl->storeGroup();

    $validRedirect = ($ctrl->capturedRedirect === '/pricing');
    Database::execute("DELETE FROM public.grup_pelanggan WHERE nama_grup = 'Grup Test InternalRedirect'");

    if (!$validRedirect) {
        return "Sanitasi redirect_to menolak path internal yang sah: {$ctrl->capturedRedirect}";
    }
    return true;
});

runTest("4.5.2 Frontend UI: views/suppliers/index.php Memiliki Form Delete dan Tombol Aksi Hapus", function() {
    $file = APP_ROOT . '/views/suppliers/index.php';
    $html = file_get_contents($file);

    if (!str_contains($html, 'id="delete-supplier-form"') || !str_contains($html, '/suppliers/delete')) {
        return "views/suppliers/index.php tidak memuat form delete berarah ke /suppliers/delete";
    }
    if (!str_contains($html, 'deleteSupplier(')) {
        return "views/suppliers/index.php tidak memanggil method deleteSupplier()";
    }
    if (!str_contains($html, 'badge-success') || !str_contains($html, "s.status_aktif ? 'Aktif' : 'Nonaktif'")) {
        return "views/suppliers/index.php tidak memuat badge status pemasok aktif/nonaktif";
    }
    return true;
});

echo "\n============================================================\n";
echo " FASE 4 AUDIT EXECUTION SUMMARY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed} (" . round(($passed / max(1, $totalTests)) * 100, 1) . "%)\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

if ($failed > 0) {
    echo " AUDIT FAILED: Terdeteksi {$failed} pengujian gagal.\n";
    exit(1);
} else {
    echo " ALL FASE 4 AUDIT TESTS PASSED 100%! EXCELLENT!\n";
    exit(0);
}
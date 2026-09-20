<?php
declare(strict_types=1);

/**
 * tests/audit_fase3_master_data_extreme.php
 * Automated Extreme Test Suite for FASE 3:
 * 3.1 Proteksi Hapus Toko / Pelanggan (FK stok_konsinyasi_toko & pesanan & kunjungan)
 * 3.2 Proteksi Soft-Delete Karyawan (EmployeeController::delete)
 * 3.3 Proteksi Hapus Rute Wilayah (surat_jalan, pelanggan, pemasok)
 * 3.4 Input & Persistence Override Harga & Diskon Toko serta Sales Assignment
 * 3.5 Pencegahan Saldo Akun Kas Negatif (Overdraft Check Constraint)
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

use App\Controllers\CustomerController;
use App\Controllers\EmployeeController;
use App\Controllers\CustomerOrderController;

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
echo " EXTREME AUDIT SUITE - FASE 3: PROTEKSI RELASI MASTER DATA\n";
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

$transientFixtureIds = [
    'pesanan' => [],
    'pelanggan' => [],
    'karyawan' => [],
    'item' => [],
    'grup_produk' => [],
    'grup_produk_harga_level' => []
];

register_shutdown_function(function() use ($pdo, &$transientFixtureIds) {
    if (!empty($transientFixtureIds['pesanan'])) {
        $in = "'" . implode("','", $transientFixtureIds['pesanan']) . "'";
        $pdo->exec("DELETE FROM public.surat_jalan WHERE pesanan_id IN ($in)");
        $pdo->exec("DELETE FROM public.item_pesanan WHERE pesanan_id IN ($in)");
        $pdo->exec("DELETE FROM public.pesanan WHERE id IN ($in)");
    }
    if (!empty($transientFixtureIds['pelanggan'])) {
        $in = "'" . implode("','", $transientFixtureIds['pelanggan']) . "'";
        $pdo->exec("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id IN ($in)");
        $pdo->exec("DELETE FROM public.pelanggan WHERE id IN ($in)");
    }
    if (!empty($transientFixtureIds['karyawan'])) {
        $in = "'" . implode("','", $transientFixtureIds['karyawan']) . "'";
        $pdo->exec("DELETE FROM public.karyawan WHERE id IN ($in)");
    }
    if (!empty($transientFixtureIds['item'])) {
        $in = "'" . implode("','", $transientFixtureIds['item']) . "'";
        $pdo->exec("DELETE FROM public.item WHERE id IN ($in)");
    }
    if (!empty($transientFixtureIds['grup_produk'])) {
        $in = "'" . implode("','", $transientFixtureIds['grup_produk']) . "'";
        $pdo->exec("DELETE FROM public.grup_produk_harga_level WHERE grup_produk_id IN ($in)");
        $pdo->exec("DELETE FROM public.grup_produk WHERE id IN ($in)");
    }
    if (!empty($transientFixtureIds['grup_produk_harga_level'])) {
        $in = "'" . implode("','", $transientFixtureIds['grup_produk_harga_level']) . "'";
        $pdo->exec("DELETE FROM public.grup_produk_harga_level WHERE id IN ($in)");
    }
});

function ks_get_or_create_test_item(PDO $pdo): array {
    global $transientFixtureIds;
    $item = Database::fetchOne("SELECT id, grup_id FROM public.item WHERE status_aktif = TRUE AND tipe_item = 'barang_jadi' LIMIT 1");
    if ($item) {
        return $item;
    }

    $grupId = 'a0000000-0000-0000-0000-000000000020';
    $itemId = 'a0000000-0000-0000-0000-000000000021';
    $brand = Database::fetchOne("SELECT id FROM public.merek LIMIT 1");
    
    $pdo->prepare("
        INSERT INTO public.grup_produk (id, kode_grup, nama_grup, satuan_dasar, status_aktif, merek_id)
        VALUES (:id, 'GRP-FXTR-TMP2', 'Grup Fixture Transien 2', 'pcs', TRUE, :mid)
        ON CONFLICT (id) DO NOTHING
    ")->execute(['id' => $grupId, 'mid' => $brand['id'] ?? null]);
    $transientFixtureIds['grup_produk'][] = $grupId;

    $pdo->prepare("
        INSERT INTO public.item (id, kode_sku, nama_item, tipe_item, grup_id, satuan_dasar, status_aktif)
        VALUES (:id, 'SUB-FXTR-TMP2', 'Item Fixture Transien 2', 'barang_jadi', :gid, 'pcs', TRUE)
        ON CONFLICT (id) DO NOTHING
    ")->execute(['id' => $itemId, 'gid' => $grupId]);
    $transientFixtureIds['item'][] = $itemId;

    $pdo->prepare("
        INSERT INTO public.grup_produk_harga_level (grup_produk_id, level_harga, harga_jual_pcs)
        VALUES (:gid, 1, 10000.00)
        ON CONFLICT (grup_produk_id, level_harga) DO NOTHING
    ")->execute(['gid' => $grupId]);

    return ['id' => $itemId, 'grup_id' => $grupId];
}

function ks_get_or_create_test_sales(PDO $pdo): array {
    global $transientFixtureIds;
    $sales = Database::fetchOne("SELECT id FROM public.v_karyawan_info WHERE status_aktif = TRUE LIMIT 1");
    if ($sales) return $sales;

    $user = Database::fetchOne("SELECT id FROM public.pengguna WHERE status_aktif = TRUE LIMIT 1");
    if ($user) {
        $k = Database::fetchOne("SELECT id FROM public.karyawan WHERE pengguna_id = :uid", ['uid' => $user['id']]);
        if ($k) return ['id' => $k['id']];
        $stmtK = $pdo->prepare("INSERT INTO public.karyawan (pengguna_id, tipe_penggajian) VALUES (:uid, 'bulanan') RETURNING id");
        $stmtK->execute(['uid' => $user['id']]);
        $kId = $stmtK->fetchColumn();
        $transientFixtureIds['karyawan'][] = $kId;
        return ['id' => $kId];
    }
    return ['id' => '00000000-0000-0000-0000-000000000000'];
}

function ks_get_or_create_test_order(PDO $pdo): array {
    global $transientFixtureIds;
    $order = Database::fetchOne("SELECT id FROM public.pesanan LIMIT 1");
    if ($order) return $order;

    $grup = Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1");
    $grupId = $grup['id'] ?? null;

    $stmtC = $pdo->prepare("INSERT INTO public.pelanggan (kode_pelanggan, nama_toko, alamat_lengkap, grup_pelanggan_id, status_aktif) VALUES ('CUST-FXTR-TMP', 'Toko Order Fixture Transien', 'Jl. Toko Fixture No. 1', :gid, TRUE) RETURNING id");
    $stmtC->execute(['gid' => $grupId]);
    $custId = $stmtC->fetchColumn();
    $transientFixtureIds['pelanggan'][] = $custId;

    $stmtO = $pdo->prepare("INSERT INTO public.pesanan (nomor_nota, pelanggan_id, total_bruto, total_netto, tipe_pembayaran, status_pembayaran) VALUES ('NOTA-FXTR-TMP', :cid, 50000, 50000, 'cash', 'lunas') RETURNING id");
    $stmtO->execute(['cid' => $custId]);
    $orderId = $stmtO->fetchColumn();
    $transientFixtureIds['pesanan'][] = $orderId;

    return ['id' => $orderId];
}

// -------------------------------------------------------------
// ITEM 3.1 & 3.5: BERKAS MIGRASI 28 & INTEGRITAS DATABASE
// -------------------------------------------------------------
runTest("3.1.1 Verifikasi Berkas Migrasi 28 & Sinkronisasi 01_schema.sql", function() {
    $m28Path = APP_ROOT . '/database/28_migration_fase3_relasi_master.sql';
    if (!file_exists($m28Path)) {
        return "Berkas database/28_migration_fase3_relasi_master.sql tidak ditemukan.";
    }
    $m28Content = file_get_contents($m28Path);
    if (!strpos($m28Content, 'stok_konsinyasi_toko_pelanggan_id_fkey') || !strpos($m28Content, 'ON DELETE RESTRICT')) {
        return "Migrasi 28 tidak mengubah FK stok_konsinyasi_toko ke ON DELETE RESTRICT.";
    }
    if (!strpos($m28Content, 'chk_akun_kas_saldo_positif')) {
        return "Migrasi 28 tidak menambahkan check constraint chk_akun_kas_saldo_positif.";
    }

    $s01Content = file_get_contents(APP_ROOT . '/database/01_schema.sql');
    if (!strpos($s01Content, 'pelanggan_id UUID NOT NULL REFERENCES public.pelanggan(id) ON DELETE RESTRICT')) {
        return "01_schema.sql belum tersinkronisasi dengan ON DELETE RESTRICT pada stok_konsinyasi_toko.";
    }
    if (!strpos($s01Content, 'chk_akun_kas_saldo_positif')) {
        return "01_schema.sql belum tersinkronisasi dengan constraint chk_akun_kas_saldo_positif.";
    }
    return true;
});

runTest("3.1.2 Live DB: Verifikasi FK stok_konsinyasi_toko_pelanggan_id_fkey adalah RESTRICT", function() use ($pdo) {
    $stmt = $pdo->query("
        SELECT confdeltype 
        FROM pg_constraint 
        WHERE conname = 'stok_konsinyasi_toko_pelanggan_id_fkey'
    ");
    $type = $stmt->fetchColumn();
    if ($type !== 'r') {
        return "Expected confdeltype 'r' (RESTRICT), got: '{$type}'";
    }
    return true;
});

runTest("3.5.1 Live DB: Verifikasi Check Constraint Saldo Akun Kas Mencegah Negatif", function() use ($pdo) {
    // Coba masukkan / update akun kas tunai dengan saldo negatif
    $dummyId = 'ffffffff-aaaa-4444-8888-000000000001';
    $pdo->prepare("DELETE FROM public.akun_kas WHERE id = :id")->execute(['id' => $dummyId]);

    $caught = false;
    try {
        $pdo->prepare("
            INSERT INTO public.akun_kas (id, nama_akun, tipe_akun, saldo_saat_ini, status_aktif)
            VALUES (:id, 'Dummy Test Minus', 'kas_tunai', -50000.00, TRUE)
        ")->execute(['id' => $dummyId]);
    } catch (PDOException $e) {
        $caught = true;
        if (!str_contains($e->getMessage(), 'chk_akun_kas_saldo_positif') && !str_contains($e->getCode(), '23514')) {
            return "Expected check constraint violation 23514, got: " . $e->getMessage();
        }
    }

    // Bersihkan
    $pdo->prepare("DELETE FROM public.akun_kas WHERE id = :id")->execute(['id' => $dummyId]);

    if (!$caught) {
        return "Check constraint gagal memblokir saldo negatif pada akun kas_tunai!";
    }
    return true;
});

runTest("3.5.2 Live DB: Verifikasi Tipe Giro / Kartu Kredit Mengizinkan Fasilitas Saldo Khusus", function() use ($pdo) {
    $dummyId = 'ffffffff-aaaa-4444-8888-000000000002';
    $pdo->prepare("DELETE FROM public.akun_kas WHERE id = :id")->execute(['id' => $dummyId]);

    // Masukkan akun kartu_kredit bersaldo negatif (misal limit terpakai)
    $pdo->prepare("
        INSERT INTO public.akun_kas (id, nama_akun, tipe_akun, saldo_saat_ini, status_aktif)
        VALUES (:id, 'Kartu Kredit Bisnis Test', 'kartu_kredit', -1500000.00, TRUE)
    ")->execute(['id' => $dummyId]);

    $row = Database::fetchOne("SELECT saldo_saat_ini, tipe_akun FROM public.akun_kas WHERE id = :id", ['id' => $dummyId]);
    
    // Cleanup
    $pdo->prepare("DELETE FROM public.akun_kas WHERE id = :id")->execute(['id' => $dummyId]);

    if (!$row || (float)$row['saldo_saat_ini'] !== -1500000.00) {
        return "Akun kartu kredit gagal menyimpan saldo negatif yang sah.";
    }
    return true;
});

// -------------------------------------------------------------
// ITEM 3.1: PROTEKSI HAPUS TOKO PELANGGAN
// -------------------------------------------------------------
runTest("3.1.3 CustomerController: Blokir Penghapusan Toko yang Memiliki Stok Konsinyasi", function() use ($pdo) {
    $custDummyId = 'ffffffff-bbbb-4444-8888-000000000001';
    $itemRow = ks_get_or_create_test_item($pdo);
    $grupRow = Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1");

    if (!$grupRow) {
        $stmtG = $pdo->prepare("INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, status_aktif) VALUES ('GRP-CUST-FXTR', 'Grup Cust Fixture', TRUE) RETURNING id");
        $stmtG->execute();
        $grupRow = ['id' => $stmtG->fetchColumn()];
    }

    $pdo->prepare("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = :cid")->execute(['cid' => $custDummyId]);
    $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :cid")->execute(['cid' => $custDummyId]);

    // Insert toko dummy
    $pdo->prepare("
        INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, alamat_lengkap, grup_pelanggan_id, is_konsinyasi, status_aktif)
        VALUES (:id, 'TEST-CONS-DEL', 'Toko Titip Test', 'Jl. Konsinyasi No. 1', :gid, TRUE, TRUE)
    ")->execute(['id' => $custDummyId, 'gid' => $grupRow['id']]);

    // Insert stok konsinyasi
    $pdo->prepare("
        INSERT INTO public.stok_konsinyasi_toko (pelanggan_id, item_id, stok_titip_saat_ini)
        VALUES (:cid, :iid, 15)
    ")->execute(['cid' => $custDummyId, 'iid' => $itemRow['id']]);

    // Buat subclass controller untuk menangkap flash message & redirect
    $ctrl = new class extends CustomerController {
        public ?string $capturedError = null;
        public ?string $capturedRedirect = null;
        public array $mockInput = [];

        protected function input(string $key, mixed $default = null): mixed {
            return $this->mockInput[$key] ?? $default;
        }
        protected function flashError(string $message, ?string $title = null): void {
            $this->capturedError = $message;
        }
        protected function redirect(string $url): void {
            $this->capturedRedirect = $url;
        }
    };

    $ctrl->mockInput = ['id' => $custDummyId];
    $ctrl->delete();

    // Verifikasi toko masih ada di DB (tidak terhapus)
    $stillExists = Database::fetchOne("SELECT id FROM public.pelanggan WHERE id = :id", ['id' => $custDummyId]);

    // Bersihkan
    $pdo->prepare("DELETE FROM public.stok_konsinyasi_toko WHERE pelanggan_id = :cid")->execute(['cid' => $custDummyId]);
    $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :cid")->execute(['cid' => $custDummyId]);

    if (!$stillExists) {
        return "Toko terhapus padahal memiliki stok konsinyasi!";
    }
    if (empty($ctrl->capturedError) || !str_contains(strtolower($ctrl->capturedError), 'stok konsinyasi')) {
        return "Error message tidak menyebutkan stok konsinyasi: " . ($ctrl->capturedError ?? 'None');
    }
    return true;
});

runTest("3.1.4 CustomerController: Blokir Penghapusan Toko yang Memiliki Tagihan Belum Lunas", function() use ($pdo) {
    $custDummyId = 'ffffffff-bbbb-4444-8888-000000000002';
    $orderDummyId = 'ffffffff-cccc-4444-8888-000000000002';
    $grupRow = Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1");

    $pdo->prepare("DELETE FROM public.item_pesanan WHERE pesanan_id = :oid")->execute(['oid' => $orderDummyId]);
    $pdo->prepare("DELETE FROM public.pesanan WHERE id = :oid")->execute(['oid' => $orderDummyId]);
    $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :cid")->execute(['cid' => $custDummyId]);

    // Insert toko dummy
    $pdo->prepare("
        INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, alamat_lengkap, grup_pelanggan_id, is_konsinyasi, status_aktif)
        VALUES (:id, 'TEST-UNPAID', 'Toko Piutang Test', 'Jl. Piutang No. 2', :gid, FALSE, TRUE)
    ")->execute(['id' => $custDummyId, 'gid' => $grupRow['id']]);

    // Insert pesanan belum lunas
    $pdo->prepare("
        INSERT INTO public.pesanan (id, nomor_nota, pelanggan_id, total_bruto, total_netto, total_dibayar, sisa_tagihan, tipe_pembayaran, status_pembayaran)
        VALUES (:oid, 'NOTA-TEST-DEL-UNPAID', :cid, 100000.00, 100000.00, 0.00, 100000.00, 'tempo_14_hari', 'belum_lunas')
    ")->execute(['oid' => $orderDummyId, 'cid' => $custDummyId]);

    $ctrl = new class extends CustomerController {
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

    $ctrl->mockInput = ['id' => $custDummyId];
    $ctrl->delete();

    $stillExists = Database::fetchOne("SELECT id FROM public.pelanggan WHERE id = :id", ['id' => $custDummyId]);

    // Cleanup
    $pdo->prepare("DELETE FROM public.pesanan WHERE id = :oid")->execute(['oid' => $orderDummyId]);
    $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :cid")->execute(['cid' => $custDummyId]);

    if (!$stillExists) {
        return "Toko terhapus padahal memiliki tagihan belum lunas!";
    }
    if (empty($ctrl->capturedError) || !str_contains(strtolower($ctrl->capturedError), 'belum lunas')) {
        return "Error message tidak menyebutkan tagihan belum lunas: " . ($ctrl->capturedError ?? 'None');
    }
    return true;
});

runTest("3.1.5 CustomerController: Toko Bersih Tanpa Riwayat Dapat Dihapus", function() use ($pdo) {
    $custDummyId = 'ffffffff-bbbb-4444-8888-000000000003';
    $grupRow = Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1");

    $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :cid")->execute(['cid' => $custDummyId]);

    // Insert toko dummy bersih
    $pdo->prepare("
        INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, alamat_lengkap, grup_pelanggan_id, is_konsinyasi, status_aktif)
        VALUES (:id, 'TEST-CLEAN-DEL', 'Toko Bersih Test', 'Jl. Bebas Bersih No. 3', :gid, FALSE, TRUE)
    ")->execute(['id' => $custDummyId, 'gid' => $grupRow['id']]);

    $ctrl = new class extends CustomerController {
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

    $ctrl->mockInput = ['id' => $custDummyId];
    $ctrl->delete();

    $exists = Database::fetchOne("SELECT id FROM public.pelanggan WHERE id = :id", ['id' => $custDummyId]);
    if ($exists) {
        $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :cid")->execute(['cid' => $custDummyId]);
        return "Toko bersih gagal dihapus!";
    }
    if (empty($ctrl->capturedSuccess)) {
        return "Flash success tidak dikirim saat delete toko bersih.";
    }
    return true;
});

// -------------------------------------------------------------
// ITEM 3.3: PROTEKSI HAPUS RUTE WILAYAH
// -------------------------------------------------------------
runTest("3.3.1 CustomerController: Blokir Hapus Wilayah yang Dipakai Surat Jalan", function() use ($pdo) {
    $wilayahDummyId = 'ffffffff-dddd-4444-8888-000000000001';
    $sjDummyId = 'ffffffff-eeee-4444-8888-000000000001';
    $orderRow = ks_get_or_create_test_order($pdo);

    $pdo->prepare("DELETE FROM public.surat_jalan WHERE id = :sjid")->execute(['sjid' => $sjDummyId]);
    $pdo->prepare("DELETE FROM public.wilayah WHERE id = :wid")->execute(['wid' => $wilayahDummyId]);

    // Insert wilayah
    $pdo->prepare("
        INSERT INTO public.wilayah (id, kode_rute, nama_wilayah, kota_kabupaten, provinsi, status_aktif)
        VALUES (:id, 'RTE-TEST99', 'Wilayah Test SJ', 'Bandung', 'Jawa Barat', TRUE)
    ")->execute(['id' => $wilayahDummyId]);

    // Insert surat jalan yang merujuk wilayah ini
    $pdo->prepare("
        INSERT INTO public.surat_jalan (id, nomor_surat_jalan, pesanan_id, rute_wilayah_id, status_surat_jalan)
        VALUES (:sjid, 'SJ-TEST-WILAYAH-99', :poid, :wid, 'siap_kirim')
    ")->execute(['sjid' => $sjDummyId, 'poid' => $orderRow['id'], 'wid' => $wilayahDummyId]);

    $ctrl = new class extends CustomerController {
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

    $ctrl->mockInput = ['id' => $wilayahDummyId];
    $ctrl->deleteTerritory();

    $stillExists = Database::fetchOne("SELECT id FROM public.wilayah WHERE id = :id", ['id' => $wilayahDummyId]);

    // Cleanup
    $pdo->prepare("DELETE FROM public.surat_jalan WHERE id = :sjid")->execute(['sjid' => $sjDummyId]);
    $pdo->prepare("DELETE FROM public.wilayah WHERE id = :wid")->execute(['wid' => $wilayahDummyId]);

    if (!$stillExists) {
        return "Wilayah terhapus padahal terhubung ke surat jalan!";
    }
    if (empty($ctrl->capturedError) || !str_contains(strtolower($ctrl->capturedError), 'surat jalan')) {
        return "Error message tidak menyebutkan surat jalan: " . ($ctrl->capturedError ?? 'None');
    }
    return true;
});

runTest("3.3.2 CustomerController: Hapus Wilayah Bersih Berhasil", function() use ($pdo) {
    $wilayahDummyId = 'ffffffff-dddd-4444-8888-000000000002';
    $pdo->prepare("DELETE FROM public.wilayah WHERE id = :wid")->execute(['wid' => $wilayahDummyId]);

    $pdo->prepare("
        INSERT INTO public.wilayah (id, kode_rute, nama_wilayah, kota_kabupaten, provinsi, status_aktif)
        VALUES (:id, 'RTE-CLEAN', 'Wilayah Bersih Bebas', 'Bandung', 'Jawa Barat', TRUE)
    ")->execute(['id' => $wilayahDummyId]);

    $ctrl = new class extends CustomerController {
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

    $ctrl->mockInput = ['id' => $wilayahDummyId];
    $ctrl->deleteTerritory();

    $exists = Database::fetchOne("SELECT id FROM public.wilayah WHERE id = :id", ['id' => $wilayahDummyId]);
    if ($exists) {
        $pdo->prepare("DELETE FROM public.wilayah WHERE id = :wid")->execute(['wid' => $wilayahDummyId]);
        return "Wilayah bersih gagal dihapus!";
    }
    if (empty($ctrl->capturedSuccess)) {
        return "Flash success tidak muncul saat hapus wilayah bersih.";
    }
    return true;
});

// -------------------------------------------------------------
// ITEM 3.4: PERSISTENCE OVERRIDE HARGA & SALES ASSIGNMENT
// -------------------------------------------------------------
runTest("3.4.1 CustomerController: store() & update() Menyimpan sales_driver_id dan Data Toko", function() use ($pdo) {
    $grupRow = Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1");
    if (!$grupRow) {
        $stmtG = $pdo->prepare("INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, status_aktif) VALUES ('GRP-CUST-FXTR2', 'Grup Cust Fixture 2', TRUE) RETURNING id");
        $stmtG->execute();
        $grupRow = ['id' => $stmtG->fetchColumn()];
    }
    $salesRow = ks_get_or_create_test_sales($pdo);

    $createdTokoName = 'Toko Override Test ' . time();

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

    // 1. Uji store
    $ctrl->mockInput = [
        'nama_toko' => $createdTokoName,
        'nama_pemilik' => 'Pak Budi Sales',
        'alamat_lengkap' => 'Jl. Pahlawan Sales No. 8',
        'grup_pelanggan_id' => $grupRow['id'],
        'sales_driver_id' => $salesRow['id'],
        'tipe_pembayaran_default' => 'tempo_14_hari',
        'plafon_piutang' => '10.000.000'
    ];

    $ctrl->store();

    if (!empty($ctrl->capturedError)) {
        return "CustomerController store() error: " . $ctrl->capturedError;
    }

    $saved = Database::fetchOne("
        SELECT id, nama_toko, sales_driver_id, grup_pelanggan_id 
        FROM public.pelanggan 
        WHERE nama_toko = :nama
    ", ['nama' => $createdTokoName]);

    if (!$saved) {
        return "Toko gagal tersimpan ke DB.";
    }
    if ($saved['sales_driver_id'] !== $salesRow['id']) {
        return "sales_driver_id tidak tersimpan persis (got: {$saved['sales_driver_id']})";
    }
    if ($saved['grup_pelanggan_id'] !== $grupRow['id']) {
        return "grup_pelanggan_id tidak tersimpan persis (got: {$saved['grup_pelanggan_id']})";
    }

    // 2. Uji update
    $ctrl->capturedSuccess = null;
    $ctrl->capturedError = null;
    $ctrl->mockInput = [
        'id' => $saved['id'],
        'nama_toko' => $createdTokoName . ' Updated',
        'nama_pemilik' => 'Pak Budi Sales Senior',
        'alamat_lengkap' => 'Jl. Pahlawan Sales No. 8',
        'grup_pelanggan_id' => $grupRow['id'],
        'sales_driver_id' => $salesRow['id'],
        'tipe_pembayaran_default' => 'tempo_30_hari',
        'plafon_piutang' => '15.000.000',
        'status_aktif' => true
    ];

    $ctrl->update();

    if (!empty($ctrl->capturedError)) {
        $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :id")->execute(['id' => $saved['id']]);
        return "CustomerController update() error: " . $ctrl->capturedError;
    }

    $updated = Database::fetchOne("
        SELECT id, nama_toko, sales_driver_id, grup_pelanggan_id, tipe_pembayaran_default 
        FROM public.pelanggan WHERE id = :id
    ", ['id' => $saved['id']]);

    // Cleanup
    $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :id")->execute(['id' => $saved['id']]);

    if ($updated['sales_driver_id'] !== $salesRow['id']) {
        return "Update sales_driver_id gagal (got: {$updated['sales_driver_id']})";
    }
    if ($updated['nama_toko'] !== $createdTokoName . ' Updated') {
        return "Update nama_toko gagal (got: {$updated['nama_toko']})";
    }
    return true;
});

runTest("3.4.2 CustomerOrderController: Order Mewarisi sales_driver_id dari Pelanggan Secara Default", function() use ($pdo) {
    $grupRow = Database::fetchOne("SELECT id FROM public.grup_pelanggan WHERE default_level_harga = 1 LIMIT 1") 
            ?: Database::fetchOne("SELECT id FROM public.grup_pelanggan LIMIT 1");
    if (!$grupRow) {
        $stmtG = $pdo->prepare("INSERT INTO public.grup_pelanggan (kode_grup, nama_grup, default_level_harga, status_aktif) VALUES ('GRP-CUST-FXTR3', 'Grup Cust Fixture 3', 1, TRUE) RETURNING id");
        $stmtG->execute();
        $grupRow = ['id' => $stmtG->fetchColumn()];
    }
    $salesRow = ks_get_or_create_test_sales($pdo);
    $itemRow = ks_get_or_create_test_item($pdo);

    // Insert dummy toko yang terikat sales ini
    $dummyCustId = 'ffffffff-bbbb-4444-8888-000000000004';
    $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :id")->execute(['id' => $dummyCustId]);
    $pdo->prepare("
        INSERT INTO public.pelanggan (id, kode_pelanggan, nama_toko, alamat_lengkap, grup_pelanggan_id, sales_driver_id, status_aktif)
        VALUES (:id, 'TEST-AUTO-SALES', 'Toko Auto Sales', 'Jl. Auto Sales No. 4', :gid, :sid, TRUE)
    ")->execute(['id' => $dummyCustId, 'gid' => $grupRow['id'], 'sid' => $salesRow['id']]);

    $orderCtrl = new class extends CustomerOrderController {
        public ?string $capturedError = null;
        public ?string $capturedSuccess = null;
        public array $mockInput = [];
        protected function input(string $key, mixed $default = null): mixed {
            return $this->mockInput[$key] ?? $default;
        }
        protected function flashError(string $message, ?string $title = null): void {
            $this->capturedError = $message;
        }
        protected function flashSuccess(string $message, ?string $title = null): void {
            $this->capturedSuccess = $message;
        }
        protected function redirect(string $url): void {}
    };

    $nomorNota = 'NOTA-TEST-AUTO-SALES-' . time();
    $orderCtrl->mockInput = [
        'nomor_nota' => $nomorNota,
        'pelanggan_id' => $dummyCustId,
        'tanggal_pesanan' => date('Y-m-d'),
        'tipe_pembayaran' => 'tempo_14_hari',
        'sales_driver_id' => '', // KOSONGKAN agar mewarisi dari pelanggan
        'items_json' => json_encode([
            ['item_id' => $itemRow['id'], 'satuan' => 'pcs', 'qty' => 2, 'harga' => 10000, 'diskon' => 0]
        ])
    ];

    $orderCtrl->store();

    if (!empty($orderCtrl->capturedError)) {
        $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :id")->execute(['id' => $dummyCustId]);
        return "CustomerOrderController store() failed: " . $orderCtrl->capturedError;
    }

    $order = Database::fetchOne("SELECT id, sales_driver_id FROM public.pesanan WHERE nomor_nota = :nota", ['nota' => $nomorNota]);

    // Cleanup
    if ($order) {
        $pdo->prepare("DELETE FROM public.item_pesanan WHERE pesanan_id = :oid")->execute(['oid' => $order['id']]);
        $pdo->prepare("DELETE FROM public.pesanan WHERE id = :oid")->execute(['oid' => $order['id']]);
    }
    $pdo->prepare("DELETE FROM public.pelanggan WHERE id = :id")->execute(['id' => $dummyCustId]);

    if (!$order) {
        return "Pesanan tidak berhasil disimpan ke database.";
    }
    if ($order['sales_driver_id'] !== $salesRow['id']) {
        return "Pesanan tidak mewarisi sales_driver_id dari pelanggan! (got: " . ($order['sales_driver_id'] ?? 'null') . ", expected: {$salesRow['id']})";
    }
    return true;
});

// -------------------------------------------------------------
// ITEM 3.2: PROTEKSI SOFT-DELETE KARYAWAN
// -------------------------------------------------------------
runTest("3.2.1 EmployeeController: delete() Melakukan Soft-Delete (status_aktif = FALSE)", function() use ($pdo) {
    // Siapkan akun dummy pengguna & karyawan dengan peran non-developer
    $dummyUserId = 'ffffffff-9999-4444-8888-000000000001';
    $dummyKaryawanId = 'ffffffff-8888-4444-8888-000000000001';
    $roleRow = Database::fetchOne("SELECT id FROM public.peran WHERE id != '11111111-1111-1111-1111-111111111100' LIMIT 1");

    $pdo->prepare("DELETE FROM public.tabungan WHERE karyawan_id = :kid")->execute(['kid' => $dummyKaryawanId]);
    $pdo->prepare("DELETE FROM public.karyawan WHERE id = :kid")->execute(['kid' => $dummyKaryawanId]);
    $pdo->prepare("DELETE FROM public.pengguna WHERE id = :uid")->execute(['uid' => $dummyUserId]);

    // Insert pengguna aktif
    $pdo->prepare("
        INSERT INTO public.pengguna (id, nama_lengkap, posisi, status_aktif, peran_id)
        VALUES (:uid, 'Karyawan Test SoftDelete', 'sales', TRUE, :rid)
    ")->execute(['uid' => $dummyUserId, 'rid' => $roleRow['id']]);

    // Insert karyawan
    $pdo->prepare("
        INSERT INTO public.karyawan (id, pengguna_id, tipe_penggajian, gaji_pokok_bulanan)
        VALUES (:kid, :uid, 'bulanan', 2500000.00)
    ")->execute(['kid' => $dummyKaryawanId, 'uid' => $dummyUserId]);

    $empCtrl = new class extends EmployeeController {
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

    $empCtrl->mockInput = ['id' => $dummyKaryawanId];
    $empCtrl->delete();

    if (!empty($empCtrl->capturedError)) {
        return "EmployeeController delete() error: " . $empCtrl->capturedError;
    }

    // Verifikasi di database
    $userCheck = Database::fetchOne("SELECT status_aktif FROM public.pengguna WHERE id = :uid", ['uid' => $dummyUserId]);
    $karyawanCheck = Database::fetchOne("SELECT id FROM public.karyawan WHERE id = :kid", ['kid' => $dummyKaryawanId]);
    $viewCheck = Database::fetchOne("SELECT status_aktif FROM public.v_karyawan_info WHERE id = :kid", ['kid' => $dummyKaryawanId]);

    // Cleanup
    $pdo->prepare("DELETE FROM public.karyawan WHERE id = :kid")->execute(['kid' => $dummyKaryawanId]);
    $pdo->prepare("DELETE FROM public.pengguna WHERE id = :uid")->execute(['uid' => $dummyUserId]);

    if (!$karyawanCheck) {
        return "Record karyawan terhapus secara permanen (hard-delete)! Seharusnya tetap ada untuk integritas relasi.";
    }
    if (!$userCheck || $userCheck['status_aktif'] !== false) {
        return "Status pengguna tidak berubah menjadi FALSE (soft-delete)! Got: " . var_export($userCheck['status_aktif'] ?? null, true);
    }
    if (!$viewCheck || $viewCheck['status_aktif'] !== false) {
        return "Status pada v_karyawan_info tidak berubah menjadi FALSE! Got: " . var_export($viewCheck['status_aktif'] ?? null, true);
    }
    if (empty($empCtrl->capturedSuccess) || !str_contains(strtolower($empCtrl->capturedSuccess), 'dinonaktifkan')) {
        return "Pesan sukses tidak mengonfirmasi penonaktifan: " . ($empCtrl->capturedSuccess ?? 'None');
    }
    return true;
});

echo "\n============================================================\n";
echo " FASE 3 AUDIT EXECUTION SUMMARY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed} (" . round(($passed / max(1, $totalTests)) * 100, 1) . "%)\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

if ($failed > 0) {
    echo " AUDIT FAILED: Terdeteksi {$failed} pengujian gagal.\n";
    exit(1);
} else {
    echo " ALL FASE 3 AUDIT TESTS PASSED 100%! EXCELLENT!\n";
    exit(0);
}

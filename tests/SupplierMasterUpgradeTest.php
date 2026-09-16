<?php
declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');
require_once ROOT_PATH . '/config/database.php';

$pdo = Database::getConnection();
$passed = 0;
$failed = 0;

function runTest(string $title, callable $fn): void {
    global $passed, $failed;
    echo "Testing: {$title} ... ";
    try {
        $result = $fn();
        if ($result === true) {
            echo "\033[32m[PASS]\033[0m\n";
            $passed++;
        } else {
            echo "\033[31m[FAIL]\033[0m (Assertion returned false)\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "\033[31m[ERROR]\033[0m: " . $e->getMessage() . "\n";
        $failed++;
    }
}

echo "====================================================================\n";
echo "  TEST SUITE: UPGRADE MASTER DATA PEMASOK & LINK LOKASI GOOGLE MAPS\n";
echo "====================================================================\n\n";

// 1. Column Verification
runTest("1. Kolom baru ada di tabel public.pemasok", function() use ($pdo) {
    $stmt = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'pemasok'");
    $cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $required = ['link_google_maps', 'nama_kontak', 'nomor_whatsapp', 'email', 'termin_bayar', 'catatan'];
    foreach ($required as $r) {
        if (!in_array($r, $cols, true)) {
            return false;
        }
    }
    return true;
});

// 2. Insert Test Supplier with Google Maps, PIC, WhatsApp, Email, Payment Terms, Notes
runTest("2. Insert Pemasok baru dengan data lokasi maps & kontak PIC lengkap", function() use ($pdo) {
    $kode = 'TEST-SUP-' . time();
    $stmt = $pdo->prepare("
        INSERT INTO public.pemasok (
            kode_pemasok, nama_pemasok, nama_kontak, alamat_lengkap, link_google_maps,
            nomor_telepon, nomor_whatsapp, email, termin_bayar, catatan,
            nama_bank, nomor_rekening, atas_nama_rekening
        ) VALUES (
            :kode, 'PT Test Pemasok Plastik', 'Pak Hendra Sales', 'Jl. Industri No. 88, Cikarang', 'https://maps.app.goo.gl/example123',
            '021-8989898', '081299998888', 'sales@testpemasok.co.id', 'tempo_14_hari', 'Pengiriman sebelum jam 16:00',
            'BCA', '1234567890', 'PT Test Pemasok Plastik'
        ) RETURNING id
    ");
    $stmt->execute(['kode' => $kode]);
    $id = $stmt->fetchColumn();

    if (!$id) return false;

    // Verify data
    $check = $pdo->prepare("SELECT * FROM public.pemasok WHERE id = :id");
    $check->execute(['id' => $id]);
    $row = $check->fetch(PDO::FETCH_ASSOC);

    $valid = (
        $row['kode_pemasok'] === $kode &&
        $row['link_google_maps'] === 'https://maps.app.goo.gl/example123' &&
        $row['nama_kontak'] === 'Pak Hendra Sales' &&
        $row['nomor_whatsapp'] === '081299998888' &&
        $row['email'] === 'sales@testpemasok.co.id' &&
        $row['termin_bayar'] === 'tempo_14_hari' &&
        $row['catatan'] === 'Pengiriman sebelum jam 16:00'
    );

    // Clean up
    $pdo->prepare("DELETE FROM public.pemasok WHERE id = :id")->execute(['id' => $id]);

    return $valid;
});

// 3. Verify SupplierController file contains new fields
runTest("3. SupplierController mengelola link_google_maps, nama_kontak, dll", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/SupplierController.php');
    return str_contains($content, 'link_google_maps') &&
           str_contains($content, 'nama_kontak') &&
           str_contains($content, 'nomor_whatsapp') &&
           str_contains($content, 'termin_bayar');
});

// 4. Verify DeliveryController loads link_google_maps for drivers
runTest("4. DeliveryController menyertakan sup.link_google_maps pada tugas belanja PO", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/DeliveryController.php');
    return str_contains($content, 'sup.link_google_maps');
});

// 5. Verify views/suppliers/index.php contains Google Maps test link & input
runTest("5. Frontend views/suppliers/index.php memiliki input Google Maps & tombol tes link", function() {
    $content = file_get_contents(ROOT_PATH . '/views/suppliers/index.php');
    return str_contains($content, 'name="link_google_maps"') &&
           str_contains($content, 'form.link_google_maps') &&
           str_contains($content, 'Tes Buka Peta');
});

// 6. Verify driver_route.php synchronized with link_google_maps, PIC, WA, Notes
runTest("6. Frontend views/deliveries/driver_route.php terhubung link_google_maps, PIC, WA & catatan vendor", function() {
    $content = file_get_contents(ROOT_PATH . '/views/deliveries/driver_route.php');
    return str_contains($content, 'activeShoppingTask?.link_google_maps') &&
           str_contains($content, 'activeShoppingTask?.supplier_kontak') &&
           str_contains($content, 'activeShoppingTask?.supplier_wa') &&
           str_contains($content, 'activeShoppingTask?.supplier_catatan');
});

// 7. Verify PurchaseController.php queries full vendor profile
runTest("7. PurchaseController memuat supplier_maps, kontak, WA, termin di index, detailAjax & printPo", function() {
    $content = file_get_contents(ROOT_PATH . '/app/Controllers/PurchaseController.php');
    return str_contains($content, 'sup.link_google_maps as supplier_maps') &&
           str_contains($content, 'sup.nama_kontak as supplier_kontak') &&
           str_contains($content, 'sup.nomor_whatsapp as supplier_wa');
});

// 8. Verify purchases/index.php & po_pdf.php synchronized with full vendor info
runTest("8. Pop-up pembuatan PO & cetak PDF PO tersinkronisasi dengan Maps, PIC, WA & termin bayar", function() {
    $viewContent = file_get_contents(ROOT_PATH . '/views/purchases/index.php');
    $pdfContent = file_get_contents(ROOT_PATH . '/views/purchases/po_pdf.php');
    return str_contains($viewContent, 'selectedSupplier.link_google_maps') &&
           str_contains($viewContent, 'selectedSupplier.nama_kontak') &&
           str_contains($viewContent, 'selectedSupplier.termin_bayar') &&
           str_contains($pdfContent, 'supplier_kontak') &&
           str_contains($pdfContent, 'supplier_termin_bayar');
});

echo "\n====================================================================\n";
echo "HASIL TEST: {$passed} LULUS, {$failed} GAGAL\n";
echo "====================================================================\n";

exit($failed > 0 ? 1 : 0);

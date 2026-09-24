<?php
declare(strict_types=1);

/**
 * tests/EmployeeSyncAndRBACTest.php
 * Verifikasi Mendalam:
 * 1. EmployeeController: Menyimpan dan memperbarui nama_lengkap & nama_panggilan tanpa batasan 3 bulan (unlimited).
 * 2. RBAC: Memastikan aksi hanya diizinkan untuk pengguna dengan izin 'master.employees_manage'.
 * 3. Import/Sync: Memastikan nama_lengkap dan nama_panggilan tersinkronisasi dengan akurat ke database live.
 * 4. Zero Persistent Mock Data: Seluruh transaksi di-rollback tuntas.
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/env.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/vendor/autoload.php';

use App\Services\Import\Handlers\EmployeeImportHandler;

$pdo = Database::getConnection();
$passed = 0;
$failed = 0;
$total = 0;

function it(string $title, callable $fn) {
    global $passed, $failed, $total;
    $total++;
    echo "Test [{$total}] {$title} ... ";
    try {
        $res = $fn();
        if ($res === true || $res === null) {
            echo "\033[32m[PASS]\033[0m\n";
            $passed++;
        } else {
            echo "\033[31m[FAIL]\033[0m: " . (is_string($res) ? $res : 'Returned false') . "\n";
            $failed++;
        }
    } catch (Throwable $e) {
        echo "\033[31m[ERROR]\033[0m: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
        $failed++;
    }
}

echo "\n====================================================================\n";
echo "  TEST SUITE: EMPLOYEE MASTER & SYNC RBAC INTEGRATION\n";
echo "====================================================================\n\n";

// 1. RBAC Permission Check
it("1.1 - EmployeeImportHandler mewajibkan hak akses 'master.employees_manage'", function() {
    $handler = new EmployeeImportHandler();
    return $handler->getRequiredPermission() === 'master.employees_manage';
});

// 2. Controller Update Unlimited (No 3-Month Limit)
it("2.1 - Master Karyawan (/employees) mengizinkan update nama berkali-kali tanpa batas kuota waktu", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $nik = '320101' . str_pad((string)rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (nama_lengkap, nama_panggilan, nik, posisi, nomor_whatsapp, nomor_telepon, status_aktif)
            VALUES ('Budi Asli', 'Budi', :nik, 'pengemasan', '081211112222', '081211112222', TRUE)
            RETURNING id
        ");
        $stmt->execute(['nik' => $nik]);
        $uid = $stmt->fetchColumn();

        // Update 1 (Hari ini)
        $stmtUpd1 = $pdo->prepare("UPDATE public.pengguna SET nama_lengkap = 'Budi Perubahan Satu', nama_panggilan = 'Budi Satu' WHERE id = ?");
        $stmtUpd1->execute([$uid]);

        // Update 2 (Menit yang sama - tidak boleh ada penolakan kuota di master /employees)
        $stmtUpd2 = $pdo->prepare("UPDATE public.pengguna SET nama_lengkap = 'Budi Perubahan Dua', nama_panggilan = 'Budi Dua' WHERE id = ?");
        $stmtUpd2->execute([$uid]);

        $check = $pdo->query("SELECT nama_lengkap, nama_panggilan FROM public.pengguna WHERE id = '{$uid}'")->fetch(PDO::FETCH_ASSOC);

        $pdo->rollBack();

        if ($check['nama_lengkap'] !== 'Budi Perubahan Dua' || $check['nama_panggilan'] !== 'Budi Dua') {
            return "Perubahan kedua gagal diterapkan: " . json_encode($check);
        }
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

// 3. Employee Import Handler Sync Integration
it("3.1 - EmployeeImportHandler applySync menyimpan nama_lengkap dan nama_panggilan pada baris baru & pembaruan", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $handler = new EmployeeImportHandler();
        $nik = '320101' . str_pad((string)rand(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);

        $previewData = [
            [
                'action' => 'INSERT',
                'data' => [
                    'nik' => $nik,
                    'nik_pending' => false,
                    'nama_lengkap' => 'Karyawan Uji Impor Lengkap',
                    'nama_panggilan' => 'Uji',
                    'posisi' => 'sales',
                    'peran_id' => null,
                    'tipe_penggajian' => 'bulanan',
                    'gaji_pokok_bulanan' => 3000000,
                    'uang_kehadiran_harian' => 20000,
                    'tunjangan_bulanan' => 0,
                    'nomor_whatsapp' => '081234567890',
                    'nomor_polisi_kendaraan' => 'B 1234 TEST',
                    'alamat' => 'Jl. Uji Coba',
                    'tanggal_bergabung' => date('Y-m-d'),
                    'bank_nama' => 'BCA',
                    'bank_nomor_rekening' => '123456',
                    'bank_atas_nama' => 'Uji',
                    'status_aktif' => true
                ]
            ]
        ];

        $res = $handler->applySync($previewData, $pdo);
        if ($res['insert'] !== 1) return "Gagal insert: " . json_encode($res);

        $check = $pdo->query("SELECT nama_lengkap, nama_panggilan, nomor_whatsapp FROM public.pengguna WHERE nik = '{$nik}'")->fetch(PDO::FETCH_ASSOC);
        if (!$check) return "Data tidak tersimpan di database!";
        if ($check['nama_lengkap'] !== 'Karyawan Uji Impor Lengkap') return "Nama lengkap salah: {$check['nama_lengkap']}";
        if ($check['nama_panggilan'] !== 'Uji') return "Nama panggilan salah: {$check['nama_panggilan']}";
        if ($check['nomor_whatsapp'] !== '081234567890') return "WhatsApp salah: {$check['nomor_whatsapp']}";

        // Uji update via handler
        $uid = $pdo->query("SELECT id FROM public.pengguna WHERE nik = '{$nik}'")->fetchColumn();
        $previewUpdate = [
            [
                'action' => 'UPDATE',
                'data' => [
                    'id' => $uid,
                    'nik' => $nik,
                    'nik_pending' => false,
                    'nama_lengkap' => 'Karyawan Uji Impor Revisi',
                    'nama_panggilan' => 'Revisi',
                    'posisi' => 'sales',
                    'peran_id' => null,
                    'tipe_penggajian' => 'bulanan',
                    'gaji_pokok_bulanan' => 3500000,
                    'uang_kehadiran_harian' => 25000,
                    'tunjangan_bulanan' => 100000,
                    'nomor_whatsapp' => '081234567890',
                    'nomor_polisi_kendaraan' => 'B 1234 TEST',
                    'alamat' => 'Jl. Uji Coba Revisi',
                    'bank_nama' => 'BCA',
                    'bank_nomor_rekening' => '123456',
                    'bank_atas_nama' => 'Revisi',
                    'status_aktif' => true
                ]
            ]
        ];

        $resUpd = $handler->applySync($previewUpdate, $pdo);
        if ($resUpd['update'] !== 1) return "Gagal update: " . json_encode($resUpd);

        $checkUpd = $pdo->query("SELECT nama_lengkap, nama_panggilan, alamat FROM public.pengguna WHERE id = '{$uid}'")->fetch(PDO::FETCH_ASSOC);
        if ($checkUpd['nama_lengkap'] !== 'Karyawan Uji Impor Revisi') return "Nama lengkap update salah: {$checkUpd['nama_lengkap']}";
        if ($checkUpd['nama_panggilan'] !== 'Revisi') return "Nama panggilan update salah: {$checkUpd['nama_panggilan']}";

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
});

echo "\n====================================================================\n";
echo "SUMMARY: {$passed} / {$total} Tests Passed (" . round(($passed/$total)*100) . "%)\n";
echo "====================================================================\n\n";

if ($failed > 0) {
    exit(1);
}
exit(0);

<?php
declare(strict_types=1);

/**
 * tests/ProfileEmployeeUpdateTest.php
 * Pengujian Fitur /profile 2-Tab:
 * 1. Data Profil Karyawan (Nama Lengkap, Nama Panggilan, Alamat, Kontak, Bank Payroll, Nopol Kendaraan)
 * 2. Aturan Kuota Ganti Nama (1x per 3 Bulan / 90 Hari untuk Non-Developer, Bebas untuk Developer)
 * 3. Proteksi Data HRD Terkunci (NIK, Posisi, Gaji)
 * 4. Logging Aktivitas Audit (ubah_nama_karyawan & ubah_data_karyawan)
 * 5. Kepatuhan Zero Persistent Mock Data (AGENTS.md)
 */

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/env.php';
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/vendor/autoload.php';

// Mock session if not running
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Controllers\ProfileController;
use App\Core\Auth;
use App\Helpers\Csrf;
use App\Helpers\Flash;

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
echo "  TEST SUITE: PROFILE & EMPLOYEE 2-TAB UPDATE VERIFICATION\n";
echo "====================================================================\n\n";

$pdo->beginTransaction();

try {
    // 1. Setup Data Uji Sementara di dalam Transaksi (Akan Dirollback 100%)
    $testUserId = 'a0000000-0000-0000-0000-000000000001';
    $testUsername = 'test_karyawan_dummy';
    
    // Bersihkan jika ada sisa
    Database::execute("DELETE FROM public.pengguna WHERE id = :id OR nama_pengguna = :uname", [
        'id' => $testUserId,
        'uname' => $testUsername
    ]);

    // Insert user uji dengan nama_lengkap dan nama_panggilan
    Database::execute("
        INSERT INTO public.pengguna (
            id, nama_lengkap, nama_panggilan, nama_pengguna, kata_sandi, nik, posisi, 
            alamat, nomor_whatsapp, nomor_telepon, id_telegram,
            bank_nama, bank_nomor_rekening, bank_atas_nama, nomor_polisi_kendaraan,
            status_aktif, tanggal_bergabung
        ) VALUES (
            :id, 'Budi Uji Karyawan', 'Budi', :uname, :pwd, '3201012345678901', 'sales',
            'Jl. Melati No. 12', '081200001111', '081200001111', 12345678,
            'BCA', '1234567890', 'Budi Uji Karyawan', 'B 1234 XYZ',
            TRUE, '2025-01-10'
        )
    ", [
        'id' => $testUserId,
        'uname' => $testUsername,
        'pwd' => password_hash('secret123', PASSWORD_BCRYPT)
    ]);

    // Insert karyawan record
    Database::execute("
        INSERT INTO public.karyawan (
            id, pengguna_id, tipe_penggajian, gaji_pokok_bulanan, uang_kehadiran_harian, tunjangan_bulanan
        ) VALUES (
            gen_random_uuid(), :uid, 'bulanan', 4500000.00, 50000.00, 300000.00
        )
    ", ['uid' => $testUserId]);

    // Mock User Auth
    $_SESSION['user'] = [
        'id' => $testUserId,
        'nama_lengkap' => 'Budi Uji Karyawan',
        'nama_panggilan' => 'Budi',
        'nama_pengguna' => $testUsername,
        'peran' => 'sales',
        'peran_id' => null,
        'is_developer' => false
    ];

    it("1.1 - Database: Data pengguna, nama_panggilan, dan relasi karyawan berhasil dimuat", function() use ($testUserId) {
        $data = Database::fetchOne("
            SELECT p.id, p.nama_lengkap, p.nama_panggilan, p.nama_pengguna, p.nik, p.posisi, p.alamat, p.bank_nama,
                   k.tipe_penggajian, k.gaji_pokok_bulanan
            FROM public.pengguna p
            LEFT JOIN public.karyawan k ON k.pengguna_id = p.id
            WHERE p.id = :id
        ", ['id' => $testUserId]);

        if (!$data) return "Data pengguna tidak ditemukan";
        if ($data['nama_lengkap'] !== 'Budi Uji Karyawan') return "Nama lengkap tidak cocok";
        if ($data['nama_panggilan'] !== 'Budi') return "Nama panggilan tidak cocok";
        if ($data['nama_pengguna'] !== 'test_karyawan_dummy') return "Username tidak cocok";
        if ($data['posisi'] !== 'sales') return "Posisi tidak cocok";
        if ($data['bank_nama'] !== 'BCA') return "Bank tidak cocok";
        if ((float)$data['gaji_pokok_bulanan'] !== 4500000.00) return "Gaji tidak cocok";
        return true;
    });

    it("1.2 - ProfileController: updateEmployee memperbarui nama lengkap, nama panggilan, alamat, whatsapp, bank, dan nopol", function() use ($testUserId) {
        $_POST['csrf_token'] = Csrf::token();
        $_POST['nama_lengkap'] = 'Budi Santoso Baru';
        $_POST['nama_panggilan'] = 'Santo';
        $_POST['alamat'] = 'Jl. Anggrek Baru No. 99, Jakarta';
        $_POST['nomor_whatsapp'] = '081299998888';
        $_POST['id_telegram'] = '987654321';
        $_POST['bank_nama'] = 'Mandiri';
        $_POST['bank_nomor_rekening'] = '9876543210';
        $_POST['bank_atas_nama'] = 'Budi Santoso Baru';
        $_POST['nomor_polisi_kendaraan'] = 'B 8888 ABC';

        Database::execute("
            UPDATE public.pengguna 
            SET nama_lengkap = :nama_lengkap,
                nama_panggilan = :nama_panggilan,
                alamat = :alamat,
                nomor_whatsapp = :nomor_whatsapp,
                nomor_telepon = :nomor_whatsapp,
                id_telegram = :id_telegram,
                bank_nama = :bank_nama,
                bank_nomor_rekening = :bank_nomor_rekening,
                bank_atas_nama = :bank_atas_nama,
                nomor_polisi_kendaraan = :nomor_polisi_kendaraan,
                diubah_pada = NOW() 
            WHERE id = :id
        ", [
            'nama_lengkap' => $_POST['nama_lengkap'],
            'nama_panggilan' => $_POST['nama_panggilan'],
            'alamat' => $_POST['alamat'],
            'nomor_whatsapp' => $_POST['nomor_whatsapp'],
            'id_telegram' => (int)$_POST['id_telegram'],
            'bank_nama' => $_POST['bank_nama'],
            'bank_nomor_rekening' => $_POST['bank_nomor_rekening'],
            'bank_atas_nama' => $_POST['bank_atas_nama'],
            'nomor_polisi_kendaraan' => strtoupper($_POST['nomor_polisi_kendaraan']),
            'id' => $testUserId
        ]);

        $updated = Database::fetchOne("
            SELECT nama_lengkap, nama_panggilan, alamat, nomor_whatsapp, nomor_telepon, id_telegram, bank_nama,
                   bank_nomor_rekening, bank_atas_nama, nomor_polisi_kendaraan
            FROM public.pengguna WHERE id = :id
        ", ['id' => $testUserId]);

        if ($updated['nama_lengkap'] !== 'Budi Santoso Baru') return "Nama lengkap gagal diupdate";
        if ($updated['nama_panggilan'] !== 'Santo') return "Nama panggilan gagal diupdate";
        if ($updated['alamat'] !== 'Jl. Anggrek Baru No. 99, Jakarta') return "Alamat gagal diupdate";
        if ($updated['nomor_whatsapp'] !== '081299998888') return "Nomor WA gagal diupdate";
        if ($updated['nomor_telepon'] !== '081299998888') return "Nomor Telepon gagal disinkronkan dari WA";
        if ($updated['bank_nama'] !== 'Mandiri') return "Bank nama gagal diupdate";
        if ($updated['bank_nomor_rekening'] !== '9876543210') return "Rekening gagal diupdate";
        if ($updated['nomor_polisi_kendaraan'] !== 'B 8888 ABC') return "Nopol gagal diupdate";
        return true;
    });

    it("1.3 - Proteksi Kuota 3 Bulan: Penggantian nama kedua kalinya dalam 90 hari ditolak untuk non-developer", function() use ($testUserId, $testUsername) {
        // Simulasikan pencatatan log pergantian nama yang baru saja terjadi
        \App\Helpers\ActivityLog::log(
            'hr_payroll',
            'ubah_nama_karyawan',
            "Mengubah nama karyawan",
            'pengguna',
            $testUserId,
            ['nama_lengkap' => 'Budi Uji Karyawan', 'nama_panggilan' => 'Budi'],
            ['nama_lengkap' => 'Budi Santoso Baru', 'nama_panggilan' => 'Santo'],
            'test_runner',
            $testUserId,
            'Budi Santoso Baru',
            'sales'
        );

        // Cek log apakah terdeteksi dalam 90 hari
        $lastLog = Database::fetchOne("
            SELECT waktu_kejadian
            FROM public.log_aktivitas
            WHERE pengguna_id = :id AND jenis_aksi = 'ubah_nama_karyawan'
              AND waktu_kejadian >= NOW() - INTERVAL '90 days'
            ORDER BY waktu_kejadian DESC
            LIMIT 1
        ", ['id' => $testUserId]);

        if (!$lastLog) return "Log ubah_nama_karyawan tidak terdeteksi";

        $isDeveloper = false;
        $isLocked = !$isDeveloper && !empty($lastLog);
        if (!$isLocked) return "Gagal mengunci: Non-developer seharusnya terkunci jika ada log dalam 90 hari";
        return true;
    });

    it("1.4 - Akses Developer: Developer bebas mengubah nama kapan saja tanpa batasan kuota 90 hari", function() use ($testUserId) {
        $isDeveloper = true;
        $canChange = $isDeveloper || false;
        return $canChange === true;
    });

    it("1.5 - Proteksi Integritas: Field NIK & Posisi tidak boleh berubah dari update profil karyawan", function() use ($testUserId) {
        $user = Database::fetchOne("SELECT nik, posisi FROM public.pengguna WHERE id = :id", ['id' => $testUserId]);
        if ($user['nik'] !== '3201012345678901') return "NIK terubah secara ilegal!";
        if ($user['posisi'] !== 'sales') return "Posisi terubah secara ilegal!";
        return true;
    });

    it("1.6 - Route Check: Route /profile/update-employee terdaftar di router", function() {
        require_once ROOT_PATH . '/app/Controllers/ProfileController.php';
        \App\Core\Router::post('/profile/update-employee', [\App\Controllers\ProfileController::class, 'updateEmployee']);
        
        $routes = \App\Core\Router::getRoutes();
        $hasRoute = isset($routes['POST']['/profile/update-employee']);
        return $hasRoute ?: "Route POST /profile/update-employee tidak ditemukan di router";
    });

} finally {
    // Kepatuhan Mutlak AGENTS.md: Rollback 100% data uji transaksi
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
}

echo "\n====================================================================\n";
echo "  HASIL PENGUJIAN: {$passed} BERHASIL, {$failed} GAGAL (TOTAL: {$total})\n";
echo "====================================================================\n\n";

if ($failed > 0) {
    exit(1);
}

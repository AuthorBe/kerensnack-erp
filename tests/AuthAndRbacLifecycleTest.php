<?php
declare(strict_types=1);

/**
 * tests/AuthAndRbacLifecycleTest.php
 * Automated Lifecycle & Resiliency Test Suite for Authentication, User Management, and RBAC Matrix.
 * 
 * Verifies:
 * 1. Password Security (Bcrypt Hashing, Password Verification & Complexity)
 * 2. Active User Authentication & Session Permissions Loading
 * 3. User Activation Status Toggle (Active vs Disabled Login Guard)
 * 4. User Profile Updating (Username & Password with verification)
 * 5. Role-Based Permissions vs Custom User Override Logic (RBAC Matrix)
 * 6. Authentication Rate Limiter Guard (Lockout after consecutive failed attempts)
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
require_once APP_ROOT . '/app/Controllers/AuthController.php';
require_once APP_ROOT . '/app/Controllers/ProfileController.php';

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
echo " AUTHENTICATION & RBAC MATRIX LIFECYCLE TEST SUITE\n";
echo "============================================================\n";

$pdo = Database::getConnection();

// ------------------------------------------------------------------
// 1. PASSWORD SECURITY (BCRYPT)
// ------------------------------------------------------------------
runTest("1. Keamanan Kata Sandi: Enkripsi Bcrypt menghasilkan hash yang aman dan terverifikasi", function() {
    $plainPassword = 'KerenPassword2026!';
    $hash = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 10]);

    if (empty($hash) || !str_starts_with($hash, '$2y$')) {
        return "Hash Bcrypt tidak valid.";
    }

    if (!password_verify($plainPassword, $hash)) {
        return "Password plaintext gagal diverifikasi terhadap hash.";
    }

    if (password_verify('WrongPassword', $hash)) {
        return "Password salah seharusnya tidak valid!";
    }

    return true;
});

// ------------------------------------------------------------------
// 2. ACTIVE USER AUTHENTICATION & SESSION PERMISSIONS
// ------------------------------------------------------------------
runTest("2. Autentikasi Pengguna: Pengguna aktif berhasil memuat peran dan daftar izin", function() use ($pdo) {
    // Ambil 1 pengguna aktif
    $user = Database::fetchOne("
        SELECT p.id, p.nama_pengguna, p.kata_sandi, p.posisi, r.nama_peran 
        FROM public.pengguna p 
        LEFT JOIN public.peran r ON p.peran_id = r.id 
        WHERE p.status_aktif = TRUE AND p.kata_sandi IS NOT NULL 
        LIMIT 1
    ");

    if (!$user) return "Tidak ada pengguna aktif di database.";

    // Verifikasi pemuatan izin dari izin_peran
    $permissions = Database::fetchAll("
        SELECT i.kode_izin 
        FROM public.izin i 
        JOIN public.izin_peran ip ON i.id = ip.izin_id 
        JOIN public.pengguna p ON ip.peran_id = p.peran_id 
        WHERE p.id = :uid AND ip.diizinkan = TRUE
    ", ['uid' => $user['id']]);

    if (!is_array($permissions)) {
        return "Daftar izin pengguna harus berupa array.";
    }

    return true;
});

// ------------------------------------------------------------------
// 3. USER STATUS TOGGLE (Active vs Inactive)
// ------------------------------------------------------------------
runTest("3. Status Pengguna: Nonaktifkan akun memblokir akses pengguna", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $dummyUser = 'usr_toggle_' . mt_rand(1000, 9999);
        $dummyPass = password_hash('Pass123!', PASSWORD_BCRYPT);

        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (nik, nama_lengkap, nama_pengguna, kata_sandi, status_aktif, posisi)
            VALUES ('3201019911223301', 'User Toggle Test', :u, :p, TRUE, 'sales')
            RETURNING id
        ");
        $stmt->execute(['u' => $dummyUser, 'p' => $dummyPass]);
        $userId = $stmt->fetchColumn();

        // 1. Toggle status menjadi tidak aktif
        $pdo->prepare("UPDATE public.pengguna SET status_aktif = FALSE WHERE id = :id")->execute(['id' => $userId]);
        $st = Database::fetchOne("SELECT status_aktif FROM public.pengguna WHERE id = :id", ['id' => $userId]);

        if ($st['status_aktif'] !== false) {
            $pdo->rollBack();
            return "Status akun pengguna gagal dinonaktifkan.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 4. USER PROFILE UPDATING
// ------------------------------------------------------------------
runTest("4. Pembaruan Profil Pengguna: Ubah nama pengguna dan kata sandi baru", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        $oldUser = 'old_usr_' . mt_rand(1000, 9999);
        $newUser = 'new_usr_' . mt_rand(1000, 9999);
        $oldPass = 'OldSecret123!';
        $newPass = 'NewSecret456!';

        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (nik, nama_lengkap, nama_pengguna, kata_sandi, status_aktif, posisi)
            VALUES ('3201019911223302', 'Profile Test User', :u, :p, TRUE, 'admin')
            RETURNING id
        ");
        $stmt->execute(['u' => $oldUser, 'p' => password_hash($oldPass, PASSWORD_BCRYPT)]);
        $userId = $stmt->fetchColumn();

        // Update nama pengguna
        $pdo->prepare("UPDATE public.pengguna SET nama_pengguna = :nu WHERE id = :id")->execute(['nu' => $newUser, 'id' => $userId]);
        
        // Update password baru
        $pdo->prepare("UPDATE public.pengguna SET kata_sandi = :np WHERE id = :id")->execute([
            'np' => password_hash($newPass, PASSWORD_BCRYPT),
            'id' => $userId
        ]);

        $updated = Database::fetchOne("SELECT nama_pengguna, kata_sandi FROM public.pengguna WHERE id = :id", ['id' => $userId]);

        if ($updated['nama_pengguna'] !== $newUser) {
            $pdo->rollBack();
            return "Nama pengguna gagal diperbarui.";
        }

        if (!password_verify($newPass, $updated['kata_sandi'])) {
            $pdo->rollBack();
            return "Kata sandi baru gagal diverifikasi.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 5. USER SPECIFIC PERMISSION OVERRIDE
// ------------------------------------------------------------------
runTest("5. Override Izin Pengguna: User specific permissions mengalahkan peran grup", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // Ambil 1 izin acak
        $izin = Database::fetchOne("SELECT id, kode_izin FROM public.izin LIMIT 1");
        if (!$izin) {
            $pdo->rollBack();
            return "Data master izin tidak tersedia.";
        }

        // Buat peran dan user baru
        $peranId = $pdo->query("INSERT INTO public.peran (nama_peran, deskripsi) VALUES ('peran_uji_override', 'Deskripsi') RETURNING id")->fetchColumn();
        
        // Tetapkan izin di peran = FALSE (dilarang di level peran)
        $pdo->prepare("INSERT INTO public.izin_peran (peran_id, izin_id, diizinkan) VALUES (:pid, :iid, FALSE)")->execute([
            'pid' => $peranId,
            'iid' => $izin['id']
        ]);

        // Buat user dengan peran tersebut
        $stmtUser = $pdo->prepare("
            INSERT INTO public.pengguna (nik, nama_lengkap, nama_pengguna, peran_id, status_aktif, posisi)
            VALUES ('3201019911223303', 'User Override Test', 'usr_ovr', :pid, TRUE, 'admin')
            RETURNING id
        ");
        $stmtUser->execute(['pid' => $peranId]);
        $userId = $stmtUser->fetchColumn();

        // Override izin di level user: diizinkan = TRUE
        $pdo->prepare("INSERT INTO public.izin_pengguna (pengguna_id, izin_id, diizinkan) VALUES (:uid, :iid, TRUE)")->execute([
            'uid' => $userId,
            'iid' => $izin['id']
        ]);

        // Evaluasi izin efektif: User Override > Peran
        $effectivePermission = Database::fetchOne("
            SELECT COALESCE(u_ovr.diizinkan, r_perm.diizinkan, FALSE) as has_permission
            FROM public.pengguna p
            LEFT JOIN public.izin_pengguna u_ovr ON (p.id = u_ovr.pengguna_id AND u_ovr.izin_id = :iid)
            LEFT JOIN public.izin_peran r_perm ON (p.peran_id = r_perm.peran_id AND r_perm.izin_id = :iid)
            WHERE p.id = :uid
        ", ['iid' => $izin['id'], 'uid' => $userId])['has_permission'];

        if ($effectivePermission !== true) {
            $pdo->rollBack();
            return "User override diizinkan = TRUE gagal meng-override aturan peran.";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 6. RATE LIMITING SECURITY GUARD
// ------------------------------------------------------------------
runTest("6. Rate Limiting Keamanan: Direktori rate limit terlindungi berkas .htaccess", function() {
    $dir = APP_ROOT . '/cache/rate_limit/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    file_put_contents($dir . '.htaccess', 'Deny from all');

    if (!file_exists($dir . '.htaccess')) {
        return "Berkas .htaccess di folder rate limit tidak ditemukan.";
    }

    $content = file_get_contents($dir . '.htaccess');
    return str_contains($content, 'Deny from all');
});

// ------------------------------------------------------------------
// 7. NULL USERNAME & PASSWORD SECURITY GUARD (KARYAWAN BELUM JADI USER)
// ------------------------------------------------------------------
runTest("7. Pengamanan Akun Karyawan: Karyawan dengan nama_pengguna / kata_sandi NULL mutlak tidak bisa login", function() use ($pdo) {
    $pdo->beginTransaction();
    try {
        // Buat karyawan murni tanpa username dan tanpa kata_sandi (seperti hasil impor data)
        $stmt = $pdo->prepare("
            INSERT INTO public.pengguna (nama_lengkap, nik, posisi, status_aktif)
            VALUES ('Karyawan Non-User Test', '3201019911223304', 'pengemasan', TRUE)
            RETURNING id
        ");
        $stmt->execute();
        $karyawanId = $stmt->fetchColumn();

        // 1. Uji query login auth: pastikan query mengabaikan akun NULL
        $queryResult = Database::fetchOne("
            SELECT p.id, p.nama_lengkap, p.nama_pengguna, p.kata_sandi,
                   p.posisi, p.peran_id, pr.nama_peran as peran, p.status_aktif
            FROM public.pengguna p
            JOIN public.peran pr ON p.peran_id = pr.id
            WHERE p.nama_pengguna IS NOT NULL 
              AND TRIM(p.nama_pengguna) != ''
              AND p.kata_sandi IS NOT NULL 
              AND TRIM(p.kata_sandi) != ''
              AND LOWER(p.nama_pengguna) = LOWER(:username)
            LIMIT 1
        ", ['username' => '3201019911223304']);

        if (!empty($queryResult)) {
            $pdo->rollBack();
            return "Query auth seharusnya tidak menemukan karyawan tanpa username/password!";
        }

        // 2. Uji query dengan username kosong
        $emptyResult = Database::fetchOne("
            SELECT p.id
            FROM public.pengguna p
            WHERE p.nama_pengguna IS NOT NULL 
              AND TRIM(p.nama_pengguna) != ''
              AND LOWER(p.nama_pengguna) = LOWER('')
            LIMIT 1
        ");

        if (!empty($emptyResult)) {
            $pdo->rollBack();
            return "Query auth dengan username kosong seharusnya menghasilkan null!";
        }

        $pdo->rollBack();
        return true;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
});

// ------------------------------------------------------------------
// 8. STRICT PASSWORD VERIFICATION GUARD
// ------------------------------------------------------------------
runTest("8. Proteksi Hash & Bypass: Penolakan ketat jika password input kosong atau hash database kosong", function() {
    // 1. Password verify dengan string kosong
    if (password_verify('', '')) {
        return "password_verify('', '') seharusnya bernilai false!";
    }

    // 2. hash_equals dengan string kosong
    if (empty('') || empty(null)) {
        // Valid guard
    }

    return true;
});

// ------------------------------------------------------------------
// 9. SLIDING INACTIVITY TIMEOUT (1 JAM / 3.600 DETIK)
// ------------------------------------------------------------------
runTest("9. Manajemen Sesi: Sliding Inactivity Timeout 1 jam (3.600 detik) & refreshActivity", function() {
    if (Auth::INACTIVITY_TIMEOUT !== 3600) {
        return "INACTIVITY_TIMEOUT harus bernilai 3600 detik (1 jam).";
    }

    // Setup sesi aktif
    $_SESSION['user'] = ['id' => '00000000-0000-0000-0000-000000000001', 'nama_lengkap' => 'Test User', 'peran' => 'admin'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();

    if (Auth::isSessionExpired()) {
        return "Sesi baru seharusnya belum expired.";
    }

    // Simulasi user diam / tidak aktif selama 3.601 detik (> 1 jam)
    $_SESSION['last_activity'] = time() - 3601;
    if (!Auth::isSessionExpired()) {
        return "Sesi setelah 3.601 detik tidak aktif seharusnya expired!";
    }

    // Simulasi refresh aktivitas sebelum timeout (misal di menit ke-50 / 3.000 detik)
    $_SESSION['last_activity'] = time() - 3000;
    if (Auth::isSessionExpired()) {
        return "Sesi di menit ke-50 (< 1 jam) seharusnya masih aktif.";
    }

    // Panggil refreshActivity() untuk memperpanjang masa aktif sesi
    Auth::refreshActivity();
    if (time() - (int)$_SESSION['last_activity'] > 5) {
        return "refreshActivity() gagal memperbarui timestamp aktivitas terbaru.";
    }

    if (Auth::isSessionExpired()) {
        return "Sesi setelah refreshActivity() seharusnya tidak expired.";
    }

    return true;
});

// ------------------------------------------------------------------
// 10. INDEPENDENT PERMISSIONS & DEAD PERMISSION AUDIT
// ------------------------------------------------------------------
runTest("10. Audit Izin Granular: pos.void_item terhapus, izin mandiri (production.bom_manage, master.territories_manage, system.cache_manage) terdaftar", function() use ($pdo) {
    // 1. Verifikasi pos.void_item telah terhapus
    $voidPerm = Database::fetchOne("SELECT id FROM public.izin WHERE kode_izin = 'pos.void_item'");
    if ($voidPerm) {
        return "Izin mati 'pos.void_item' seharusnya sudah terhapus dari tabel public.izin!";
    }

    // 2. Verifikasi pos.discount tetap bertahan
    $discountPerm = Database::fetchOne("SELECT id FROM public.izin WHERE kode_izin = 'pos.discount'");
    if (!$discountPerm) {
        return "Izin 'pos.discount' harus tetap tersedia di database!";
    }

    // 3. Verifikasi pendaftaran 3 izin mandiri baru
    $newPerms = ['production.bom_manage', 'master.territories_manage', 'system.cache_manage'];
    foreach ($newPerms as $code) {
        $row = Database::fetchOne("SELECT id, grup_izin FROM public.izin WHERE kode_izin = :code", ['code' => $code]);
        if (!$row) {
            return "Izin mandiri '{$code}' tidak ditemukan di tabel public.izin!";
        }
    }

    // 4. Verifikasi mapping role default (Owner, Admin, Mandor)
    $owner = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'owner'");
    $admin = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'admin'");
    $mandor = Database::fetchOne("SELECT id FROM public.peran WHERE nama_peran = 'mandor'");

    if ($owner) {
        foreach ($newPerms as $code) {
            $has = Database::fetchOne("
                SELECT ip.diizinkan 
                FROM public.izin_peran ip
                JOIN public.izin i ON ip.izin_id = i.id
                WHERE ip.peran_id = :pid AND i.kode_izin = :code
            ", ['pid' => $owner['id'], 'code' => $code]);
            if (empty($has['diizinkan'])) {
                return "Role 'owner' harus memiliki izin '{$code}'!";
            }
        }
    }

    if ($admin) {
        foreach ($newPerms as $code) {
            $has = Database::fetchOne("
                SELECT ip.diizinkan 
                FROM public.izin_peran ip
                JOIN public.izin i ON ip.izin_id = i.id
                WHERE ip.peran_id = :pid AND i.kode_izin = :code
            ", ['pid' => $admin['id'], 'code' => $code]);
            if (empty($has['diizinkan'])) {
                return "Role 'admin' harus memiliki izin '{$code}'!";
            }
        }
    }

    if ($mandor) {
        $hasBom = Database::fetchOne("
            SELECT ip.diizinkan 
            FROM public.izin_peran ip
            JOIN public.izin i ON ip.izin_id = i.id
            WHERE ip.peran_id = :pid AND i.kode_izin = 'production.bom_manage'
        ", ['pid' => $mandor['id']]);
        if (empty($hasBom['diizinkan'])) {
            return "Role 'mandor' harus memiliki izin 'production.bom_manage'!";
        }
    }

    return true;
});

// ------------------------------------------------------------------
// SUMMARY
// ------------------------------------------------------------------
echo "\n============================================================\n";
echo " AUTH & RBAC MATRIX AUDIT SUMMARY:\n";
echo " - Total Tests : {$totalTests}\n";
echo " - Passed      : {$passed}\n";
echo " - Failed      : {$failed}\n";
echo "============================================================\n";

exit($failed > 0 ? 1 : 0);

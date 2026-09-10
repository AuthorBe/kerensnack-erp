<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Helpers\ActivityLog;
use Database;

/**
 * app/Controllers/AuthController.php
 * Pengendali Login, Logout, dan Pengalihan Sesi Multi-Peran.
 */

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const LOCKOUT_SECONDS = 900; // 15 minutes

    private function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    private function getRateLimitDir(): string
    {
        $dir = ROOT_PATH . '/cache/rate_limit/';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            file_put_contents($dir . '.htaccess', 'Deny from all');
        }
        return $dir;
    }

    private function getRateLimitFile(string $ip): string
    {
        return $this->getRateLimitDir() . md5($ip) . '.json';
    }

    private function checkRateLimit(string $ip): array
    {
        $file = $this->getRateLimitFile($ip);
        if (!file_exists($file)) {
            return ['blocked' => false, 'attempts' => 0, 'remaining' => 0];
        }

        $data = json_decode((string)file_get_contents($file), true);
        if (!$data) {
            return ['blocked' => false, 'attempts' => 0, 'remaining' => 0];
        }

        $elapsed = time() - ($data['first_attempt'] ?? time());
        if ($elapsed > self::LOCKOUT_SECONDS) {
            @unlink($file);
            return ['blocked' => false, 'attempts' => 0, 'remaining' => 0];
        }

        $attempts = (int)($data['attempts'] ?? 0);
        $blocked = $attempts >= self::MAX_ATTEMPTS;
        $remaining = self::LOCKOUT_SECONDS - $elapsed;

        return ['blocked' => $blocked, 'attempts' => $attempts, 'remaining' => max(0, $remaining)];
    }

    private function recordFailedAttempt(string $ip): void
    {
        $file = $this->getRateLimitFile($ip);
        $data = ['ip' => $ip, 'attempts' => 1, 'first_attempt' => time()];
        if (file_exists($file)) {
            $existing = json_decode((string)file_get_contents($file), true);
            if ($existing && (time() - ($existing['first_attempt'] ?? time())) <= self::LOCKOUT_SECONDS) {
                $newAttempts = ($existing['attempts'] ?? 0) + 1;
                // Jika mencapai ambang batas blokir, mulai hitungan lockout 15 menit penuh dari saat diblokir
                $firstAttempt = ($newAttempts >= self::MAX_ATTEMPTS) ? time() : $existing['first_attempt'];
                $data = [
                    'ip' => $ip,
                    'attempts' => $newAttempts,
                    'first_attempt' => $firstAttempt
                ];
            }
        }
        file_put_contents($file, json_encode($data), LOCK_EX);
    }

    private function resetRateLimit(string $ip): void
    {
        $file = $this->getRateLimitFile($ip);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect('/pos');
            return;
        }

        $ip = $this->getClientIp();

        // Fitur darurat untuk developer / testing: Buka blokir instan via query ?unlock=1
        if (isset($_GET['unlock']) || isset($_GET['reset_lock'])) {
            $this->resetRateLimit($ip);
            $this->redirect('/login');
            return;
        }

        $rateCheck = $this->checkRateLimit($ip);
        $remainingTime = 0;
        $error = $_SESSION['auth_error'] ?? null;
        unset($_SESSION['auth_error']);
        $info = null;

        if ($rateCheck['blocked']) {
            $remainingTime = $rateCheck['remaining'];
            $error = "<div>Terlalu banyak percobaan gagal.</div><div class='alert-sub'>Silakan coba lagi dalam <span id='countdown-timer' class='font-bold font-mono'></span>.</div>";
        }

        if (isset($_GET['timeout'])) {
            $info = 'Sesi Anda telah berakhir secara otomatis demi keamanan.';
        } elseif (isset($_GET['illegal'])) {
            $info = 'Silakan login terlebih dahulu untuk mengakses sistem.';
        }

        $this->view('auth.login', [
            'error' => $error,
            'info' => $info,
            'remainingTime' => $remainingTime,
            'maxAttempts' => self::MAX_ATTEMPTS,
            'lockoutMinutes' => (int)(self::LOCKOUT_SECONDS / 60)
        ]);
    }

    public function login(): void
    {
        $username = trim((string)$this->input('username'));
        $password = trim((string)$this->input('password'));
        $ip = $this->getClientIp();
        $rateCheck = $this->checkRateLimit($ip);

        if (empty($username) || empty($password)) {
            $_SESSION['auth_error'] = 'Nama pengguna dan kata sandi wajib diisi.';
            $this->redirect('/login');
            return;
        }

        try {
            // 1. Cari pengguna dari Database PostgreSQL
            $userDb = Database::fetchOne("
                SELECT p.id, p.nama_lengkap, p.nama_pengguna, p.kata_sandi,
                       p.posisi, p.peran_id, pr.nama_peran as peran, p.status_aktif,
                       k.id as karyawan_id
                FROM public.pengguna p
                JOIN public.peran pr ON p.peran_id = pr.id
                LEFT JOIN public.v_karyawan_info k ON k.pengguna_id = p.id
                WHERE LOWER(p.nama_pengguna) = LOWER(:username)
                LIMIT 1
            ", ['username' => $username]);

            $passwordMatch = false;
            $needsRehash = false;

            if ($userDb && !empty($userDb['kata_sandi'])) {
                if (password_verify($password, $userDb['kata_sandi'])) {
                    $passwordMatch = true;
                    if (password_needs_rehash($userDb['kata_sandi'], PASSWORD_BCRYPT)) {
                        $needsRehash = true;
                    }
                } elseif ($password === $userDb['kata_sandi']) {
                    $passwordMatch = true;
                    $needsRehash = true;
                }
            } elseif ($userDb && empty($userDb['kata_sandi'])) {
                // Inisialisasi password akun baru (HANYA JIKA PUNYA AKUN)
                if (empty($userDb['nama_pengguna'])) {
                    $passwordMatch = false; // Karyawan tanpa akun login tidak bisa masuk
                } else {
                    $passwordMatch = true;
                    $needsRehash = true;
                }
            }

            // Anti-brute-force guard: Jika IP diblokir, HANYA izinkan jika role developer dengan password benar
            if ($rateCheck['blocked']) {
                $isBypass = $passwordMatch && $userDb && (strtolower(trim((string)($userDb['peran'] ?? ''))) === 'developer');
                if (!$isBypass) {
                    $_SESSION['auth_error'] = "<div>Terlalu banyak percobaan gagal.</div><div class='alert-sub'>Silakan coba lagi dalam <span id='countdown-timer' class='font-bold font-mono'></span>.</div>";
                    $this->redirect('/login');
                    return;
                }
                // Khusus Developer dengan password benar: buka kunci limit secara otomatis
                $this->resetRateLimit($ip);
            }

            if ($userDb && $passwordMatch) {
                if (!$userDb['status_aktif']) {
                    $this->recordFailedAttempt($ip);
                    $_SESSION['auth_error'] = 'Akun Anda telah dinonaktifkan oleh Administrator.';
                    $this->redirect('/login');
                    return;
                }

                // Berhasil login: reset hitungan percobaan gagal
                $this->resetRateLimit($ip);

                if ($needsRehash) {
                    $newHash = password_hash($password, PASSWORD_BCRYPT);
                    Database::execute("UPDATE public.pengguna SET kata_sandi = :hash, diubah_pada = NOW() WHERE id = :id", [
                        'hash' => $newHash,
                        'id' => $userDb['id']
                    ]);
                }

                // Hapus hash dari memori sesi
                unset($userDb['kata_sandi']);

                // Regenerate session id untuk cegah session fixation
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_regenerate_id(true);
                }

                Auth::login($userDb);
                ActivityLog::log(
                    'keamanan_auth',
                    'LOGIN',
                    'pengguna',
                    $userDb['id'],
                    "Pengguna {$userDb['nama_lengkap']} berhasil login ke sistem.",
                    null,
                    null,
                    'web_app',
                    $userDb['id'],
                    $userDb['nama_lengkap'],
                    $userDb['peran'] ?? 'staff'
                );

                // Smart Redirect sesuai Role & Hak Akses
                if ($userDb['peran'] === 'driver' || ($userDb['peran'] === 'sales' && ($userDb['posisi'] ?? '') === 'driver')) {
                    $this->redirect('/deliveries');
                    return;
                }
                if ($userDb['peran'] === 'sales') {
                    $this->redirect('/consignment');
                    return;
                }
                if ($userDb['peran'] === 'owner') {
                    $this->redirect('/owner');
                    return;
                }

                $this->redirect('/pos');
                return;
            }

            // Gagal login: catat percobaan gagal
            $this->recordFailedAttempt($ip);
            ActivityLog::log(
                'keamanan',
                'LOGIN_FAILED',
                "Percobaan login gagal untuk username '{$username}' dari IP {$ip}",
                'pengguna',
                null,
                null,
                null,
                'web_app',
                null,
                'Tamu / Unauthenticated',
                'guest'
            );

            $newRateCheck = $this->checkRateLimit($ip);
            $attemptsLeft = max(0, self::MAX_ATTEMPTS - (int)($newRateCheck['attempts'] ?? 0));

            if ($attemptsLeft > 0) {
                $_SESSION['auth_error'] = "<div>Nama pengguna atau kata sandi tidak sesuai.</div><div class='alert-sub'>Sisa <strong>{$attemptsLeft} kali</strong> percobaan sebelum akses diblokir.</div>";
            } else {
                $_SESSION['auth_error'] = "<div>Terlalu banyak percobaan gagal.</div><div class='alert-sub'>Silakan coba lagi dalam <span id='countdown-timer' class='font-bold font-mono'></span>.</div>";
            }

            $this->redirect('/login');

        } catch (\Throwable $e) {
            $_SESSION['auth_error'] = 'Terjadi kesalahan koneksi: ' . $e->getMessage();
            $this->redirect('/login');
        }
    }

    public function logout(): void
    {
        if (Auth::check()) {
            ActivityLog::log(
                'keamanan',
                'LOGOUT',
                "Pengguna " . (Auth::user()['nama_lengkap'] ?? Auth::name() ?? 'Pengguna') . " telah keluar dari sistem",
                'pengguna',
                Auth::id()
            );
        }

        Auth::logout();
        
        // Anti-cache headers
        header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
        header("Cache-Control: post-check=0, pre-check=0", false);
        header("Pragma: no-cache");
        header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

        $this->view('auth.logout');
    }
}

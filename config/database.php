<?php
declare(strict_types=1);

/**
 * config/database.php
 * Koneksi Database PostgreSQL Supabase menggunakan PDO Singleton.
 * 100% Mengikuti pola arsitektur SalaryApp (D:\laragon\www\salary)
 */

require_once __DIR__ . '/env.php';

class Database
{
    private static ?PDO $instance = null;

    /**
     * Dapatkan koneksi PDO PostgreSQL tunggal (Singleton).
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $host     = getenv('DB_HOST')     ?: '127.0.0.1';
            $port     = getenv('DB_PORT')     ?: '5432';
            $dbname   = getenv('DB_DATABASE') ?: 'postgres';
            $user     = getenv('DB_USERNAME') ?: 'postgres';
            $password = getenv('DB_PASSWORD') ?: '';
            $sslmode  = getenv('DB_SSLMODE')  ?: 'prefer';

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";

            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $password, $options);
            } catch (PDOException $e) {
                // Log pesan error dan lempar exception yang ramah
                error_log("Database Connection Error: " . $e->getMessage());
                throw new RuntimeException("Gagal terhubung ke Database Supabase: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Helper untuk eksekusi query SELECT dan ambil semua baris
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Helper untuk eksekusi query SELECT dan ambil 1 baris
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getConnection()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Helper untuk eksekusi INSERT / UPDATE / DELETE
     */
    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::getConnection()->prepare($sql);
        return $stmt->execute($params);
    }
}

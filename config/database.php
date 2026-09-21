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
     * Dapatkan koneksi PDO PostgreSQL tunggal (Singleton) dengan Auto-Retry & Resilient Connection.
     */
    public static function getConnection(): PDO
    {
        // 1. Jika instance sudah ada, pastikan koneksi masih hidup (tidak diputus oleh Supabase Pooler)
        if (self::$instance !== null) {
            try {
                self::$instance->query("SELECT 1");
                return self::$instance;
            } catch (Throwable $e) {
                // Socket telah diputus oleh pooler/server, reset instance untuk menyambung ulang
                self::$instance = null;
            }
        }

        $host     = $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?: '127.0.0.1';
        $port     = $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?: '5432';
        $dbname   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'postgres';
        $user     = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'postgres';
        $password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';
        $sslmode  = $_ENV['DB_SSLMODE']  ?? getenv('DB_SSLMODE')  ?: 'prefer';

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 15,
        ];

        $maxRetries = 3;
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $pdo = new PDO($dsn, $user, $password, $options);
                $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
                self::$instance = $pdo;
                return self::$instance;
            } catch (PDOException $e) {
                $lastException = $e;
                error_log("Database Connection Attempt {$attempt}/{$maxRetries} Failed: " . $e->getMessage());
                if ($attempt < $maxRetries) {
                    usleep(300000); // 300ms pause sebelum mencoba kembali
                }
            }
        }

        // Jika seluruh retry gagal, lempar RuntimeException
        throw new RuntimeException("Gagal terhubung ke Database Supabase: " . ($lastException ? $lastException->getMessage() : 'Unknown Error'));
    }

    /**
     * Alias singkat untuk getConnection()
     */
    public static function pdo(): PDO
    {
        return self::getConnection();
    }

    /**
     * Bind parameter dengan tipe data PDO yang tepat (khususnya boolean & null untuk PostgreSQL)
     */
    private static function bindAndExecute(PDOStatement $stmt, array $params = []): bool
    {
        foreach ($params as $key => $val) {
            $paramKey = is_int($key) ? $key + 1 : (str_starts_with((string)$key, ':') ? $key : ':' . $key);
            if (is_bool($val)) {
                $stmt->bindValue($paramKey, $val, PDO::PARAM_BOOL);
            } elseif (is_null($val)) {
                $stmt->bindValue($paramKey, null, PDO::PARAM_NULL);
            } elseif (is_int($val)) {
                $stmt->bindValue($paramKey, $val, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($paramKey, (string)$val, PDO::PARAM_STR);
            }
        }
        return $stmt->execute();
    }

    /**
     * Helper untuk eksekusi query SELECT dan ambil semua baris
     */
    public static function fetchAll(string $sql, array $params = []): array
    {
        $stmt = self::getConnection()->prepare($sql);
        self::bindAndExecute($stmt, $params);
        return $stmt->fetchAll();
    }

    /**
     * Helper untuk eksekusi query SELECT dan ambil 1 baris
     */
    public static function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = self::getConnection()->prepare($sql);
        self::bindAndExecute($stmt, $params);
        $result = $stmt->fetch();
        return $result !== false ? $result : null;
    }

    /**
     * Helper untuk eksekusi INSERT / UPDATE / DELETE
     */
    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = self::getConnection()->prepare($sql);
        return self::bindAndExecute($stmt, $params);
    }

    /**
     * Helper untuk insert data: Database::insert('public.peran', ['nama_peran' => 'staff'])
     */
    public static function insert(string $table, array $data): bool
    {
        if (empty($data)) {
            return false;
        }
        $columns = array_keys($data);
        $fields = implode(', ', $columns);
        $placeholders = ':' . implode(', :', $columns);
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";
        return self::execute($sql, $data);
    }

    /**
     * Helper untuk update data: Database::update('public.peran', ['deskripsi' => '...'], ['id' => $id])
     */
    public static function update(string $table, array $data, array $where): bool
    {
        if (empty($data) || empty($where)) {
            return false;
        }
        $setClauses = [];
        $params = [];
        foreach ($data as $col => $val) {
            $paramName = 'set_' . str_replace('.', '_', $col);
            $setClauses[] = "{$col} = :{$paramName}";
            $params[$paramName] = $val;
        }
        $whereClauses = [];
        foreach ($where as $col => $val) {
            $paramName = 'where_' . str_replace('.', '_', $col);
            $whereClauses[] = "{$col} = :{$paramName}";
            $params[$paramName] = $val;
        }
        $sql = "UPDATE {$table} SET " . implode(', ', $setClauses) . " WHERE " . implode(' AND ', $whereClauses);
        return self::execute($sql, $params);
    }

    /**
     * Helper untuk delete data: Database::delete('public.peran', ['id' => $id])
     */
    public static function delete(string $table, array $where): bool
    {
        if (empty($where)) {
            return false;
        }
        $whereClauses = [];
        $params = [];
        foreach ($where as $col => $val) {
            $paramName = 'del_' . str_replace('.', '_', $col);
            $whereClauses[] = "{$col} = :{$paramName}";
            $params[$paramName] = $val;
        }
        $sql = "DELETE FROM {$table} WHERE " . implode(' AND ', $whereClauses);
        return self::execute($sql, $params);
    }
}

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

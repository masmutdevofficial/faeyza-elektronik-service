<?php
declare(strict_types=1);
final class Database {
    private static ?mysqli $connection = null;
    public static function connection(bool $withDatabase = true): mysqli {
        if ($withDatabase && self::$connection) return self::$connection;
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        $c = new mysqli(getenv('DB_HOST') ?: '127.0.0.1', getenv('DB_USER') ?: 'faeyza', getenv('DB_PASSWORD') ?: '', $withDatabase ? (getenv('DB_NAME') ?: 'faeyza') : '', (int)(getenv('DB_PORT') ?: 3306));
        $c->set_charset('utf8mb4');
        $c->query("SET time_zone = '+07:00'");
        $c->query("SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");
        if ($withDatabase) self::$connection = $c;
        return $c;
    }
    public static function query(string $sql, array $params = []): mysqli_stmt {
        $stmt = self::connection()->prepare($sql);
        if ($params) $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        $stmt->execute();
        return $stmt;
    }
    public static function all(string $sql, array $params = []): array { return self::query($sql,$params)->get_result()->fetch_all(MYSQLI_ASSOC); }
    public static function one(string $sql, array $params = []): ?array { return self::query($sql,$params)->get_result()->fetch_assoc(); }
    public static function tx(callable $callback): mixed {
        $c = self::connection();
        for ($attempt=0; ; $attempt++) {
            $c->begin_transaction();
            try { $result=$callback(); $c->commit(); return $result; }
            catch (Throwable $e) { $c->rollback(); if ($e instanceof mysqli_sql_exception && in_array($e->getCode(),[1205,1213]) && $attempt<3) { usleep(random_int(15000,70000)); continue; } throw $e; }
        }
    }
    public static function id(): int { return (int)self::connection()->insert_id; }
}

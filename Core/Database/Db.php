<?php
declare(strict_types=1);
// ============================================================
// src/Core/Database/Db.php
// Façade statique vers Database::getInstance()
// Usage : Db::prepare($sql), Db::lastInsertId()
// Namespace : Nenad\Autosav\Core\Database
// ============================================================

namespace Nenad\Autosav\Core\Database;

use PDOStatement;

class Db
{
    public static function prepare(string $sql): PDOStatement
    {
        return Database::getInstance()->prepare($sql);
    }

    public static function exec(string $sql): int
    {
        return Database::getInstance()->exec($sql);
    }

    public static function lastInsertId(): string
    {
        return Database::getInstance()->lastInsertId();
    }

    public static function beginTransaction(): void { Database::getInstance()->beginTransaction(); }
    public static function commit(): void           { Database::getInstance()->commit(); }
    public static function rollback(): void         { Database::getInstance()->rollback(); }

    public static function transaction(callable $callback): mixed
    {
        return Database::getInstance()->transaction($callback);
    }
}

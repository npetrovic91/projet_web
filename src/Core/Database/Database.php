<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Database;

use PDO;
use PDOException;
use PDOStatement;

/**
 * AUTOSAV — Gestionnaire de base de donnees (CORRIGE)
 * - handleConnectionFailure() isole la gestion d'erreur
 * - Logger appele dans try-catch pour eviter exception secondaire
 * - Message debug utile sans exposer le mot de passe
 */
class Database
{
    private static ?self $instance = null;
    private PDO $pdo;
    private ?PDOStatement $lastStatement = null;

    private function __construct()
    {
        $dsn = sprintf(
            '%s:host=%s;port=%d;dbname=%s;charset=%s',
            DB_DRIVER, DB_HOST, DB_PORT, DB_NAME, DB_CHARSET
        );
        try {
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, DB_PDO_OPTIONS);
        } catch (PDOException $e) {
            self::handleConnectionFailure($e, $dsn);
        }
    }

    private static function handleConnectionFailure(PDOException $e, string $dsn): never
    {
        $debugMsg = sprintf(
            'Connexion BDD echouee — DSN: %s — User: %s — Erreur SQLSTATE[%s]: %s',
            $dsn, DB_USER, $e->getCode(), $e->getMessage()
        );
        try {
            if (function_exists('logger')) {
                logger('database')->critical($debugMsg, [
                    'dsn_host' => DB_HOST,
                    'dsn_db'   => DB_NAME,
                    'code'     => $e->getCode(),
                ]);
            } else {
                error_log('[AUTOSAV] ' . $debugMsg);
            }
        } catch (\Throwable) {
            error_log('[AUTOSAV] Logger indisponible. ' . $debugMsg);
        }

        http_response_code(503);
        $display = (defined('APP_DEBUG') && APP_DEBUG)
            ? htmlspecialchars($debugMsg, ENT_QUOTES, 'UTF-8')
            : 'Impossible de se connecter a la base de donnees.';
        die('<h1>Service temporairement indisponible</h1><p>' . $display . '</p>');
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getPdo(): PDO { return $this->pdo; }

    public function prepare(string $sql): PDOStatement
    {
        return $this->pdo->prepare($sql);
    }

    public function exec(string $sql): int
    {
        $result = $this->pdo->exec($sql);
        return $result === false ? 0 : $result;
    }

    public function lastInsertId(?string $name = null): string
    {
        return $this->pdo->lastInsertId($name);
    }

    public function beginTransaction(): bool { return $this->pdo->beginTransaction(); }
    public function commit(): bool           { return $this->pdo->commit(); }
    public function rollback(): bool         { return $this->pdo->rollBack(); }

    public function transaction(callable $callback): mixed
    {
        if ($this->pdo->inTransaction()) {
            return $callback($this);
        }
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) $this->rollback();
            throw $e;
        }
    }

    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->bind($this->prepare($sql), $params);
        $this->lastStatement = $stmt;
        return $stmt->execute();
    }

    public function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->bind($this->prepare($sql), $params);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function fetchOne(string $sql, array $params = []): ?array
    {
        return $this->fetch($sql, $params);
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->bind($this->prepare($sql), $params);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->bind($this->prepare($sql), $params);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function rowCount(): int
    {
        return $this->lastStatement?->rowCount() ?? 0;
    }

    private function bind(PDOStatement $stmt, array $params): PDOStatement
    {
        foreach ($params as $key => $value) {
            $placeholder = is_int($key) ? $key + 1 : $key;
            if (is_string($placeholder) && $placeholder !== '' && $placeholder[0] !== ':') {
                $placeholder = ':' . $placeholder;
            }
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                $value === null => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($placeholder, $value, $type);
        }
        return $stmt;
    }

    private function __clone() {}

    public function __wakeup(): void
    {
        throw new \RuntimeException('Impossible de deserialiser le singleton Database.');
    }
}
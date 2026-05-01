<?php
declare(strict_types=1);

namespace Nenad\Autosav\Core\Model;

use Nenad\Autosav\Core\Database\Database;
use PDO;
use PDOStatement;

/**
 * AUTOSAV — Modèle de base
 * Fichier : src/Core/Model/BaseModel.php
 * Rôle    : Abstraction PDO commune à tous les modèles.
 *           Toujours utiliser bindValue() — jamais d'interpolation.
 *           Toujours utiliser le préfixe de tables DB_PREFIX.
 */
abstract class BaseModel
{
    /** Instance PDO partagée */
    protected PDO $pdo;

    /** Gestionnaire Database complet pour les modeles qui utilisent la facade. */
    protected Database $db;

    /** Nom de la table (sans préfixe). Doit être défini dans chaque modèle. */
    protected string $table = '';

    /** Nom complet de la table avec préfixe */
    protected string $tableName = '';

    public function __construct()
    {
        $this->db        = Database::getInstance();
        $this->pdo       = $this->db->getPdo();
        $this->tableName = str_starts_with($this->table, DB_PREFIX)
            ? $this->table
            : DB_PREFIX . $this->table;
    }

    // ============================================================
    // MÉTHODES CRUD DE BASE
    // ============================================================

    /**
     * Trouve un enregistrement par son ID.
     */
    public function findById(int $id, string $pkColumn = 'id'): ?array
    {
        $col = $this->colPrefix() . $pkColumn;
        $sql = "SELECT * FROM `{$this->tableName}` WHERE `{$col}` = :id LIMIT 1";
        $stmt = $this->query($sql, ['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Trouve un enregistrement par une colonne.
     */
    public function findBy(string $column, mixed $value): ?array
    {
        $sql  = "SELECT * FROM `{$this->tableName}` WHERE `{$column}` = :val LIMIT 1";
        $stmt = $this->query($sql, ['val' => $value]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Récupère tous les enregistrements.
     */
    public function findAll(string $orderBy = '', int $limit = 0): array
    {
        $sql = "SELECT * FROM `{$this->tableName}`";
        if ($orderBy) $sql .= " ORDER BY {$orderBy}";
        if ($limit > 0) $sql .= " LIMIT {$limit}";
        return $this->query($sql)->fetchAll();
    }

    /**
     * Insère un enregistrement et retourne l'ID inséré.
     */
    public function insert(array $data): int
    {
        $columns      = implode('`, `', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql  = "INSERT INTO `{$this->tableName}` (`{$columns}`) VALUES ({$placeholders})";
        $this->query($sql, $data);
        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Met à jour un enregistrement par son ID.
     */
    public function update(int $id, array $data, string $pkColumn = 'id'): int
    {
        $col  = $this->colPrefix() . $pkColumn;
        $sets = implode(', ', array_map(fn($k) => "`{$k}` = :{$k}", array_keys($data)));
        $sql  = "UPDATE `{$this->tableName}` SET {$sets} WHERE `{$col}` = :__pk";
        $data['__pk'] = $id;
        $stmt = $this->query($sql, $data);
        return $stmt->rowCount();
    }

    /**
     * Soft-delete (marque deleted_at).
     */
    public function softDelete(int $id, ?int $deletedBy = null, ?string $reason = null, string $pkColumn = 'id'): int
    {
        $pk  = $this->colPrefix() . $pkColumn;
        $del = $this->colPrefix() . 'deleted_at';
        $by  = $this->colPrefix() . 'deleted_by';
        $res = $this->colPrefix() . 'deleted_reason';

        $sql  = "UPDATE `{$this->tableName}` SET `{$del}` = NOW(), `{$by}` = :by, `{$res}` = :reason WHERE `{$pk}` = :id";
        $stmt = $this->query($sql, ['id' => $id, 'by' => $deletedBy, 'reason' => $reason]);
        return $stmt->rowCount();
    }

    /**
     * Suppression physique.
     */
    public function delete(int $id, string $pkColumn = 'id'): int
    {
        $col  = $this->colPrefix() . $pkColumn;
        $sql  = "DELETE FROM `{$this->tableName}` WHERE `{$col}` = :id";
        $stmt = $this->query($sql, ['id' => $id]);
        return $stmt->rowCount();
    }

    /**
     * Compte les enregistrements.
     */
    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) as cnt FROM `{$this->tableName}`";
        if ($where) $sql .= " WHERE {$where}";
        $stmt = $this->query($sql, $params);
        return (int)($stmt->fetch()['cnt'] ?? 0);
    }

    // ============================================================
    // EXÉCUTION DE REQUÊTES PDO (avec bindValue SYSTÉMATIQUE)
    // ============================================================

    /**
     * Exécute une requête préparée avec bindValue().
     *
     * @param string $sql
     * @param array  $params Associatif ['col' => value, ...] ou positionnel
     * @return PDOStatement
     */
    protected function query(string $sql, array $params = []): PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);

        foreach ($params as $key => $value) {
            $placeholder = is_int($key) ? ($key + 1) : ":{$key}";
            $type = match (true) {
                is_int($value)  => PDO::PARAM_INT,
                is_bool($value) => PDO::PARAM_BOOL,
                is_null($value) => PDO::PARAM_NULL,
                default         => PDO::PARAM_STR,
            };
            $stmt->bindValue($placeholder, $value, $type);
        }

        $stmt->execute();
        return $stmt;
    }

    /**
     * Exécute une requête et retourne tous les résultats.
     */
    protected function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    /**
     * Exécute une requête et retourne un seul résultat.
     */
    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: null;
    }

    protected function db(): Database
    {
        return $this->db;
    }

    /**
     * Démarre une transaction.
     */
    protected function beginTransaction(): void
    {
        $this->pdo->beginTransaction();
    }

    /**
     * Valide une transaction.
     */
    protected function commit(): void
    {
        $this->pdo->commit();
    }

    /**
     * Annule une transaction.
     */
    protected function rollback(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * Retourne le préfixe de colonnes de la table courante.
     * Doit être surchargé dans chaque modèle.
     */
    protected function colPrefix(): string
    {
        $name = str_starts_with($this->table, DB_PREFIX)
            ? substr($this->table, strlen(DB_PREFIX))
            : $this->table;

        $first = explode('_', $name)[0] ?? 'id';
        return substr(str_pad($first, 3, $first), 0, 3) . '_';
    }
}

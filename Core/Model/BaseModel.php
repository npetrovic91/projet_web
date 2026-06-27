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

    /** Préfixe explicite des colonnes de la table, ex. soc_, uti_, rol_. */
    protected string $colPrefix = '';

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
     *
     * CORRECTIF HIGH-2 (audit sécurité 2026-06-26) : $orderBy était
     * concaténé directement dans la requête sans validation
     * ("ORDER BY {$orderBy}"). Si jamais passé depuis une valeur
     * utilisateur ($_GET['sort']), c'était une injection SQL via ORDER BY.
     * Aucun appelant actuel ne passait de valeur utilisateur (vérifié :
     * findAll() n'est appelé nulle part dans le code à ce jour), mais la
     * méthode reste publique et doit être sûre par construction.
     *
     * $orderBy est maintenant validé par quoteIdentifier() (format strict
     * /^[A-Za-z0-9_]+$/) et, si une whitelist est fournie, vérifié contre
     * elle. $orderBy doit toujours être une valeur en dur dans le code
     * appelant, jamais directement issue de $_GET/$_POST.
     *
     * @param string[] $allowedColumns Whitelist optionnelle des colonnes de tri autorisées.
     */
    public function findAll(string $orderBy = '', int $limit = 0, string $direction = 'ASC', array $allowedColumns = []): array
    {
        $sql = "SELECT * FROM `{$this->tableName}`";
        if ($orderBy !== '') {
            if ($allowedColumns !== [] && !in_array($orderBy, $allowedColumns, true)) {
                throw new \InvalidArgumentException('Colonne de tri non autorisée : ' . $orderBy);
            }
            $direction = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= ' ORDER BY ' . $this->quoteIdentifier($orderBy) . ' ' . $direction;
        }
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
        $pk = $this->colPrefix() . $pkColumn;
        $columns = $this->tableColumns();
        $sets = [];
        $params = ['id' => $id];

        foreach ([$this->colPrefix() . 'supprime_le', $this->colPrefix() . 'deleted_at'] as $deletedColumn) {
            if (in_array($deletedColumn, $columns, true)) {
                $sets[] = "`{$deletedColumn}` = NOW()";
                break;
            }
        }

        foreach ([$this->colPrefix() . 'supprime_par_utilisateur_id', $this->colPrefix() . 'deleted_by'] as $byColumn) {
            if (in_array($byColumn, $columns, true)) {
                $sets[] = "`{$byColumn}` = :by";
                $params['by'] = $deletedBy;
                break;
            }
        }

        foreach ([$this->colPrefix() . 'motif_suppression', $this->colPrefix() . 'deleted_reason'] as $reasonColumn) {
            if (in_array($reasonColumn, $columns, true)) {
                $sets[] = "`{$reasonColumn}` = :reason";
                $params['reason'] = $reason;
                break;
            }
        }

        if ($sets === []) {
            return $this->delete($id, $pkColumn);
        }

        $sql = "UPDATE `{$this->tableName}` SET " . implode(', ', $sets) . " WHERE `{$pk}` = :id";
        $stmt = $this->query($sql, $params);
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

    protected function filterColumns(array $payload, ?string $table = null): array
    {
        return array_intersect_key($payload, array_flip($this->tableColumns($table)));
    }

    protected function tableColumns(?string $table = null): array
    {
        static $cache = [];

        $tableName = $this->normalizeTableName($table ?? $this->tableName);
        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }

        $rows = $this->db->fetchAll('SHOW COLUMNS FROM ' . $this->quoteIdentifier($tableName));
        $cache[$tableName] = array_map('strval', array_column($rows, 'Field'));

        return $cache[$tableName];
    }

    protected function insertDynamic(string $table, array $payload): bool
    {
        $payload = $this->filterColumns($payload, $table);
        if ($payload === []) {
            throw new \InvalidArgumentException('Aucune colonne valide pour insertion dans ' . $table);
        }

        $columns = array_keys($payload);
        $quoted = array_map(fn(string $column): string => $this->quoteIdentifier($column), $columns);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        return $this->db->execute(
            'INSERT INTO ' . $this->quoteIdentifier($this->normalizeTableName($table)) .
            ' (' . implode(', ', $quoted) . ') VALUES (' . implode(', ', $placeholders) . ')',
            $payload
        );
    }

    protected function updateDynamic(string $table, string $primaryKey, int $id, array $payload): bool
    {
        $payload = $this->filterColumns($payload, $table);
        if ($payload === []) {
            return true;
        }

        $sets = array_map(
            fn(string $column): string => $this->quoteIdentifier($column) . ' = :' . $column,
            array_keys($payload)
        );
        $payload['__id'] = $id;

        return $this->db->execute(
            'UPDATE ' . $this->quoteIdentifier($this->normalizeTableName($table)) .
            ' SET ' . implode(', ', $sets) .
            ' WHERE ' . $this->quoteIdentifier($primaryKey) . ' = :__id',
            $payload
        );
    }

    public function statusId(string $domain, string $code): ?int
    {
        $id = $this->db->fetchColumn(
            'SELECT sta_id
             FROM sav_statuts
             WHERE sta_domaine = :domain
               AND sta_code = :code
               AND sta_supprime_le IS NULL
             LIMIT 1',
            ['domain' => $domain, 'code' => $code]
        );

        return $id !== false && $id !== null ? (int) $id : null;
    }

    protected function normalizeTableName(string $table): string
    {
        $table = trim($table, '` ');
        if (!preg_match('/^[A-Za-z0-9_]+$/', $table)) {
            throw new \InvalidArgumentException('Nom de table invalide: ' . $table);
        }

        if (!str_starts_with($table, DB_PREFIX)) {
            $table = DB_PREFIX . $table;
        }

        return $table;
    }

    protected function quoteIdentifier(string $identifier): string
    {
        $identifier = trim($identifier, '` ');
        if (!preg_match('/^[A-Za-z0-9_]+$/', $identifier)) {
            throw new \InvalidArgumentException('Identifiant SQL invalide: ' . $identifier);
        }

        return '`' . $identifier . '`';
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
        if ($this->colPrefix !== '' && preg_match('/^[a-z][a-z0-9]{2}_$/', $this->colPrefix)) {
            return $this->colPrefix;
        }

        throw new \LogicException(sprintf(
            'Le modèle %s doit déclarer explicitement protected string $colPrefix = "xxx_";',
            static::class
        ));
    }
}

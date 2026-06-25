<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Functions\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use PDO;

/**
 * Modele de reference des fonctions metier.
 *
 * Source SQL unique : sav_fonctions.
 * Les alias fnc_* sont exposes pour compatibilite avec les ecrans existants
 * et avec les anciens appels internes du module Functions.
 */
class FunctionModel extends BaseModel
{
    protected string $table = 'sav_fonctions';
    protected string $colPrefix = 'fon_';

    public function getForContext(?int $companyId = null, bool $adminView = false, array $networkOwnerCompanyIds = []): array
    {
        $conditions = ['f.fon_supprime_le IS NULL', 'f.fon_archive_le IS NULL'];
        $params = [];

        if (!$adminView) {
            $conditions[] = "(st.sta_code IS NULL OR st.sta_code = 'actif')";
        }

        if ($companyId !== null) {
            $this->addScopeCondition($conditions, $params, 'f.fon_societe_proprietaire_id', 'fon_portee_code', $companyId, $networkOwnerCompanyIds);
        }

        return $this->db->fetchAll($this->selectSql() . '
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY CASE WHEN f.fon_societe_proprietaire_id IS NULL THEN 0 ELSE 1 END, f.fon_nom ASC', $params);
    }

    public function findByIdFull(int $id): ?array
    {
        return $this->db->fetch($this->selectSql() . '
            WHERE f.fon_id = :id
              AND f.fon_supprime_le IS NULL
              AND f.fon_archive_le IS NULL
            LIMIT 1', ['id' => $id]);
    }

    public function codeExists(string $code, ?int $companyId = null, ?int $excludeId = null): bool
    {
        $conditions = ['fon_code = :code'];
        $params = ['code' => strtoupper(trim($code))];
        $columns = $this->tableColumns();

        if (in_array('fon_supprime_le', $columns, true)) {
            $conditions[] = 'fon_supprime_le IS NULL';
        }
        if (in_array('fon_archive_le', $columns, true)) {
            $conditions[] = 'fon_archive_le IS NULL';
        }

        // La contrainte SQL actuelle rend le code unique parmi les fonctions actives.
        // Le parametre companyId est conserve pour compatibilite de signature.
        if ($excludeId !== null) {
            $conditions[] = 'fon_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql = 'SELECT COUNT(*) FROM sav_fonctions WHERE ' . implode(' AND ', $conditions);

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function createFunction(array $data): int
    {
        $payload = $this->filterColumns([
            'fon_code' => strtoupper(trim((string) $data['code'])),
            'fon_nom' => trim((string) $data['name']),
            'fon_description' => $data['description'] ?? null,
            'fon_societe_proprietaire_id' => $data['owner_company_id'] ?? null,
            'fon_type_societe_id' => $data['company_type_id'] ?? null,
            'fon_portee_code' => $data['scope_code'] ?? 'interne',
            'fon_statut_id' => $data['status_id'] ?? $this->statusId('general', 'actif'),
            'fon_cree_par_utilisateur_id' => $data['created_by'] ?? null,
            'fon_modifie_par_utilisateur_id' => $data['created_by'] ?? null,
        ]);

        $this->insertDynamic('sav_fonctions', $payload);

        return (int) $this->db->lastInsertId();
    }

    public function updateFunction(int $id, array $data): bool
    {
        $payload = $this->filterColumns([
            'fon_code' => strtoupper(trim((string) $data['code'])),
            'fon_nom' => trim((string) $data['name']),
            'fon_description' => $data['description'] ?? null,
            'fon_type_societe_id' => $data['company_type_id'] ?? null,
            'fon_portee_code' => $data['scope_code'] ?? 'interne',
            'fon_statut_id' => $data['status_id'] ?? $this->statusId('general', 'actif'),
            'fon_modifie_par_utilisateur_id' => $data['updated_by'] ?? null,
        ]);

        return $this->updateDynamic('sav_fonctions', 'fon_id', $id, $payload);
    }

    public function setActive(int $id, bool $active, int $updatedBy): bool
    {
        return $this->db->execute(
            'UPDATE sav_fonctions
             SET fon_statut_id = :status_id,
                 fon_modifie_par_utilisateur_id = :updated_by
             WHERE fon_id = :id
               AND fon_supprime_le IS NULL
               AND fon_archive_le IS NULL',
            [
                'id' => $id,
                'status_id' => $this->statusId('general', $active ? 'actif' : 'inactif'),
                'updated_by' => $updatedBy,
            ]
        );
    }

    public function search(string $query, ?int $companyId = null, int $limit = 20, array $networkOwnerCompanyIds = []): array
    {
        $conditions = [
            'f.fon_supprime_le IS NULL',
            'f.fon_archive_le IS NULL',
            "(st.sta_code IS NULL OR st.sta_code = 'actif')",
            '(f.fon_nom LIKE :q1 OR f.fon_code LIKE :q2)',
        ];
        $params = [];

        if ($companyId !== null) {
            $this->addScopeCondition($conditions, $params, 'f.fon_societe_proprietaire_id', 'fon_portee_code', $companyId, $networkOwnerCompanyIds);
        }

        // Paramètres nommés distincts. Database::bind() gère les types PDO automatiquement.
        $like = '%' . trim($query) . '%';
        $params['q1']     = $like;
        $params['q2']     = $like;
        $params['qlimit'] = max(1, min(50, $limit));

        $sql = $this->selectSql() . '
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY CASE WHEN f.fon_societe_proprietaire_id IS NULL THEN 0 ELSE 1 END, f.fon_nom ASC
            LIMIT :qlimit';

        return $this->db->fetchAll($sql, $params);
    }

    public function companyTypes(): array
    {
        return $this->db->fetchAll(
            "SELECT tso_id, tso_code, tso_nom
             FROM sav_types_societes
             WHERE tso_supprime_le IS NULL
               AND tso_archive_le IS NULL
             ORDER BY tso_nom ASC"
        );
    }

    private function selectSql(): string
    {
        $scopeExpr = $this->porteeSelect('f', 'fon_portee_code');

        return "SELECT
                    f.*,
                    f.fon_id AS fnc_id,
                    f.fon_code AS fnc_code,
                    f.fon_nom AS fnc_label,
                    f.fon_nom AS fnc_name,
                    f.fon_description AS fnc_description,
                    f.fon_societe_proprietaire_id AS fnc_company_id,
                    f.fon_societe_proprietaire_id AS fnc_owner_company_id,
                    f.fon_type_societe_id AS fnc_company_type_id,
                    " . $scopeExpr . " AS fnc_scope_code,
                    " . $scopeExpr . " AS fon_portee_code,
                    f.fon_statut_id AS fnc_status_id,
                    f.fon_cree_le AS fnc_created_at,
                    f.fon_modifie_le AS fnc_updated_at,
                    f.fon_supprime_le AS fnc_deleted_at,
                    f.fon_archive_le AS fnc_archived_at,
                    f.fon_cree_par_utilisateur_id AS fnc_created_by,
                    f.fon_modifie_par_utilisateur_id AS fnc_updated_by,
                    CASE WHEN f.fon_societe_proprietaire_id IS NULL THEN 1 ELSE 0 END AS fnc_is_global,
                    CASE WHEN st.sta_code IS NULL OR st.sta_code = 'actif' THEN 1 ELSE 0 END AS fnc_is_active,
                    CASE WHEN f.fon_societe_proprietaire_id IS NULL THEN 'plateforme' ELSE " . $scopeExpr . " END AS fnc_scope,
                    st.sta_code AS fnc_status_code,
                    st.sta_libelle AS fnc_status_label,
                    s.soc_nom AS fnc_company_name,
                    t.tso_code AS fnc_company_type_code,
                    t.tso_nom AS fnc_company_type_name,
                    CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS fnc_creator_name
                FROM sav_fonctions f
                LEFT JOIN sav_statuts st ON st.sta_id = f.fon_statut_id
                LEFT JOIN sav_societes s ON s.soc_id = f.fon_societe_proprietaire_id
                LEFT JOIN sav_types_societes t ON t.tso_id = f.fon_type_societe_id
                LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = f.fon_cree_par_utilisateur_id";
    }


    private function addScopeCondition(array &$conditions, array &$params, string $ownerColumn, string $scopeColumn, int $companyId, array $networkOwnerCompanyIds): void
    {
        $networkOwnerCompanyIds = array_values(array_unique(array_filter(array_map('intval', $networkOwnerCompanyIds), static fn(int $id): bool => $id > 0)));
        $scopeExpr = $this->porteeSelect('f', $scopeColumn);
        $parts = [$ownerColumn . ' IS NULL', $ownerColumn . ' = :company_id'];
        $params['company_id'] = $companyId;

        if ($networkOwnerCompanyIds !== [] && in_array($scopeColumn, $this->tableColumns(), true)) {
            $placeholders = [];
            foreach ($networkOwnerCompanyIds as $index => $id) {
                $key = 'network_owner_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $id;
            }
            $parts[] = '(' . $ownerColumn . ' IN (' . implode(', ', $placeholders) . ") AND " . $scopeExpr . " = 'reseau')";
        }

        $conditions[] = '(' . implode(' OR ', $parts) . ')';
    }

    private function porteeSelect(string $alias, string $column): string
    {
        return in_array($column, $this->tableColumns(), true)
            ? $alias . '.' . $column
            : "'interne'";
    }

}

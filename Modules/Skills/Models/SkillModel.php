<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Skills\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use PDO;

/**
 * Modele de reference des competences metier.
 *
 * Source SQL unique : sav_competences.
 * Les alias skl_* sont conserves pour compatibilite avec les vues et appels existants.
 */
class SkillModel extends BaseModel
{
    protected string $table = 'sav_competences';
    protected string $colPrefix = 'cmp_';

    public function getForContext(?int $companyId = null, bool $adminView = false, array $networkOwnerCompanyIds = []): array
    {
        $conditions = ['c.cmp_supprime_le IS NULL', 'c.cmp_archive_le IS NULL'];
        $params = [];

        if (!$adminView) {
            $conditions[] = "(st.sta_code IS NULL OR st.sta_code = 'actif')";
        }

        if ($companyId !== null) {
            $this->addScopeCondition($conditions, $params, 'c.cmp_societe_proprietaire_id', 'cmp_portee_code', $companyId, $networkOwnerCompanyIds);
        }

        return $this->db->fetchAll(
            $this->selectSql() . "
 WHERE " . implode(' AND ', $conditions) . "
 ORDER BY CASE WHEN c.cmp_societe_proprietaire_id IS NULL THEN 0 ELSE 1 END, c.cmp_nom ASC",
            $params
        );
    }

    public function findByIdFull(int $id): ?array
    {
        return $this->db->fetch(
            $this->selectSql() . "\n WHERE c.cmp_id = :id AND c.cmp_supprime_le IS NULL AND c.cmp_archive_le IS NULL LIMIT 1",
            ['id' => $id]
        );
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $conditions = ['cmp_code = :code'];
        $params = ['code' => strtoupper(trim($code))];
        $columns = $this->tableColumns();

        if (in_array('cmp_supprime_le', $columns, true)) {
            $conditions[] = 'cmp_supprime_le IS NULL';
        }
        if (in_array('cmp_archive_le', $columns, true)) {
            $conditions[] = 'cmp_archive_le IS NULL';
        }

        if ($excludeId !== null) {
            $conditions[] = 'cmp_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql = 'SELECT COUNT(*) FROM sav_competences WHERE ' . implode(' AND ', $conditions);

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function createSkill(array $data): int
    {
        $payload = $this->filterColumns([
            'cmp_code' => strtoupper(trim((string) $data['code'])),
            'cmp_nom' => trim((string) $data['name']),
            'cmp_description' => $data['description'] ?? null,
            'cmp_societe_proprietaire_id' => $data['owner_company_id'] ?? null,
            'cmp_portee_code' => $data['scope_code'] ?? 'interne',
            'cmp_statut_id' => $data['status_id'] ?? $this->statusId('general', 'actif'),
            'cmp_cree_par_utilisateur_id' => $data['created_by'] ?? null,
            'cmp_modifie_par_utilisateur_id' => $data['created_by'] ?? null,
        ]);

        $this->insertDynamic('sav_competences', $payload);

        return (int) $this->db->lastInsertId();
    }

    public function updateSkill(int $id, array $data): bool
    {
        $payload = $this->filterColumns([
            'cmp_code' => strtoupper(trim((string) $data['code'])),
            'cmp_nom' => trim((string) $data['name']),
            'cmp_description' => $data['description'] ?? null,
            'cmp_portee_code' => $data['scope_code'] ?? 'interne',
            'cmp_statut_id' => $data['status_id'] ?? $this->statusId('general', 'actif'),
            'cmp_modifie_par_utilisateur_id' => $data['updated_by'] ?? null,
        ]);

        return $this->updateDynamic('sav_competences', 'cmp_id', $id, $payload);
    }

    public function setActive(int $id, bool $active, int $updatedBy): bool
    {
        return $this->db->execute(
            'UPDATE sav_competences
             SET cmp_statut_id = :status_id,
                 cmp_modifie_par_utilisateur_id = :updated_by
             WHERE cmp_id = :id
               AND cmp_supprime_le IS NULL
               AND cmp_archive_le IS NULL',
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
            'c.cmp_supprime_le IS NULL',
            'c.cmp_archive_le IS NULL',
            "(st.sta_code IS NULL OR st.sta_code = 'actif')",
            '(c.cmp_nom LIKE :q_name OR c.cmp_code LIKE :q_code)',
        ];
        $params = [];
        if ($companyId !== null) {
            $this->addScopeCondition($conditions, $params, 'c.cmp_societe_proprietaire_id', 'cmp_portee_code', $companyId, $networkOwnerCompanyIds);
        }
        $like = '%' . trim($query) . '%';
        $params['q_name']  = $like;
        $params['q_code']  = $like;
        $params['qlimit']  = max(1, min(50, $limit));

        $sql = $this->selectSql()
            . "
 WHERE " . implode(' AND ', $conditions)
            . "
 ORDER BY c.cmp_nom ASC LIMIT :qlimit";

        return $this->db->fetchAll($sql, $params);
    }

    private function selectSql(): string
    {
        $scopeExpr = $this->porteeSelect('c', 'cmp_portee_code');

        return "SELECT
                    c.*,
                    c.cmp_id AS skl_id,
                    c.cmp_code AS skl_code,
                    c.cmp_nom AS skl_name,
                    c.cmp_nom AS skl_label,
                    c.cmp_description AS skl_description,
                    c.cmp_societe_proprietaire_id AS skl_company_id,
                    c.cmp_societe_proprietaire_id AS skl_owner_company_id,
                    " . $scopeExpr . " AS skl_scope_code,
                    " . $scopeExpr . " AS cmp_portee_code,
                    c.cmp_statut_id AS skl_status_id,
                    c.cmp_cree_le AS skl_created_at,
                    c.cmp_modifie_le AS skl_updated_at,
                    c.cmp_supprime_le AS skl_deleted_at,
                    c.cmp_archive_le AS skl_archived_at,
                    c.cmp_cree_par_utilisateur_id AS skl_created_by,
                    c.cmp_modifie_par_utilisateur_id AS skl_updated_by,
                    CASE WHEN c.cmp_societe_proprietaire_id IS NULL THEN 1 ELSE 0 END AS skl_is_global,
                    CASE WHEN st.sta_code IS NULL OR st.sta_code = 'actif' THEN 1 ELSE 0 END AS skl_is_active,
                    CASE WHEN c.cmp_societe_proprietaire_id IS NULL THEN 'plateforme' ELSE " . $scopeExpr . " END AS skl_scope,
                    st.sta_code AS skl_status_code,
                    st.sta_libelle AS skl_status_label,
                    s.soc_nom AS skl_company_name,
                    CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS skl_creator_name
                FROM sav_competences c
                LEFT JOIN sav_statuts st ON st.sta_id = c.cmp_statut_id
                LEFT JOIN sav_societes s ON s.soc_id = c.cmp_societe_proprietaire_id
                LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = c.cmp_cree_par_utilisateur_id";
    }


    private function addScopeCondition(array &$conditions, array &$params, string $ownerColumn, string $scopeColumn, int $companyId, array $networkOwnerCompanyIds): void
    {
        $networkOwnerCompanyIds = array_values(array_unique(array_filter(array_map('intval', $networkOwnerCompanyIds), static fn(int $id): bool => $id > 0)));
        $scopeExpr = $this->porteeSelect('c', $scopeColumn);
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

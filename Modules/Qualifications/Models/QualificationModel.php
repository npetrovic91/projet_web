<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Qualifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use PDO;

/**
 * Modele de compatibilite Qualifications.
 *
 * Dans la base SQL actuelle, une qualification/certification est portee par
 * sav_certifications. Les alias qua_* evitent de casser les ecrans historiques.
 */
class QualificationModel extends BaseModel
{
    protected string $table = 'sav_certifications';
    protected string $colPrefix = 'cer_';

    public function getForContext(?int $companyId = null, bool $adminView = false, array $networkOwnerCompanyIds = []): array
    {
        $conditions = ['c.cer_supprime_le IS NULL', 'c.cer_archive_le IS NULL'];
        $params = [];

        if (!$adminView) {
            $conditions[] = "(st.sta_code IS NULL OR st.sta_code = 'actif')";
        }

        if ($companyId !== null) {
            $this->addScopeCondition($conditions, $params, 'c.cer_societe_proprietaire_id', 'cer_portee_code', $companyId, $networkOwnerCompanyIds);
        }

        return $this->db->fetchAll(
            $this->selectSql() . "
 WHERE " . implode(' AND ', $conditions) . "
 ORDER BY CASE WHEN c.cer_societe_proprietaire_id IS NULL THEN 0 ELSE 1 END, c.cer_nom ASC",
            $params
        );
    }

    public function findByIdFull(int $id): ?array
    {
        return $this->db->fetch(
            $this->selectSql() . "\n WHERE c.cer_id = :id AND c.cer_supprime_le IS NULL AND c.cer_archive_le IS NULL LIMIT 1",
            ['id' => $id]
        );
    }

    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $conditions = ['cer_code = :code'];
        $params = ['code' => strtoupper(trim($code))];
        $columns = $this->tableColumns();

        if (in_array('cer_supprime_le', $columns, true)) {
            $conditions[] = 'cer_supprime_le IS NULL';
        }
        if (in_array('cer_archive_le', $columns, true)) {
            $conditions[] = 'cer_archive_le IS NULL';
        }

        if ($excludeId !== null) {
            $conditions[] = 'cer_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $sql = 'SELECT COUNT(*) FROM sav_certifications WHERE ' . implode(' AND ', $conditions);

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function createQualification(array $data): int
    {
        $payload = $this->filterColumns([
            'cer_code' => strtoupper(trim((string) $data['code'])),
            'cer_nom' => trim((string) $data['name']),
            'cer_description' => $data['description'] ?? null,
            'cer_prerequis_json' => $data['prerequisites_json'] ?? null,
            'cer_societe_proprietaire_id' => $data['owner_company_id'] ?? null,
            'cer_portee_code' => $data['scope_code'] ?? 'interne',
            'cer_statut_id' => $data['status_id'] ?? $this->statusId('general', 'actif'),
            'cer_cree_par_utilisateur_id' => $data['created_by'] ?? null,
            'cer_modifie_par_utilisateur_id' => $data['created_by'] ?? null,
        ]);

        $this->insertDynamic('sav_certifications', $payload);
        return (int) $this->db->lastInsertId();
    }

    public function updateQualification(int $id, array $data): bool
    {
        $payload = $this->filterColumns([
            'cer_code' => strtoupper(trim((string) $data['code'])),
            'cer_nom' => trim((string) $data['name']),
            'cer_description' => $data['description'] ?? null,
            'cer_prerequis_json' => $data['prerequisites_json'] ?? null,
            'cer_portee_code' => $data['scope_code'] ?? 'interne',
            'cer_statut_id' => $data['status_id'] ?? $this->statusId('general', 'actif'),
            'cer_modifie_par_utilisateur_id' => $data['updated_by'] ?? null,
        ]);

        return $this->updateDynamic('sav_certifications', 'cer_id', $id, $payload);
    }

    public function setActive(int $id, bool $active, int $updatedBy): bool
    {
        return $this->db->execute(
            'UPDATE sav_certifications
             SET cer_statut_id = :status_id,
                 cer_modifie_par_utilisateur_id = :updated_by
             WHERE cer_id = :id
               AND cer_supprime_le IS NULL
               AND cer_archive_le IS NULL',
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
            'c.cer_supprime_le IS NULL',
            'c.cer_archive_le IS NULL',
            "(st.sta_code IS NULL OR st.sta_code = 'actif')",
            '(c.cer_nom LIKE :q_name OR c.cer_code LIKE :q_code)',
        ];
        $params = [];
        if ($companyId !== null) {
            $this->addScopeCondition($conditions, $params, 'c.cer_societe_proprietaire_id', 'cer_portee_code', $companyId, $networkOwnerCompanyIds);
        }
        $like = '%' . trim($query) . '%';
        $params['q_name']  = $like;
        $params['q_code']  = $like;
        $params['qlimit']  = max(1, min(50, $limit));

        $sql = $this->selectSql()
            . "
 WHERE " . implode(' AND ', $conditions)
            . "
 ORDER BY c.cer_nom ASC LIMIT :qlimit";

        return $this->db->fetchAll($sql, $params);
    }

    private function selectSql(): string
    {
        $scopeExpr = $this->porteeSelect('c', 'cer_portee_code');

        return "SELECT
                    c.*,
                    c.cer_id AS qua_id,
                    c.cer_code AS qua_code,
                    c.cer_nom AS qua_name,
                    c.cer_nom AS qua_label,
                    c.cer_description AS qua_description,
                    c.cer_prerequis_json AS qua_prerequisites,
                    c.cer_prerequis_json AS qua_prerequisites_json,
                    c.cer_societe_proprietaire_id AS qua_company_id,
                    c.cer_societe_proprietaire_id AS qua_owner_company_id,
                    " . $scopeExpr . " AS qua_scope_code,
                    " . $scopeExpr . " AS cer_portee_code,
                    c.cer_statut_id AS qua_status_id,
                    c.cer_cree_le AS qua_created_at,
                    c.cer_modifie_le AS qua_updated_at,
                    c.cer_supprime_le AS qua_deleted_at,
                    c.cer_archive_le AS qua_archived_at,
                    c.cer_cree_par_utilisateur_id AS qua_created_by,
                    c.cer_modifie_par_utilisateur_id AS qua_updated_by,
                    CASE WHEN c.cer_societe_proprietaire_id IS NULL THEN 1 ELSE 0 END AS qua_is_global,
                    CASE WHEN st.sta_code IS NULL OR st.sta_code = 'actif' THEN 1 ELSE 0 END AS qua_is_active,
                    CASE WHEN c.cer_societe_proprietaire_id IS NULL THEN 'plateforme' ELSE " . $scopeExpr . " END AS qua_scope,
                    st.sta_code AS qua_status_code,
                    st.sta_libelle AS qua_status_label,
                    s.soc_nom AS qua_company_name,
                    CONCAT(COALESCE(p.pui_prenom, ''), ' ', COALESCE(p.pui_nom, '')) AS qua_creator_name
                FROM sav_certifications c
                LEFT JOIN sav_statuts st ON st.sta_id = c.cer_statut_id
                LEFT JOIN sav_societes s ON s.soc_id = c.cer_societe_proprietaire_id
                LEFT JOIN sav_profils_utilisateurs p ON p.pui_utilisateur_id = c.cer_cree_par_utilisateur_id";
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

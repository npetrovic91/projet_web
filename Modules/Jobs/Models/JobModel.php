<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use PDO;

/**
 * Compatibilite Jobs.
 *
 * Le PROJET.md valide que Fonction = metier reel. Le dump SQL ne contient pas de table metiers separee : les metiers sont lus/ecrits dans sav_fonctions.
 */
class JobModel extends BaseModel
{
    protected string $table = 'sav_fonctions';
    protected string $colPrefix = 'fon_';

    public function getForContext(?int $companyTypeId = null, ?int $companyId = null, bool $adminView = false): array
    {
        $conditions = ['f.fon_supprime_le IS NULL', 'f.fon_archive_le IS NULL'];
        $params = [];

        if (!$adminView) {
            $conditions[] = "(st.sta_code IS NULL OR st.sta_code = 'actif')";
        }
        if ($companyTypeId !== null) {
            $conditions[] = '(f.fon_type_societe_id IS NULL OR f.fon_type_societe_id = :company_type_id)';
            $params['company_type_id'] = $companyTypeId;
        }
        if ($companyId !== null) {
            $conditions[] = '(f.fon_societe_proprietaire_id IS NULL OR f.fon_societe_proprietaire_id = :company_id)';
            $params['company_id'] = $companyId;
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

    public function codeExists(string $code, ?int $companyId, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM sav_fonctions
                WHERE fon_code = :code
                  AND fon_supprime_le IS NULL
                  AND fon_archive_le IS NULL';
        $params = ['code' => strtoupper(trim($code))];
        if ($excludeId !== null) {
            $sql .= ' AND fon_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function createJob(array $data): int
    {
        $this->db->execute(
            'INSERT INTO sav_fonctions
                (fon_code, fon_nom, fon_description, fon_societe_proprietaire_id,
                 fon_type_societe_id, fon_statut_id, fon_cree_par_utilisateur_id,
                 fon_modifie_par_utilisateur_id)
             VALUES
                (:code, :label, :description, :company_id,
                 :company_type_id, :status_id, :created_by, :created_by)',
            [
                'code' => strtoupper(trim((string) $data['code'])),
                'label' => trim((string) $data['label']),
                'description' => $data['description'] ?? null,
                'company_id' => $data['company_id'] ?? null,
                'company_type_id' => $data['company_type_id'] ?? null,
                'status_id' => $data['status_id'] ?? $this->statusId('general', 'actif'),
                'created_by' => $data['created_by'] ?? null,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function updateJob(int $id, array $data): bool
    {
        return $this->db->execute(
            'UPDATE sav_fonctions
             SET fon_code = :code,
                 fon_nom = :label,
                 fon_description = :description,
                 fon_type_societe_id = :company_type_id,
                 fon_statut_id = :status_id,
                 fon_modifie_par_utilisateur_id = :updated_by
             WHERE fon_id = :id
               AND fon_supprime_le IS NULL
               AND fon_archive_le IS NULL',
            [
                'id' => $id,
                'code' => strtoupper(trim((string) $data['code'])),
                'label' => trim((string) $data['label']),
                'description' => $data['description'] ?? null,
                'company_type_id' => $data['company_type_id'] ?? null,
                'status_id' => $data['status_id'] ?? $this->statusId('general', 'actif'),
                'updated_by' => $data['updated_by'] ?? null,
            ]
        );
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

    public function search(string $query, ?int $companyTypeId, ?int $companyId, int $limit = 20): array
    {
        $conditions = [
            'f.fon_supprime_le IS NULL',
            'f.fon_archive_le IS NULL',
            "(st.sta_code IS NULL OR st.sta_code = 'actif')",
            '(f.fon_nom LIKE :q1 OR f.fon_code LIKE :q2)',
        ];
        $params = [];
        if ($companyTypeId !== null) {
            $conditions[] = '(f.fon_type_societe_id IS NULL OR f.fon_type_societe_id = :company_type_id)';
            $params['company_type_id'] = $companyTypeId;
        }
        if ($companyId !== null) {
            $conditions[] = '(f.fon_societe_proprietaire_id IS NULL OR f.fon_societe_proprietaire_id = :company_id)';
            $params['company_id'] = $companyId;
        }

        $sql = $this->selectSql() . '
            WHERE ' . implode(' AND ', $conditions) . '
            ORDER BY CASE WHEN f.fon_societe_proprietaire_id IS NULL THEN 0 ELSE 1 END, f.fon_nom ASC
            LIMIT :limit';

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $like = '%' . trim($query) . '%';
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function companyTypes(): array
    {
        return $this->db->fetchAll(
            'SELECT tso_id, tso_code, tso_nom
             FROM sav_types_societes
             WHERE tso_supprime_le IS NULL AND tso_archive_le IS NULL
             ORDER BY tso_nom ASC'
        );
    }

    public function statusId(string $domain, string $code): ?int
    {
        $id = $this->db->fetchColumn(
            'SELECT sta_id FROM sav_statuts
             WHERE sta_domaine = :domain AND sta_code = :code AND sta_supprime_le IS NULL
             LIMIT 1',
            ['domain' => $domain, 'code' => $code]
        );
        return $id !== false && $id !== null ? (int) $id : null;
    }

    private function selectSql(): string
    {
        return "SELECT
                    f.*,
                    f.fon_id AS job_id,
                    f.fon_code AS job_code,
                    f.fon_nom AS job_label,
                    f.fon_nom AS job_name,
                    f.fon_description AS job_description,
                    f.fon_societe_proprietaire_id AS job_company_id,
                    f.fon_type_societe_id AS job_company_type_id,
                    f.fon_statut_id AS job_status_id,
                    f.fon_cree_le AS job_created_at,
                    f.fon_modifie_le AS job_updated_at,
                    CASE WHEN f.fon_societe_proprietaire_id IS NULL THEN 1 ELSE 0 END AS job_is_global,
                    CASE WHEN st.sta_code IS NULL OR st.sta_code = 'actif' THEN 1 ELSE 0 END AS job_is_active,
                    st.sta_code AS job_status_code,
                    st.sta_libelle AS job_status_label,
                    s.soc_nom AS job_company_name,
                    t.tso_code AS job_company_type_code,
                    t.tso_nom AS job_company_type_name,
                    f.fon_id AS fnc_id,
                    f.fon_code AS fnc_code,
                    f.fon_nom AS fnc_label,
                    f.fon_nom AS fnc_name,
                    f.fon_description AS fnc_description,
                    CASE WHEN f.fon_societe_proprietaire_id IS NULL THEN 1 ELSE 0 END AS fnc_is_global,
                    CASE WHEN st.sta_code IS NULL OR st.sta_code = 'actif' THEN 1 ELSE 0 END AS fnc_is_active
                FROM sav_fonctions f
                LEFT JOIN sav_statuts st ON st.sta_id = f.fon_statut_id
                LEFT JOIN sav_societes s ON s.soc_id = f.fon_societe_proprietaire_id
                LEFT JOIN sav_types_societes t ON t.tso_id = f.fon_type_societe_id";
    }
}

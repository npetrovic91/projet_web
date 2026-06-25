<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Brands\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use Nenad\Autosav\Modules\Society\Models\ModeleTypeSociete;

/**
 * Les marques ne sont pas une table isolée :
 * elles sont des sociétés typées « marque » dans sav_societes.
 *
 * CORRECTIF FONC (BrandModel::paginate) :
 *   La version précédente construisait deux clauses WHERE distinctes :
 *     • $whereSql   pour le COUNT(*) — incluant isBrandExistsSql()
 *     • $searchSql  pour le SELECT  — reconstruite différemment
 *   Si l'une des deux changeait (ajout d'un filtre, refactor), l'autre
 *   ne changeait pas → pagination incohérente (total vs résultats).
 *
 *   Correction : buildBrandWhere() est la source unique de vérité.
 *   COUNT et SELECT utilisent exactement le même tableau de conditions
 *   et le même tableau de paramètres.
 */
class BrandModel extends BaseModel
{
    protected string $table     = 'sav_societes';
    protected string $colPrefix = 'soc_';

    // ----------------------------------------------------------------
    // LECTURE
    // ----------------------------------------------------------------

    public function allActive(): array
    {
        return $this->db()->fetchAll(
            $this->selectBrandsSql('AND s.soc_statut_id = 1 ORDER BY s.soc_nom ASC')
        );
    }

    /**
     * Pagination cohérente : COUNT et SELECT partagent buildBrandWhere().
     */
    public function paginate(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        [$conditions, $params] = $this->buildBrandWhere($filters);
        $whereSql = implode(' AND ', $conditions);

        // COUNT — même WHERE que le SELECT
        $total = (int) ($this->db()->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_societes s WHERE {$whereSql}",
            $params
        )['cnt'] ?? 0);

        $pages  = max(1, (int) ceil($total / max(1, $perPage)));
        $page   = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        // SELECT — même WHERE, conditions injectées dans selectBrandsSql()
        $rows = $this->db()->fetchAll(
            "SELECT
                s.*,
                s.soc_id   AS brd_id,
                s.soc_uuid AS brd_uuid,
                s.soc_code AS brd_code,
                s.soc_nom  AS brd_name,
                NULL AS brd_logo_url,
                IF(s.soc_statut_id = 1 AND s.soc_supprime_le IS NULL AND s.soc_archive_le IS NULL, 1, 0) AS brd_is_active,
                s.soc_supprime_le AS brd_deleted_at
             FROM sav_societes s
             WHERE {$whereSql}
             ORDER BY s.soc_nom ASC
             LIMIT :limit OFFSET :offset",
            array_merge($params, ['limit' => $perPage, 'offset' => $offset])
        );

        return [
            'rows'    => $rows,
            'total'   => $total,
            'page'    => $page,
            'pages'   => $pages,
            'perPage' => $perPage,
        ];
    }

    public function find(int $id): ?array
    {
        return $this->db()->fetch(
            $this->selectBrandsSql('AND s.soc_id = :id LIMIT 1'),
            ['id' => $id]
        );
    }

    // ----------------------------------------------------------------
    // ÉCRITURE
    // ----------------------------------------------------------------

    public function create(array $data): int
    {
        return (int) $this->db()->transaction(function () use ($data): int {
            $typeId = (new ModeleTypeSociete())->trouverOuCreerParCode(
                'marque', 'Marque', (int) ($data['user_id'] ?? 0)
            );

            // Placeholders nommés distincts — HY093 évité avec EMULATE_PREPARES=false
            $this->db()->execute(
                "INSERT INTO sav_societes
                 (soc_uuid, soc_code, soc_nom, soc_nom_legal, soc_pays_id, soc_statut_id,
                  soc_cree_le, soc_cree_par_utilisateur_id, soc_modifie_par_utilisateur_id)
                 VALUES (:uuid, :code, :name, :name_legal, 1, :statut_id, NOW(), :created_by, :updated_by)",
                [
                    'uuid'       => $data['uuid']      ?? null,
                    'code'       => $data['code']      ?? null,
                    'name'       => $data['name']      ?? null,
                    'name_legal' => $data['name_legal'] ?? $data['name'] ?? null,
                    'statut_id'  => $data['statut_id'] ?? 1,
                    'created_by' => $data['user_id']   ?? null,
                    'updated_by' => $data['user_id']   ?? null,
                ]
            );

            $id = (int) $this->db()->lastInsertId();

            $this->db()->execute(
                "INSERT INTO sav_affectations_types_societes
                 (ats_societe_id, ats_type_societe_id, ats_debute_le, ats_statut_id, ats_cree_le, ats_cree_par_utilisateur_id)
                 VALUES (:societe, :type, CURDATE(), 1, NOW(), :user_id)",
                ['societe' => $id, 'type' => $typeId, 'user_id' => $data['user_id'] ?? null]
            );

            return $id;
        });
    }

    public function updateBrand(int $id, array $data): bool
    {
        return $this->db()->execute(
            "UPDATE sav_societes
             SET soc_code = :code,
                 soc_nom = :name,
                 soc_nom_legal = :name_legal,
                 soc_statut_id = :statut_id,
                 soc_modifie_par_utilisateur_id = :user_id,
                 soc_modifie_le = NOW()
             WHERE soc_id = :id
               AND soc_supprime_le IS NULL",
            [
                'id'        => $id,
                'code'      => $data['code']       ?? null,
                'name'      => $data['name']       ?? null,
                'name_legal'=> $data['name_legal'] ?? $data['name'] ?? null,
                'statut_id' => $data['statut_id']  ?? 1,
                'user_id'   => $data['user_id']    ?? null,
            ]
        );
    }

    public function attachToCompany(int $companyId, int $brandId, int $userId, bool $primary = false): bool
    {
        $existing = $this->db()->fetch(
            "SELECT rma_id FROM sav_representations_marques_societes
              WHERE rma_concession_societe_id = :company_id
                AND rma_marque_societe_id     = :brand_id
                AND rma_supprime_le IS NULL
              LIMIT 1",
            ['company_id' => $companyId, 'brand_id' => $brandId]
        );

        if ($existing) {
            return true; // lien déjà existant
        }

        return $this->db()->execute(
            "INSERT INTO sav_representations_marques_societes
             (rma_concession_societe_id, rma_marque_societe_id, rma_debute_le,
              rma_est_principale, rma_cree_le, rma_cree_par_utilisateur_id)
             VALUES (:company_id, :brand_id, CURDATE(), :primary, NOW(), :user_id)",
            [
                'company_id' => $companyId,
                'brand_id'   => $brandId,
                'primary'    => $primary ? 1 : 0,
                'user_id'    => $userId,
            ]
        );
    }

    public function detachFromCompany(int $companyId, int $brandId, int $userId): bool
    {
        return $this->db()->execute(
            "UPDATE sav_representations_marques_societes
             SET rma_archive_le = NOW(),
                 rma_modifie_par_utilisateur_id = :user_id,
                 rma_archive_par_utilisateur_id = :user_id
             WHERE rma_concession_societe_id = :company_id
               AND rma_marque_societe_id     = :brand_id
               AND rma_supprime_le IS NULL",
            ['company_id' => $companyId, 'brand_id' => $brandId, 'user_id' => $userId]
        );
    }

    // ----------------------------------------------------------------
    // HELPERS PRIVÉS
    // ----------------------------------------------------------------

    /**
     * Source unique de vérité pour la clause WHERE des marques.
     * Utilisée par COUNT et SELECT dans paginate().
     *
     * @param  array $filters Filtres facultatifs (search, statut_id)
     * @return array{0: string[], 1: array<string, mixed>} [conditions[], params[]]
     */
    private function buildBrandWhere(array $filters): array
    {
        $conditions = [
            's.soc_supprime_le IS NULL',
            's.soc_archive_le IS NULL',
            $this->isBrandExistsSql(),
        ];
        $params = [];

        if (!empty($filters['search'])) {
            // B-03 : avec ATTR_EMULATE_PREPARES=false, un paramètre nommé ne peut
            // pas apparaître plusieurs fois dans le même statement natif.
            // Solution : trois placeholders distincts avec la même valeur.
            $conditions[] = '(s.soc_nom LIKE :search1 OR s.soc_code LIKE :search2 OR s.soc_nom_legal LIKE :search3)';
            $searchVal = '%' . trim((string) $filters['search']) . '%';
            $params['search1'] = $searchVal;
            $params['search2'] = $searchVal;
            $params['search3'] = $searchVal;
        }

        if (!empty($filters['statut_id'])) {
            $conditions[] = 's.soc_statut_id = :statut_id';
            $params['statut_id'] = (int) $filters['statut_id'];
        }

        return [$conditions, $params];
    }

    private function isBrandExistsSql(): string
    {
        return "EXISTS (
            SELECT 1
            FROM sav_affectations_types_societes ats
            INNER JOIN sav_types_societes t ON t.tso_id = ats.ats_type_societe_id
            WHERE ats.ats_societe_id = s.soc_id
              AND ats.ats_supprime_le IS NULL
              AND ats.ats_archive_le IS NULL
              AND LOWER(t.tso_code) = 'marque'
        )";
    }

    private function selectBrandsSql(string $suffix = ''): string
    {
        return "SELECT
                s.*,
                s.soc_id   AS brd_id,
                s.soc_uuid AS brd_uuid,
                s.soc_code AS brd_code,
                s.soc_nom  AS brd_name,
                NULL AS brd_logo_url,
                IF(s.soc_statut_id = 1 AND s.soc_supprime_le IS NULL AND s.soc_archive_le IS NULL, 1, 0) AS brd_is_active,
                s.soc_supprime_le AS brd_deleted_at
             FROM sav_societes s
             WHERE s.soc_supprime_le IS NULL
               AND s.soc_archive_le IS NULL
               AND {$this->isBrandExistsSql()}
             {$suffix}";
    }
}
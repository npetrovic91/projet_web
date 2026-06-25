<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Functions\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Affectations utilisateur/fonction sur la table SQL reelle sav_fonctions_utilisateurs.
 */
class UserFunctionModel extends BaseModel
{
    protected string $table = 'sav_fonctions_utilisateurs';
    protected string $colPrefix = 'fut_';

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT fut.*,
                    f.fon_code,
                    f.fon_nom,
                    f.fon_description,
                    f.fon_nom AS fnc_name,
                    f.fon_nom AS fnc_label,
                    f.fon_code AS fnc_code,
                    f.fon_id AS fnc_id,
                    f.fon_nom AS job_name,
                    f.fon_nom AS job_label,
                    f.fon_code AS job_code,
                    f.fon_id AS job_id,
                    s.soc_nom AS societe_nom,
                    ms.soc_nom AS marque_nom,
                    cs.soc_nom AS concession_nom
             FROM sav_fonctions_utilisateurs fut
             INNER JOIN sav_fonctions f ON f.fon_id = fut.fut_fonction_id
             LEFT JOIN sav_societes s ON s.soc_id = fut.fut_societe_id
             LEFT JOIN sav_societes ms ON ms.soc_id = fut.fut_marque_societe_id
             LEFT JOIN sav_societes cs ON cs.soc_id = fut.fut_concession_societe_id
             LEFT JOIN sav_statuts st ON st.sta_id = fut.fut_statut_id
             WHERE fut.fut_utilisateur_id = :user_id
               AND fut.fut_supprime_le IS NULL
               AND fut.fut_archive_le IS NULL
               AND f.fon_supprime_le IS NULL
               AND f.fon_archive_le IS NULL
               AND (st.sta_code IS NULL OR st.sta_code = 'actif')
             ORDER BY f.fon_nom ASC",
            ['user_id' => $userId]
        );
    }

    public function hasFunction(int $userId, int $functionId, ?int $companyId = null): bool
    {
        $sql = 'SELECT COUNT(*)
                FROM sav_fonctions_utilisateurs
                WHERE fut_utilisateur_id = :user_id
                  AND fut_fonction_id = :function_id
                  AND fut_supprime_le IS NULL
                  AND fut_archive_le IS NULL';
        $params = ['user_id' => $userId, 'function_id' => $functionId];
        if ($companyId !== null) {
            $sql .= ' AND fut_societe_id = :company_id';
            $params['company_id'] = $companyId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function assign(int $userId, int $functionId, ?int $companyId, bool $isPrimary, int $createdBy): int
    {
        $this->db->execute(
            'INSERT INTO sav_fonctions_utilisateurs
                (fut_utilisateur_id, fut_societe_id, fut_fonction_id, fut_debute_le,
                 fut_statut_id, fut_cree_par_utilisateur_id)
             VALUES
                (:user_id, :company_id, :function_id, CURDATE(), :status_id, :created_by)',
            [
                'user_id' => $userId,
                'company_id' => $companyId,
                'function_id' => $functionId,
                'status_id' => $this->statusId('general', 'actif'),
                'created_by' => $createdBy,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function unassign(int $userId, int $functionId, ?int $operatorId = null): bool
    {
        return $this->db->execute(
            'UPDATE sav_fonctions_utilisateurs
             SET fut_archive_le = NOW(),
                 fut_archive_par_utilisateur_id = :operator_id
             WHERE fut_utilisateur_id = :user_id
               AND fut_fonction_id = :function_id
               AND fut_supprime_le IS NULL
               AND fut_archive_le IS NULL',
            ['user_id' => $userId, 'function_id' => $functionId, 'operator_id' => $operatorId]
        );
    }

    public function sync(int $userId, array $functionIds, int $primaryFunctionId, int $createdBy, ?int $companyId = null): void
    {
        $this->db->transaction(function () use ($userId, $functionIds, $createdBy, $companyId): void {
            $this->db->execute(
                'UPDATE sav_fonctions_utilisateurs
                 SET fut_archive_le = NOW(), fut_archive_par_utilisateur_id = :created_by
                 WHERE fut_utilisateur_id = :user_id
                   AND fut_archive_le IS NULL
                   AND fut_supprime_le IS NULL',
                ['user_id' => $userId, 'created_by' => $createdBy]
            );

            foreach (array_values(array_unique(array_map('intval', $functionIds))) as $functionId) {
                if ($functionId <= 0) {
                    continue;
                }
                $this->assign($userId, $functionId, $companyId, false, $createdBy);
            }
        });
    }
}

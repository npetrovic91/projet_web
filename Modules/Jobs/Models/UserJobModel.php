<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Models;

use Nenad\Autosav\Core\Model\BaseModel;

/**
 * Compatibilite Jobs -> sav_fonctions_utilisateurs.
 */
class UserJobModel extends BaseModel
{
    protected string $table = 'sav_fonctions_utilisateurs';
    protected string $colPrefix = 'fut_';

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT fut.*,
                    f.fon_id AS job_id,
                    f.fon_code AS job_code,
                    f.fon_nom AS job_label,
                    f.fon_nom AS job_name,
                    f.fon_description AS job_description,
                    f.fon_id AS fnc_id,
                    f.fon_code AS fnc_code,
                    f.fon_nom AS fnc_label,
                    s.soc_nom AS societe_nom
             FROM sav_fonctions_utilisateurs fut
             INNER JOIN sav_fonctions f ON f.fon_id = fut.fut_fonction_id
             LEFT JOIN sav_societes s ON s.soc_id = fut.fut_societe_id
             LEFT JOIN sav_statuts st ON st.sta_id = fut.fut_statut_id
             WHERE fut.fut_utilisateur_id = :user_id
               AND fut.fut_supprime_le IS NULL
               AND fut.fut_archive_le IS NULL
               AND (st.sta_code IS NULL OR st.sta_code = 'actif')
             ORDER BY f.fon_nom ASC",
            ['user_id' => $userId]
        );
    }

    public function hasJob(int $userId, int $jobId, ?int $companyId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM sav_fonctions_utilisateurs
                WHERE fut_utilisateur_id = :user_id
                  AND fut_fonction_id = :job_id
                  AND fut_supprime_le IS NULL
                  AND fut_archive_le IS NULL';
        $params = ['user_id' => $userId, 'job_id' => $jobId];
        if ($companyId !== null) {
            $sql .= ' AND fut_societe_id = :company_id';
            $params['company_id'] = $companyId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function assign(int $userId, int $jobId, ?int $companyTypeId, ?int $companyId, bool $isPrimary, int $createdBy): int
    {
        $this->db->execute(
            'INSERT INTO sav_fonctions_utilisateurs
                (fut_utilisateur_id, fut_societe_id, fut_fonction_id, fut_debute_le,
                 fut_statut_id, fut_cree_par_utilisateur_id)
             VALUES
                (:user_id, :company_id, :job_id, CURDATE(), :status_id, :created_by)',
            [
                'user_id' => $userId,
                'company_id' => $companyId,
                'job_id' => $jobId,
                'status_id' => $this->statusId('general', 'actif'),
                'created_by' => $createdBy,
            ]
        );
        return (int) $this->db->lastInsertId();
    }

    public function unassign(int $userId, int $jobId, ?int $operatorId = null): bool
    {
        return $this->db->execute(
            'UPDATE sav_fonctions_utilisateurs
             SET fut_archive_le = NOW(), fut_archive_par_utilisateur_id = :operator_id
             WHERE fut_utilisateur_id = :user_id
               AND fut_fonction_id = :job_id
               AND fut_archive_le IS NULL
               AND fut_supprime_le IS NULL',
            ['user_id' => $userId, 'job_id' => $jobId, 'operator_id' => $operatorId]
        );
    }

    public function sync(int $userId, array $jobIds, int $primaryJobId, int $createdBy, ?int $companyId = null): void
    {
        $this->db->transaction(function () use ($userId, $jobIds, $createdBy, $companyId): void {
            $this->db->execute(
                'UPDATE sav_fonctions_utilisateurs
                 SET fut_archive_le = NOW(), fut_archive_par_utilisateur_id = :created_by
                 WHERE fut_utilisateur_id = :user_id
                   AND fut_archive_le IS NULL
                   AND fut_supprime_le IS NULL',
                ['user_id' => $userId, 'created_by' => $createdBy]
            );
            foreach (array_values(array_unique(array_map('intval', $jobIds))) as $jobId) {
                if ($jobId > 0) {
                    $this->assign($userId, $jobId, null, $companyId, false, $createdBy);
                }
            }
        });
    }

    private function statusId(string $domain, string $code): ?int
    {
        $id = $this->db->fetchColumn(
            'SELECT sta_id FROM sav_statuts
             WHERE sta_domaine = :domain AND sta_code = :code AND sta_supprime_le IS NULL LIMIT 1',
            ['domain' => $domain, 'code' => $code]
        );
        return $id !== false && $id !== null ? (int) $id : null;
    }
}

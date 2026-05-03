<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class UserJobModel extends BaseModel
{
    protected string $table = 'sav_user_jobs';

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT uj.*, j.job_code, j.job_label, j.job_is_global, c.com_name AS context_company_name
             FROM sav_user_jobs uj
             INNER JOIN sav_jobs j ON j.job_id = uj.ujb_job_id
             LEFT JOIN sav_companies c ON c.com_id = uj.ujb_company_id
             WHERE uj.ujb_user_id = :user_id
             ORDER BY uj.ujb_is_primary DESC, j.job_label ASC",
            ['user_id' => $userId]
        );
    }

    public function hasJob(int $userId, int $jobId, ?int $companyId = null): bool
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM sav_user_jobs
             WHERE ujb_user_id = :user_id
               AND ujb_job_id = :job_id
               AND ((ujb_company_id IS NULL AND :company_null = 1) OR ujb_company_id = :company_id)",
            ['user_id' => $userId, 'job_id' => $jobId, 'company_null' => $companyId === null ? 1 : 0, 'company_id' => $companyId]
        ) > 0;
    }

    public function assign(int $userId, int $jobId, ?int $companyTypeId, ?int $companyId, bool $isPrimary, int $createdBy): bool
    {
        return $this->db->transaction(function () use ($userId, $jobId, $companyTypeId, $companyId, $isPrimary, $createdBy): bool {
            if ($isPrimary) {
                $this->db->execute('UPDATE sav_user_jobs SET ujb_is_primary = 0 WHERE ujb_user_id = :user_id', ['user_id' => $userId]);
            }
            return $this->db->execute(
                "INSERT INTO sav_user_jobs
                    (ujb_user_id, ujb_job_id, ujb_company_id, ujb_company_type_id, ujb_is_primary, ujb_created_by, ujb_created_at)
                 VALUES
                    (:user_id, :job_id, :company_id, :company_type_id, :is_primary, :created_by, NOW())
                 ON DUPLICATE KEY UPDATE
                    ujb_is_primary = VALUES(ujb_is_primary),
                    ujb_created_by = VALUES(ujb_created_by)",
                [
                    'user_id' => $userId,
                    'job_id' => $jobId,
                    'company_id' => $companyId,
                    'company_type_id' => $companyTypeId,
                    'is_primary' => $isPrimary,
                    'created_by' => $createdBy,
                ]
            );
        });
    }

    public function unassign(int $userId, int $jobId): bool
    {
        return $this->db->execute(
            'DELETE FROM sav_user_jobs WHERE ujb_user_id = :user_id AND ujb_job_id = :job_id',
            ['user_id' => $userId, 'job_id' => $jobId]
        );
    }

    public function sync(int $userId, array $jobIds, int $primaryJobId, int $createdBy): void
    {
        $this->db->transaction(function () use ($userId, $jobIds, $primaryJobId, $createdBy): void {
            $this->db->execute('DELETE FROM sav_user_jobs WHERE ujb_user_id = :user_id', ['user_id' => $userId]);
            foreach (array_values(array_unique(array_map('intval', $jobIds))) as $jobId) {
                $this->assign($userId, $jobId, null, null, $jobId === $primaryJobId, $createdBy);
            }
        });
    }
}

<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class JobCompanyTypeModel extends BaseModel
{
    protected string $table = 'sav_job_company_types';

    public function syncTypes(int $jobId, array $typeIds, int $createdBy): void
    {
        $this->db->transaction(function () use ($jobId, $typeIds, $createdBy): void {
            $this->db->execute('DELETE FROM sav_job_company_types WHERE jct_job_id = :job_id', ['job_id' => $jobId]);
            foreach (array_values(array_unique(array_map('intval', $typeIds))) as $typeId) {
                $this->db->execute(
                    "INSERT INTO sav_job_company_types
                        (jct_job_id, jct_company_type_id, jct_is_active, jct_created_by, jct_created_at)
                     VALUES
                        (:job_id, :type_id, 1, :created_by, NOW())",
                    ['job_id' => $jobId, 'type_id' => $typeId, 'created_by' => $createdBy]
                );
            }
        });
    }

    public function isAllowedForType(int $jobId, int $companyTypeId): bool
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM sav_job_company_types
             WHERE jct_job_id = :job_id
               AND jct_company_type_id = :type_id
               AND jct_is_active = 1",
            ['job_id' => $jobId, 'type_id' => $companyTypeId]
        ) > 0;
    }
}

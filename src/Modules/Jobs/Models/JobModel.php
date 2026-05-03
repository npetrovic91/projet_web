<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Jobs\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use PDO;

class JobModel extends BaseModel
{
    protected string $table = 'sav_jobs';

    public function getForContext(?int $companyTypeId = null, ?int $companyId = null, bool $adminView = false): array
    {
        $conditions = $adminView ? ['1 = 1'] : ['j.job_is_active = 1'];
        $params = [];

        if ($companyTypeId !== null) {
            $conditions[] = "(j.job_is_global = 1 OR EXISTS (
                SELECT 1 FROM sav_job_company_types jct
                WHERE jct.jct_job_id = j.job_id
                  AND jct.jct_company_type_id = :company_type_id
                  AND jct.jct_is_active = 1
            ))";
            $params['company_type_id'] = $companyTypeId;
        }
        if ($companyId !== null) {
            $conditions[] = '(j.job_is_global = 1 OR j.job_company_id = :company_id)';
            $params['company_id'] = $companyId;
        }

        return $this->db->fetchAll(
            "SELECT j.*, c.com_name AS job_company_name
             FROM sav_jobs j
             LEFT JOIN sav_companies c ON c.com_id = j.job_company_id
             WHERE " . implode(' AND ', $conditions) . "
             ORDER BY j.job_is_global DESC, j.job_label ASC",
            $params
        );
    }

    public function findByIdFull(int $id): ?array
    {
        $job = $this->db->fetch(
            "SELECT j.*, c.com_name AS job_company_name
             FROM sav_jobs j
             LEFT JOIN sav_companies c ON c.com_id = j.job_company_id
             WHERE j.job_id = :id",
            ['id' => $id]
        );
        if (!$job) {
            return null;
        }
        $job['allowed_company_types'] = $this->db->fetchAll(
            "SELECT jct.jct_company_type_id, ct.cty_code, ct.cty_label
             FROM sav_job_company_types jct
             INNER JOIN sav_company_types ct ON ct.cty_id = jct.jct_company_type_id
             WHERE jct.jct_job_id = :id AND jct.jct_is_active = 1",
            ['id' => $id]
        );
        return $job;
    }

    public function codeExists(string $code, ?int $companyId, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM sav_jobs WHERE job_code = :code";
        $params = ['code' => strtoupper(trim($code))];
        if ($excludeId !== null) {
            $sql .= ' AND job_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }
        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function createJob(array $data): int
    {
        $this->db->execute(
            "INSERT INTO sav_jobs
                (job_uuid, job_code, job_label, job_description, job_company_id,
                 job_is_global, job_is_active, job_created_by, job_updated_by,
                 job_created_at, job_updated_at)
             VALUES
                (:uuid, :code, :label, :description, :company_id,
                 :is_global, 1, :created_by, :created_by, NOW(), NOW())",
            $data
        );
        return (int) $this->db->lastInsertId();
    }

    public function updateJob(int $id, array $data): bool
    {
        return $this->db->execute(
            "UPDATE sav_jobs
             SET job_code = :code,
                 job_label = :label,
                 job_description = :description,
                 job_updated_by = :updated_by,
                 job_updated_at = NOW()
             WHERE job_id = :id",
            ['id' => $id] + $data
        );
    }

    public function setActive(int $id, bool $active, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_jobs
             SET job_is_active = :active,
                 job_updated_by = :updated_by,
                 job_updated_at = NOW()
             WHERE job_id = :id",
            ['active' => $active, 'updated_by' => $updatedBy, 'id' => $id]
        );
    }

    public function search(string $query, ?int $companyTypeId, ?int $companyId, int $limit = 20): array
    {
        $sql = "SELECT DISTINCT j.job_id, j.job_code, j.job_label, j.job_is_global
                FROM sav_jobs j
                LEFT JOIN sav_job_company_types jct ON jct.jct_job_id = j.job_id AND jct.jct_is_active = 1
                WHERE j.job_is_active = 1
                  AND (j.job_is_global = 1 OR j.job_company_id = :company_id OR :company_null = 1 OR jct.jct_company_type_id = :company_type_id)
                  AND (j.job_label LIKE :q1 OR j.job_code LIKE :q2)
                ORDER BY j.job_is_global DESC, j.job_label ASC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $like = '%' . trim($query) . '%';
        $stmt->bindValue(':company_id', $companyId, $companyId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':company_null', $companyId === null ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':company_type_id', $companyTypeId, $companyTypeId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

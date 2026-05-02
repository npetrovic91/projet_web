<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class UserCompanyHistoryModel extends BaseModel
{
    protected string $table = 'sav_user_company_history';

    public function getUserHistory(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT h.uch_id, h.uch_user_id, h.uch_company_id, h.uch_job_title,
                    h.uch_started_at, h.uch_ended_at, h.uch_departure_reason,
                    h.uch_notes, h.uch_created_at, c.com_name, ct.cty_label AS type_label
             FROM sav_user_company_history h
             INNER JOIN sav_companies c ON c.com_id = h.uch_company_id
             INNER JOIN sav_company_types ct ON ct.cty_id = c.com_type_id
             WHERE h.uch_user_id = :user_id
             ORDER BY h.uch_started_at DESC, h.uch_id DESC",
            ['user_id' => $userId]
        );
    }

    public function addEntry(array $data): int
    {
        $this->db->execute(
            "INSERT INTO sav_user_company_history
                (uch_user_id, uch_company_id, uch_job_title, uch_started_at,
                 uch_ended_at, uch_departure_reason, uch_notes, uch_created_by,
                 uch_updated_by, uch_created_at, uch_updated_at)
             VALUES
                (:user_id, :company_id, :job_title, :started_at,
                 :ended_at, :departure_reason, :notes, :created_by,
                 :created_by, NOW(), NOW())",
            [
                'user_id' => $data['user_id'],
                'company_id' => $data['company_id'],
                'job_title' => $data['job_title'] ?? null,
                'started_at' => $data['started_at'] ?? date('Y-m-d'),
                'ended_at' => $data['ended_at'] ?? null,
                'departure_reason' => $data['departure_reason'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'] ?? null,
            ]
        );

        return (int) $this->db->lastInsertId();
    }

    public function closeActiveEntry(int $userId, int $companyId, string $endedAt, string $reason, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_user_company_history
             SET uch_ended_at = :ended_at,
                 uch_departure_reason = :reason,
                 uch_updated_by = :updated_by,
                 uch_updated_at = NOW()
             WHERE uch_user_id = :user_id
               AND uch_company_id = :company_id
               AND uch_ended_at IS NULL",
            [
                'ended_at' => $endedAt,
                'reason' => $reason,
                'updated_by' => $updatedBy,
                'user_id' => $userId,
                'company_id' => $companyId,
            ]
        );
    }
}

<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class CompanyRelationModel extends BaseModel
{
    public function getAllForCompany(int $companyId): array
    {
        return $this->db()->fetchAll(
            "SELECT r.*, p.com_name AS parent_name, c.com_name AS child_name
             FROM sav_company_relations r
             INNER JOIN sav_companies p ON p.com_id = r.cor_parent_company_id
             INNER JOIN sav_companies c ON c.com_id = r.cor_child_company_id
             WHERE (r.cor_parent_company_id = :id OR r.cor_child_company_id = :id)
               AND r.cor_is_active = 1
             ORDER BY r.cor_created_at DESC",
            [':id' => $companyId]
        );
    }

    public function exists(int $parentId, int $childId, string $type): bool
    {
        $row = $this->db()->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_company_relations
             WHERE cor_parent_company_id = :parent_id
               AND cor_child_company_id = :child_id
               AND cor_relation_type = :type
               AND cor_is_active = 1",
            [':parent_id' => $parentId, ':child_id' => $childId, ':type' => $type]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    public function create(int $parentId, int $childId, string $type, int $userId): int
    {
        $this->db()->execute(
            "INSERT INTO sav_company_relations
             (cor_parent_company_id, cor_child_company_id, cor_relation_type, cor_is_active, cor_created_by, cor_created_at)
             VALUES (:parent_id, :child_id, :type, 1, :user_id, NOW())",
            [':parent_id' => $parentId, ':child_id' => $childId, ':type' => $type, ':user_id' => $userId]
        );
        return (int) $this->db()->lastInsertId();
    }

    public function deactivate(int $id, int $userId): bool
    {
        return $this->db()->execute(
            "UPDATE sav_company_relations
             SET cor_is_active = 0, cor_ended_at = CURDATE(), cor_updated_by = :user_id, cor_updated_at = NOW()
             WHERE cor_id = :id",
            [':id' => $id, ':user_id' => $userId]
        );
    }
}

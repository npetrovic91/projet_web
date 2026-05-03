<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class UserHierarchyModel extends BaseModel
{
    protected string $table = 'sav_user_hierarchy';

    public function getManagers(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT h.uhi_id, h.uhi_user_id, h.uhi_manager_id, h.uhi_company_id,
                    h.uhi_is_primary, h.uhi_valid_from, h.uhi_valid_until,
                    m.use_firstname, m.use_lastname, m.use_email,
                    r.rol_label AS manager_role_label, c.com_name AS company_name
             FROM sav_user_hierarchy h
             INNER JOIN sav_users m ON m.use_id = h.uhi_manager_id
             LEFT JOIN sav_user_roles ur ON ur.url_user_id = m.use_id
                AND ur.url_is_primary = 1
                AND ur.url_revoked_at IS NULL
             LEFT JOIN sav_roles r ON r.rol_id = ur.url_role_id
             LEFT JOIN sav_companies c ON c.com_id = h.uhi_company_id
             WHERE h.uhi_user_id = :user_id
               AND (h.uhi_valid_until IS NULL OR h.uhi_valid_until >= CURDATE())
               AND m.use_deleted_at IS NULL
             ORDER BY h.uhi_is_primary DESC, m.use_lastname ASC",
            ['user_id' => $userId]
        );
    }

    public function getSubordinates(int $managerId, ?int $companyId = null): array
    {
        $sql = "SELECT h.uhi_id, h.uhi_user_id, h.uhi_is_primary, h.uhi_company_id,
                       u.use_firstname, u.use_lastname, u.use_email, u.use_is_active,
                       r.rol_label AS user_role_label, c.com_name AS company_name
                FROM sav_user_hierarchy h
                INNER JOIN sav_users u ON u.use_id = h.uhi_user_id
                LEFT JOIN sav_user_roles ur ON ur.url_user_id = u.use_id
                   AND ur.url_is_primary = 1
                   AND ur.url_revoked_at IS NULL
                LEFT JOIN sav_roles r ON r.rol_id = ur.url_role_id
                LEFT JOIN sav_companies c ON c.com_id = h.uhi_company_id
                WHERE h.uhi_manager_id = :manager_id
                  AND (h.uhi_valid_until IS NULL OR h.uhi_valid_until >= CURDATE())
                  AND u.use_deleted_at IS NULL";
        $params = ['manager_id' => $managerId];

        if ($companyId !== null) {
            $sql .= " AND h.uhi_company_id = :company_id";
            $params['company_id'] = $companyId;
        }

        $sql .= " ORDER BY u.use_lastname ASC, u.use_firstname ASC";
        return $this->db->fetchAll($sql, $params);
    }

    public function relationExists(int $userId, int $managerId): bool
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM sav_user_hierarchy
             WHERE uhi_user_id = :user_id
               AND uhi_manager_id = :manager_id
               AND (uhi_valid_until IS NULL OR uhi_valid_until >= CURDATE())",
            ['user_id' => $userId, 'manager_id' => $managerId]
        ) > 0;
    }

    public function getManagerIds(int $userId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT uhi_manager_id FROM sav_user_hierarchy
             WHERE uhi_user_id = :user_id
               AND (uhi_valid_until IS NULL OR uhi_valid_until >= CURDATE())",
            ['user_id' => $userId]
        );

        return array_map('intval', array_column($rows, 'uhi_manager_id'));
    }

    public function addManager(int $userId, int $managerId, ?int $companyId, bool $isPrimary, ?string $validFrom, int $createdBy): bool
    {
        return $this->db->transaction(function () use ($userId, $managerId, $companyId, $isPrimary, $validFrom, $createdBy): bool {
            if ($isPrimary) {
                $this->db->execute(
                    "UPDATE sav_user_hierarchy
                     SET uhi_is_primary = 0,
                         uhi_updated_by = :updated_by,
                         uhi_updated_at = NOW()
                     WHERE uhi_user_id = :user_id
                       AND (uhi_valid_until IS NULL OR uhi_valid_until >= CURDATE())",
                    ['updated_by' => $createdBy, 'user_id' => $userId]
                );
            }

            return $this->db->execute(
                "INSERT INTO sav_user_hierarchy
                    (uhi_user_id, uhi_manager_id, uhi_company_id, uhi_is_primary,
                     uhi_valid_from, uhi_valid_until, uhi_created_by, uhi_updated_by,
                     uhi_created_at, uhi_updated_at)
                 VALUES
                    (:user_id, :manager_id, :company_id, :is_primary,
                     :valid_from, NULL, :created_by, :created_by, NOW(), NOW())",
                [
                    'user_id' => $userId,
                    'manager_id' => $managerId,
                    'company_id' => $companyId,
                    'is_primary' => $isPrimary,
                    'valid_from' => $validFrom ?? date('Y-m-d'),
                    'created_by' => $createdBy,
                ]
            );
        });
    }

    public function removeManager(int $userId, int $managerId, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_user_hierarchy
             SET uhi_valid_until = CURDATE(),
                 uhi_is_primary = 0,
                 uhi_updated_by = :updated_by,
                 uhi_updated_at = NOW()
             WHERE uhi_user_id = :user_id
               AND uhi_manager_id = :manager_id
               AND (uhi_valid_until IS NULL OR uhi_valid_until >= CURDATE())",
            ['updated_by' => $updatedBy, 'user_id' => $userId, 'manager_id' => $managerId]
        );
    }
}

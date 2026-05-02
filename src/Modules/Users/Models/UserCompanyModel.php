<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class UserCompanyModel extends BaseModel
{
    protected string $table = 'sav_user_companies';

    public function getUserCompanies(int $userId, bool $activeOnly = true): array
    {
        $sql = "SELECT uc.ucm_id, uc.ucm_user_id, uc.ucm_company_id,
                       uc.ucm_is_primary, uc.ucm_is_active, uc.ucm_joined_at,
                       uc.ucm_left_at, c.com_name, c.com_status, c.com_logo_url,
                       c.com_email, c.com_city, ct.cty_code AS type_code,
                       ct.cty_label AS type_label
                FROM sav_user_companies uc
                INNER JOIN sav_companies c ON c.com_id = uc.ucm_company_id
                INNER JOIN sav_company_types ct ON ct.cty_id = c.com_type_id
                WHERE uc.ucm_user_id = :user_id";

        if ($activeOnly) {
            $sql .= " AND uc.ucm_is_active = 1 AND c.com_deleted_at IS NULL AND c.com_is_active = 1";
        }

        $sql .= " ORDER BY uc.ucm_is_primary DESC, c.com_name ASC";
        return $this->db->fetchAll($sql, ['user_id' => $userId]);
    }

    public function getCompanyUsers(int $companyId, bool $activeOnly = true): array
    {
        $sql = "SELECT uc.ucm_id, uc.ucm_user_id, uc.ucm_is_primary,
                       uc.ucm_joined_at, u.use_firstname, u.use_lastname,
                       u.use_email, u.use_is_active, r.rol_code AS primary_role_code,
                       r.rol_label AS primary_role_label
                FROM sav_user_companies uc
                INNER JOIN sav_users u ON u.use_id = uc.ucm_user_id
                LEFT JOIN sav_user_roles ur ON ur.url_user_id = u.use_id
                    AND ur.url_is_primary = 1
                    AND ur.url_revoked_at IS NULL
                LEFT JOIN sav_roles r ON r.rol_id = ur.url_role_id
                WHERE uc.ucm_company_id = :company_id
                  AND u.use_deleted_at IS NULL";

        if ($activeOnly) {
            $sql .= " AND uc.ucm_is_active = 1 AND u.use_is_active = 1";
        }

        $sql .= " ORDER BY u.use_lastname ASC, u.use_firstname ASC";
        return $this->db->fetchAll($sql, ['company_id' => $companyId]);
    }

    public function isAttached(int $userId, int $companyId): bool
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM sav_user_companies
             WHERE ucm_user_id = :user_id
               AND ucm_company_id = :company_id
               AND ucm_is_active = 1",
            ['user_id' => $userId, 'company_id' => $companyId]
        ) > 0;
    }

    public function attach(int $userId, int $companyId, bool $isPrimary, ?string $joinedAt, int $createdBy): bool
    {
        return $this->db->transaction(function () use ($userId, $companyId, $isPrimary, $joinedAt, $createdBy): bool {
            if ($isPrimary) {
                $this->db->execute(
                    "UPDATE sav_user_companies SET ucm_is_primary = 0 WHERE ucm_user_id = :user_id",
                    ['user_id' => $userId]
                );
            }

            return $this->db->execute(
                "INSERT INTO sav_user_companies
                    (ucm_user_id, ucm_company_id, ucm_is_primary, ucm_is_active,
                     ucm_joined_at, ucm_left_at, ucm_created_by, ucm_updated_by,
                     ucm_created_at, ucm_updated_at)
                 VALUES
                    (:user_id, :company_id, :is_primary, 1,
                     :joined_at, NULL, :created_by, :created_by, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    ucm_is_active = 1,
                    ucm_is_primary = VALUES(ucm_is_primary),
                    ucm_joined_at = VALUES(ucm_joined_at),
                    ucm_left_at = NULL,
                    ucm_updated_by = VALUES(ucm_updated_by),
                    ucm_updated_at = NOW()",
                [
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'is_primary' => $isPrimary,
                    'joined_at' => $joinedAt,
                    'created_by' => $createdBy,
                ]
            );
        });
    }

    public function detach(int $userId, int $companyId, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_user_companies
             SET ucm_is_active = 0,
                 ucm_left_at = CURDATE(),
                 ucm_is_primary = 0,
                 ucm_updated_by = :updated_by,
                 ucm_updated_at = NOW()
             WHERE ucm_user_id = :user_id
               AND ucm_company_id = :company_id",
            ['updated_by' => $updatedBy, 'user_id' => $userId, 'company_id' => $companyId]
        );
    }

    public function setPrimary(int $userId, int $companyId, int $updatedBy): bool
    {
        return $this->db->transaction(function () use ($userId, $companyId, $updatedBy): bool {
            $this->db->execute(
                "UPDATE sav_user_companies
                 SET ucm_is_primary = 0,
                     ucm_updated_by = :updated_by,
                     ucm_updated_at = NOW()
                 WHERE ucm_user_id = :user_id",
                ['updated_by' => $updatedBy, 'user_id' => $userId]
            );

            return $this->db->execute(
                "UPDATE sav_user_companies
                 SET ucm_is_primary = 1,
                     ucm_updated_by = :updated_by,
                     ucm_updated_at = NOW()
                 WHERE ucm_user_id = :user_id
                   AND ucm_company_id = :company_id
                   AND ucm_is_active = 1",
                ['updated_by' => $updatedBy, 'user_id' => $userId, 'company_id' => $companyId]
            );
        });
    }
}

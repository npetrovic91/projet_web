<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class UserRoleModel extends BaseModel
{
    protected string $table = 'sav_user_roles';

    public function findRoleById(int $roleId): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM sav_roles WHERE rol_id = :id AND rol_is_active = 1",
            ['id' => $roleId]
        );
    }

    public function findRoleByCode(string $code): ?array
    {
        return $this->db->fetch(
            "SELECT * FROM sav_roles WHERE rol_code = :code AND rol_is_active = 1",
            ['code' => strtoupper(trim($code))]
        );
    }

    public function getActiveRoles(): array
    {
        return $this->db->fetchAll(
            "SELECT rol_id, rol_code, rol_label, rol_level, rol_description, rol_can_create_roles
             FROM sav_roles
             WHERE rol_is_active = 1
             ORDER BY rol_level ASC, rol_label ASC"
        );
    }

    public function getUserRoles(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT ur.url_id, ur.url_user_id, ur.url_role_id, ur.url_is_primary,
                    ur.url_granted_by, ur.url_granted_at, r.rol_code, r.rol_label,
                    r.rol_level, r.rol_can_create_roles, r.rol_description
             FROM sav_user_roles ur
             INNER JOIN sav_roles r ON r.rol_id = ur.url_role_id
             WHERE ur.url_user_id = :user_id
               AND ur.url_revoked_at IS NULL
             ORDER BY ur.url_is_primary DESC, r.rol_level ASC",
            ['user_id' => $userId]
        );
    }

    public function getPrimaryRole(int $userId): ?array
    {
        return $this->db->fetch(
            "SELECT ur.url_id, ur.url_role_id, ur.url_is_primary,
                    r.rol_code, r.rol_label, r.rol_level, r.rol_can_create_roles
             FROM sav_user_roles ur
             INNER JOIN sav_roles r ON r.rol_id = ur.url_role_id
             WHERE ur.url_user_id = :user_id
               AND ur.url_is_primary = 1
               AND ur.url_revoked_at IS NULL
             LIMIT 1",
            ['user_id' => $userId]
        );
    }

    public function assignRole(int $userId, int $roleId, int $grantedBy, bool $isPrimary = false): bool
    {
        return $this->db->transaction(function () use ($userId, $roleId, $grantedBy, $isPrimary): bool {
            if ($isPrimary) {
                $this->db->execute(
                    "UPDATE sav_user_roles SET url_is_primary = 0 WHERE url_user_id = :user_id",
                    ['user_id' => $userId]
                );
            }

            return $this->db->execute(
                "INSERT INTO sav_user_roles
                    (url_user_id, url_role_id, url_is_primary, url_granted_by, url_granted_at, url_created_at)
                 VALUES
                    (:user_id, :role_id, :is_primary, :granted_by, NOW(), NOW())
                 ON DUPLICATE KEY UPDATE
                    url_is_primary = VALUES(url_is_primary),
                    url_granted_by = VALUES(url_granted_by),
                    url_granted_at = NOW(),
                    url_revoked_by = NULL,
                    url_revoked_at = NULL",
                [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'is_primary' => $isPrimary,
                    'granted_by' => $grantedBy,
                ]
            );
        });
    }

    public function revokeRole(int $userId, int $roleId, int $revokedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_user_roles
             SET url_revoked_by = :revoked_by,
                 url_revoked_at = NOW(),
                 url_is_primary = 0
             WHERE url_user_id = :user_id
               AND url_role_id = :role_id
               AND url_revoked_at IS NULL",
            ['revoked_by' => $revokedBy, 'user_id' => $userId, 'role_id' => $roleId]
        );
    }
}

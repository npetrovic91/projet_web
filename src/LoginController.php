<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Functions\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class UserFunctionModel extends BaseModel
{
    protected string $table = 'sav_user_functions';

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT uf.*, f.fnc_code, f.fnc_label, f.fnc_is_global, c.com_name AS context_company_name
             FROM sav_user_functions uf
             INNER JOIN sav_functions f ON f.fnc_id = uf.ufn_function_id
             LEFT JOIN sav_companies c ON c.com_id = uf.ufn_company_id
             WHERE uf.ufn_user_id = :user_id
             ORDER BY uf.ufn_is_primary DESC, f.fnc_label ASC",
            ['user_id' => $userId]
        );
    }

    public function hasFunction(int $userId, int $functionId, ?int $companyId = null): bool
    {
        return (int) $this->db->fetchColumn(
            "SELECT COUNT(*) FROM sav_user_functions
             WHERE ufn_user_id = :user_id
               AND ufn_function_id = :function_id
               AND ((ufn_company_id IS NULL AND :company_null = 1) OR ufn_company_id = :company_id)",
            [
                'user_id' => $userId,
                'function_id' => $functionId,
                'company_null' => $companyId === null ? 1 : 0,
                'company_id' => $companyId,
            ]
        ) > 0;
    }

    public function assign(int $userId, int $functionId, ?int $companyId, bool $isPrimary, int $createdBy): bool
    {
        return $this->db->transaction(function () use ($userId, $functionId, $companyId, $isPrimary, $createdBy): bool {
            if ($isPrimary) {
                $this->db->execute(
                    "UPDATE sav_user_functions SET ufn_is_primary = 0 WHERE ufn_user_id = :user_id",
                    ['user_id' => $userId]
                );
            }

            return $this->db->execute(
                "INSERT INTO sav_user_functions
                    (ufn_user_id, ufn_function_id, ufn_company_id, ufn_is_primary, ufn_created_by, ufn_created_at)
                 VALUES
                    (:user_id, :function_id, :company_id, :is_primary, :created_by, NOW())
                 ON DUPLICATE KEY UPDATE
                    ufn_is_primary = VALUES(ufn_is_primary),
                    ufn_created_by = VALUES(ufn_created_by)",
                [
                    'user_id' => $userId,
                    'function_id' => $functionId,
                    'company_id' => $companyId,
                    'is_primary' => $isPrimary,
                    'created_by' => $createdBy,
                ]
            );
        });
    }

    public function unassign(int $userId, int $functionId): bool
    {
        return $this->db->execute(
            "DELETE FROM sav_user_functions
             WHERE ufn_user_id = :user_id AND ufn_function_id = :function_id",
            ['user_id' => $userId, 'function_id' => $functionId]
        );
    }

    public function sync(int $userId, array $functionIds, int $primaryFunctionId, int $createdBy): void
    {
        $this->db->transaction(function () use ($userId, $functionIds, $primaryFunctionId, $createdBy): void {
            $this->db->execute('DELETE FROM sav_user_functions WHERE ufn_user_id = :user_id', ['user_id' => $userId]);
            foreach (array_values(array_unique(array_map('intval', $functionIds))) as $functionId) {
                $this->assign($userId, $functionId, null, $functionId === $primaryFunctionId, $createdBy);
            }
        });
    }
}

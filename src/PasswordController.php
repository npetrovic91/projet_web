<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Functions\Models;

use Nenad\Autosav\Core\Model\BaseModel;
use PDO;

class FunctionModel extends BaseModel
{
    protected string $table = 'sav_functions';

    public function getForContext(?int $companyId = null, bool $adminView = false): array
    {
        $conditions = $adminView ? ['1 = 1'] : ['f.fnc_is_active = 1'];
        $params = [];

        if ($companyId !== null) {
            $conditions[] = '(f.fnc_is_global = 1 OR f.fnc_company_id = :company_id)';
            $params['company_id'] = $companyId;
        }

        $sql = "SELECT f.*, c.com_name AS fnc_company_name,
                       CONCAT(COALESCE(u.use_firstname, ''), ' ', COALESCE(u.use_lastname, '')) AS fnc_creator_name
                FROM sav_functions f
                LEFT JOIN sav_companies c ON c.com_id = f.fnc_company_id
                LEFT JOIN sav_users u ON u.use_id = f.fnc_created_by
                WHERE " . implode(' AND ', $conditions) . "
                ORDER BY f.fnc_is_global DESC, f.fnc_label ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function findByIdFull(int $id): ?array
    {
        return $this->db->fetch(
            "SELECT f.*, c.com_name AS fnc_company_name
             FROM sav_functions f
             LEFT JOIN sav_companies c ON c.com_id = f.fnc_company_id
             WHERE f.fnc_id = :id",
            ['id' => $id]
        );
    }

    public function codeExists(string $code, ?int $companyId, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*) FROM sav_functions WHERE fnc_code = :code";
        $params = ['code' => strtoupper(trim($code))];

        if ($excludeId !== null) {
            $sql .= ' AND fnc_id <> :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        return (int) $this->db->fetchColumn($sql, $params) > 0;
    }

    public function createFunction(array $data): int
    {
        $this->db->execute(
            "INSERT INTO sav_functions
                (fnc_uuid, fnc_code, fnc_label, fnc_description, fnc_company_id,
                 fnc_is_global, fnc_is_active, fnc_created_by, fnc_updated_by,
                 fnc_created_at, fnc_updated_at)
             VALUES
                (:uuid, :code, :label, :description, :company_id,
                 :is_global, 1, :created_by, :created_by, NOW(), NOW())",
            $data
        );

        return (int) $this->db->lastInsertId();
    }

    public function updateFunction(int $id, array $data): bool
    {
        return $this->db->execute(
            "UPDATE sav_functions
             SET fnc_code = :code,
                 fnc_label = :label,
                 fnc_description = :description,
                 fnc_updated_by = :updated_by,
                 fnc_updated_at = NOW()
             WHERE fnc_id = :id",
            [
                'id' => $id,
                'code' => $data['code'],
                'label' => $data['label'],
                'description' => $data['description'] ?? null,
                'updated_by' => $data['updated_by'] ?? null,
            ]
        );
    }

    public function setActive(int $id, bool $active, int $updatedBy): bool
    {
        return $this->db->execute(
            "UPDATE sav_functions
             SET fnc_is_active = :active,
                 fnc_updated_by = :updated_by,
                 fnc_updated_at = NOW()
             WHERE fnc_id = :id",
            ['active' => $active, 'updated_by' => $updatedBy, 'id' => $id]
        );
    }

    public function search(string $query, ?int $companyId, int $limit = 20): array
    {
        $sql = "SELECT f.fnc_id, f.fnc_code, f.fnc_label, f.fnc_is_global
                FROM sav_functions f
                WHERE f.fnc_is_active = 1
                  AND (f.fnc_is_global = 1 OR f.fnc_company_id = :company_id OR :company_id_null = 1)
                  AND (f.fnc_label LIKE :q1 OR f.fnc_code LIKE :q2)
                ORDER BY f.fnc_is_global DESC, f.fnc_label ASC
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $like = '%' . trim($query) . '%';
        $stmt->bindValue(':company_id', $companyId, $companyId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':company_id_null', $companyId === null ? 1 : 0, PDO::PARAM_INT);
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', max(1, min(50, $limit)), PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Brands\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class BrandModel extends BaseModel
{
    public function allActive(): array
    {
        return $this->db()->fetchAll(
            "SELECT * FROM sav_brands WHERE brd_is_active = 1 ORDER BY brd_name ASC"
        );
    }

    public function paginate(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['brd_deleted_at IS NULL'];
        $params = [];
        if (!empty($filters['search'])) {
            $where[] = '(brd_name LIKE :search OR brd_code LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        $whereSql = implode(' AND ', $where);
        $total = (int) ($this->db()->fetch("SELECT COUNT(*) AS cnt FROM sav_brands WHERE {$whereSql}", $params)['cnt'] ?? 0);
        $pages = max(1, (int) ceil($total / $perPage));
        $offset = (max(1, $page) - 1) * $perPage;
        $rows = $this->db()->fetchAll(
            "SELECT * FROM sav_brands WHERE {$whereSql} ORDER BY brd_name ASC LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $perPage, ':offset' => $offset])
        );
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    public function find(int $id): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM sav_brands WHERE brd_id = :id AND brd_deleted_at IS NULL LIMIT 1",
            [':id' => $id]
        );
    }

    public function create(array $data): int
    {
        $this->db()->execute(
            "INSERT INTO sav_brands
             (brd_uuid, brd_code, brd_name, brd_logo_url, brd_is_active, brd_created_by, brd_created_at, brd_updated_at)
             VALUES (:uuid, :code, :name, :logo_url, :active, :user_id, NOW(), NOW())",
            $data
        );
        return (int) $this->db()->lastInsertId();
    }

    public function updateBrand(int $id, array $data): bool
    {
        $data[':id'] = $id;
        return $this->db()->execute(
            "UPDATE sav_brands
             SET brd_code = :code, brd_name = :name, brd_logo_url = :logo_url,
                 brd_is_active = :active, brd_updated_by = :user_id, brd_updated_at = NOW()
             WHERE brd_id = :id",
            $data
        );
    }

    public function attachToCompany(int $companyId, int $brandId, int $userId, bool $primary = false): bool
    {
        return $this->db()->execute(
            "INSERT INTO sav_company_brands
             (cbr_company_id, cbr_brand_id, cbr_is_primary, cbr_created_by, cbr_created_at)
             VALUES (:company_id, :brand_id, :primary, :user_id, NOW())
             ON DUPLICATE KEY UPDATE cbr_is_active = 1, cbr_is_primary = VALUES(cbr_is_primary), cbr_updated_by = VALUES(cbr_created_by), cbr_updated_at = NOW()",
            [':company_id' => $companyId, ':brand_id' => $brandId, ':primary' => $primary ? 1 : 0, ':user_id' => $userId]
        );
    }

    public function detachFromCompany(int $companyId, int $brandId, int $userId): bool
    {
        return $this->db()->execute(
            "UPDATE sav_company_brands
             SET cbr_is_active = 0, cbr_updated_by = :user_id, cbr_updated_at = NOW()
             WHERE cbr_company_id = :company_id AND cbr_brand_id = :brand_id",
            [':company_id' => $companyId, ':brand_id' => $brandId, ':user_id' => $userId]
        );
    }

    public function getBrandsForCompany(int $companyId): array
    {
        return $this->db()->fetchAll(
            "SELECT b.*, cb.cbr_is_primary
             FROM sav_company_brands cb
             INNER JOIN sav_brands b ON b.brd_id = cb.cbr_brand_id
             WHERE cb.cbr_company_id = :company_id AND cb.cbr_is_active = 1
             ORDER BY cb.cbr_is_primary DESC, b.brd_name ASC",
            [':company_id' => $companyId]
        );
    }
}

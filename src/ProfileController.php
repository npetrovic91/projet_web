<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class CompanyModel extends BaseModel
{
    public function paginate(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where = ['c.com_deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['type_code'])) {
            $where[] = 'ct.cty_code = :type_code';
            $params[':type_code'] = strtoupper((string) $filters['type_code']);
        }
        if (!empty($filters['search'])) {
            $where[] = '(c.com_name LIKE :search OR c.com_legal_name LIKE :search OR c.com_city LIKE :search OR c.com_siret LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }
        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $where[] = 'c.com_is_active = :active';
            $params[':active'] = (int) $filters['is_active'];
        }

        $whereSql = implode(' AND ', $where);
        $total = (int) ($this->db()->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_companies c
             INNER JOIN sav_company_types ct ON ct.cty_id = c.com_type_id
             WHERE {$whereSql}",
            $params
        )['cnt'] ?? 0);

        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));
        $offset = ($page - 1) * $perPage;

        $rows = $this->db()->fetchAll(
            "SELECT c.*, ct.cty_code AS type_code, ct.cty_label AS type_label, h.com_name AS holding_name
             FROM sav_companies c
             INNER JOIN sav_company_types ct ON ct.cty_id = c.com_type_id
             LEFT JOIN sav_companies h ON h.com_id = c.com_holding_id
             WHERE {$whereSql}
             ORDER BY ct.cty_sort_order ASC, c.com_name ASC
             LIMIT :limit OFFSET :offset",
            array_merge($params, [':limit' => $perPage, ':offset' => $offset])
        );

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'perPage' => $perPage];
    }

    public function findFull(int $id): ?array
    {
        return $this->db()->fetch(
            "SELECT c.*, ct.cty_code AS type_code, ct.cty_label AS type_label, h.com_name AS holding_name
             FROM sav_companies c
             INNER JOIN sav_company_types ct ON ct.cty_id = c.com_type_id
             LEFT JOIN sav_companies h ON h.com_id = c.com_holding_id
             WHERE c.com_id = :id AND c.com_deleted_at IS NULL
             LIMIT 1",
            [':id' => $id]
        );
    }

    public function create(array $data): int
    {
        $this->db()->execute(
            "INSERT INTO sav_companies
             (com_uuid, com_type_id, com_holding_id, com_name, com_legal_name, com_siret,
              com_address, com_zipcode, com_city, com_country, com_phone, com_email,
              com_status, com_is_active, com_created_by, com_created_at, com_updated_at)
             VALUES
             (:uuid, :type_id, :holding_id, :name, :legal_name, :siret,
              :address, :zipcode, :city, :country, :phone, :email,
              :status, :active, :created_by, NOW(), NOW())",
            $data
        );
        return (int) $this->db()->lastInsertId();
    }

    public function updateCompany(int $id, array $data): bool
    {
        $data[':id'] = $id;
        return $this->db()->execute(
            "UPDATE sav_companies
             SET com_type_id = :type_id,
                 com_holding_id = :holding_id,
                 com_name = :name,
                 com_legal_name = :legal_name,
                 com_siret = :siret,
                 com_address = :address,
                 com_zipcode = :zipcode,
                 com_city = :city,
                 com_country = :country,
                 com_phone = :phone,
                 com_email = :email,
                 com_status = :status,
                 com_is_active = :active,
                 com_updated_by = :created_by,
                 com_updated_at = NOW()
             WHERE com_id = :id",
            $data
        );
    }

    public function softDeleteCompany(int $id, int $userId): bool
    {
        return $this->db()->execute(
            "UPDATE sav_companies
             SET com_deleted_at = NOW(), com_deleted_by = :user_id, com_is_active = 0, com_updated_at = NOW()
             WHERE com_id = :id",
            [':id' => $id, ':user_id' => $userId]
        );
    }

    public function byTypeCode(string $code): array
    {
        return $this->db()->fetchAll(
            "SELECT c.*
             FROM sav_companies c
             INNER JOIN sav_company_types ct ON ct.cty_id = c.com_type_id
             WHERE ct.cty_code = :code
               AND c.com_is_active = 1
               AND c.com_deleted_at IS NULL
             ORDER BY c.com_name ASC",
            [':code' => strtoupper($code)]
        );
    }
}

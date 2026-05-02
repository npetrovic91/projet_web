<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Ajax\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class ContextModel extends BaseModel
{
    public function getUserCompanies(int $userId): array
    {
        return $this->db()->fetchAll(
            "SELECT c.com_id, c.com_name, c.com_short_name, c.com_logo_url,
                    ct.cty_code AS company_type_code, ct.cty_label AS company_type_label,
                    uc.ucm_is_primary
             FROM sav_user_companies uc
             INNER JOIN sav_companies c ON c.com_id = uc.ucm_company_id
             LEFT JOIN sav_company_types ct ON ct.cty_id = c.com_type_id
             WHERE uc.ucm_user_id = :user_id
               AND uc.ucm_is_active = 1
               AND c.com_deleted_at IS NULL
             ORDER BY uc.ucm_is_primary DESC, c.com_name ASC",
            [':user_id' => $userId]
        );
    }

    public function getBrandsForCompany(int $companyId): array
    {
        return $this->db()->fetchAll(
            "SELECT b.brd_id, b.brd_code, b.brd_name, b.brd_logo_url, cb.cbr_is_primary
             FROM sav_company_brands cb
             INNER JOIN sav_brands b ON b.brd_id = cb.cbr_brand_id
             WHERE cb.cbr_company_id = :company_id
               AND b.brd_is_active = 1
             ORDER BY cb.cbr_is_primary DESC, b.brd_name ASC",
            [':company_id' => $companyId]
        );
    }

    public function userOwnsCompany(int $userId, int $companyId): bool
    {
        $row = $this->db()->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_user_companies
             WHERE ucm_user_id = :user_id AND ucm_company_id = :company_id AND ucm_is_active = 1",
            [':user_id' => $userId, ':company_id' => $companyId]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    public function companyHasBrand(int $companyId, int $brandId): bool
    {
        $row = $this->db()->fetch(
            "SELECT COUNT(*) AS cnt FROM sav_company_brands
             WHERE cbr_company_id = :company_id AND cbr_brand_id = :brand_id",
            [':company_id' => $companyId, ':brand_id' => $brandId]
        );
        return (int) ($row['cnt'] ?? 0) > 0;
    }

    public function updateActiveCompany(int $userId, ?int $companyId): bool
    {
        return $this->db()->execute(
            "UPDATE sav_users
             SET use_active_company_id = :company_id, use_active_brand_id = NULL, use_updated_at = NOW()
             WHERE use_id = :user_id",
            [':company_id' => $companyId, ':user_id' => $userId]
        );
    }

    public function updateActiveBrand(int $userId, ?int $brandId): bool
    {
        return $this->db()->execute(
            "UPDATE sav_users
             SET use_active_brand_id = :brand_id, use_updated_at = NOW()
             WHERE use_id = :user_id",
            [':brand_id' => $brandId, ':user_id' => $userId]
        );
    }
}

<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Companies\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class CompanyTypeModel extends BaseModel
{
    public function getAllActive(): array
    {
        return $this->db()->fetchAll(
            "SELECT * FROM sav_company_types
             WHERE cty_is_active = 1
             ORDER BY cty_sort_order ASC, cty_label ASC"
        );
    }

    public function findByCode(string $code): ?array
    {
        return $this->db()->fetch(
            "SELECT * FROM sav_company_types WHERE cty_code = :code LIMIT 1",
            [':code' => strtoupper(trim($code))]
        );
    }
}

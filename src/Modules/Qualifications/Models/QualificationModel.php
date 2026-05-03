<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Qualifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class QualificationModel extends BaseModel
{
    protected string $table = 'sav_qualifications';

    public function getForContext(?int $companyId = null): array
    {
        return $this->db->fetchAll(
            "SELECT q.*, c.com_name AS qua_company_name
             FROM sav_qualifications q
             LEFT JOIN sav_companies c ON c.com_id = q.qua_company_id
             WHERE q.qua_is_active = 1
               AND (q.qua_is_global = 1 OR q.qua_company_id = :company_id OR :company_null = 1)
             ORDER BY q.qua_is_global DESC, q.qua_label ASC",
            ['company_id' => $companyId, 'company_null' => $companyId === null ? 1 : 0]
        );
    }
}

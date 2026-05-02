<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Skills\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class SkillModel extends BaseModel
{
    protected string $table = 'sav_skills';

    public function getForContext(?int $companyId = null): array
    {
        $sql = "SELECT s.*, c.com_name AS skl_company_name
                FROM sav_skills s
                LEFT JOIN sav_companies c ON c.com_id = s.skl_company_id
                WHERE s.skl_is_active = 1
                  AND (s.skl_is_global = 1 OR s.skl_company_id = :company_id OR :company_null = 1)
                ORDER BY s.skl_is_global DESC, s.skl_label ASC";

        return $this->db->fetchAll($sql, [
            'company_id' => $companyId,
            'company_null' => $companyId === null ? 1 : 0,
        ]);
    }
}

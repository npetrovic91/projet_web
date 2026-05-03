<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Qualifications\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class UserQualificationModel extends BaseModel
{
    protected string $table = 'sav_user_qualifications';

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT uq.*, q.qua_code, q.qua_label, q.qua_description, q.qua_issuer
             FROM sav_user_qualifications uq
             INNER JOIN sav_qualifications q ON q.qua_id = uq.uqu_qualification_id
             WHERE uq.uqu_user_id = :user_id
             ORDER BY uq.uqu_expires_at IS NULL ASC, uq.uqu_expires_at ASC, q.qua_label ASC",
            ['user_id' => $userId]
        );
    }
}

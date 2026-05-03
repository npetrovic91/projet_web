<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Skills\Models;

use Nenad\Autosav\Core\Model\BaseModel;

class UserSkillModel extends BaseModel
{
    protected string $table = 'sav_user_skills';

    public function getForUser(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT us.*, s.skl_code, s.skl_label, s.skl_description
             FROM sav_user_skills us
             INNER JOIN sav_skills s ON s.skl_id = us.usk_skill_id
             WHERE us.usk_user_id = :user_id
             ORDER BY s.skl_label ASC",
            ['user_id' => $userId]
        );
    }
}

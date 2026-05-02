<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Skills\Services;

use Nenad\Autosav\Modules\Skills\Models\SkillModel;
use Nenad\Autosav\Modules\Skills\Models\UserSkillModel;

class SkillService
{
    public function __construct(
        private SkillModel $skills,
        private UserSkillModel $userSkills
    ) {}

    public function getAvailableSkills(?int $companyId): array
    {
        return $this->skills->getForContext($companyId);
    }

    public function getUserSkills(int $userId): array
    {
        return $this->userSkills->getForUser($userId);
    }
}

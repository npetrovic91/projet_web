<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Qualifications\Services;

use Nenad\Autosav\Modules\Qualifications\Models\QualificationModel;
use Nenad\Autosav\Modules\Qualifications\Models\UserQualificationModel;

class QualificationService
{
    public function __construct(
        private QualificationModel $qualifications,
        private UserQualificationModel $userQualifications
    ) {}

    public function getAvailableQualifications(?int $companyId): array
    {
        return $this->qualifications->getForContext($companyId);
    }

    public function getUserQualifications(int $userId): array
    {
        return $this->userQualifications->getForUser($userId);
    }
}

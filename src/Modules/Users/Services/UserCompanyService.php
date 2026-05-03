<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Services;

use Nenad\Autosav\Modules\Users\Models\UserCompanyHistoryModel;
use Nenad\Autosav\Modules\Users\Models\UserCompanyModel;
use Nenad\Autosav\Modules\Users\Models\UserModel;

class UserCompanyService
{
    public function __construct(
        private UserCompanyModel $companies,
        private UserCompanyHistoryModel $history,
        private UserModel $users
    ) {}

    public function attachUserToCompany(
        int $userId,
        int $companyId,
        bool $isPrimary,
        ?string $joinedAt,
        ?string $jobTitle,
        int $operatorId
    ): array {
        if ($companyId <= 0) {
            return ['success' => false, 'message' => 'Entreprise invalide.'];
        }
        if ($this->companies->isAttached($userId, $companyId)) {
            return ['success' => false, 'message' => 'Utilisateur deja rattache a cette entreprise.'];
        }

        $joinedAt = $joinedAt ?: date('Y-m-d');
        $this->companies->attach($userId, $companyId, $isPrimary, $joinedAt, $operatorId);
        $this->history->addEntry([
            'user_id' => $userId,
            'company_id' => $companyId,
            'job_title' => trim((string) $jobTitle) ?: null,
            'started_at' => $joinedAt,
            'created_by' => $operatorId,
        ]);

        if ($isPrimary) {
            $this->users->updateActiveContext($userId, $companyId, null);
        }

        logger('audit')->info('user_company_attached', [
            'user_id' => $userId,
            'company_id' => $companyId,
            'operator_id' => $operatorId,
        ]);

        return ['success' => true, 'message' => 'Rattachement entreprise ajoute.'];
    }

    public function detachUserFromCompany(int $userId, int $companyId, string $reason, int $operatorId): array
    {
        if (!$this->companies->isAttached($userId, $companyId)) {
            return ['success' => false, 'message' => 'Utilisateur non rattache a cette entreprise.'];
        }

        $this->history->closeActiveEntry($userId, $companyId, date('Y-m-d'), $reason, $operatorId);
        $this->companies->detach($userId, $companyId, $operatorId);

        logger('audit')->info('user_company_detached', [
            'user_id' => $userId,
            'company_id' => $companyId,
            'operator_id' => $operatorId,
            'reason' => $reason,
        ]);

        return ['success' => true, 'message' => 'Rattachement entreprise retire.'];
    }

    public function setPrimaryCompany(int $userId, int $companyId, int $operatorId): array
    {
        if (!$this->companies->isAttached($userId, $companyId)) {
            return ['success' => false, 'message' => 'Utilisateur non rattache a cette entreprise.'];
        }

        $this->companies->setPrimary($userId, $companyId, $operatorId);
        $this->users->updateActiveContext($userId, $companyId, null);

        logger('audit')->info('user_primary_company_set', [
            'user_id' => $userId,
            'company_id' => $companyId,
            'operator_id' => $operatorId,
        ]);

        return ['success' => true, 'message' => 'Entreprise principale mise a jour.'];
    }

    public function getUserCompanies(int $userId): array
    {
        return $this->companies->getUserCompanies($userId);
    }

    public function getUserHistory(int $userId): array
    {
        return $this->history->getUserHistory($userId);
    }
}

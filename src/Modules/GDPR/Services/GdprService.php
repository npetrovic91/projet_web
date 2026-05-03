<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\GDPR\Services;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Modules\GDPR\Models\GdprActionModel;
use Nenad\Autosav\Modules\GDPR\Models\GdprExportModel;
use Nenad\Autosav\Modules\GDPR\Models\GdprRequestModel;
use Nenad\Autosav\Modules\Profile\Models\GdprRequestModel as ProfileGdprRequestModel;
use Nenad\Autosav\Modules\Profile\Services\ProfileService;
use Nenad\Autosav\Modules\Qualifications\Models\QualificationModel;
use Nenad\Autosav\Modules\Qualifications\Models\UserQualificationModel;
use Nenad\Autosav\Modules\Qualifications\Services\QualificationService;
use Nenad\Autosav\Modules\Skills\Models\SkillModel;
use Nenad\Autosav\Modules\Skills\Models\UserSkillModel;
use Nenad\Autosav\Modules\Skills\Services\SkillService;
use Nenad\Autosav\Modules\Users\Models\UserCompanyHistoryModel;
use Nenad\Autosav\Modules\Users\Models\UserCompanyModel;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use Nenad\Autosav\Modules\Users\Services\UserCompanyService;

class GdprService
{
    private ProfileService $profiles;

    public function __construct(
        private GdprRequestModel $requests,
        private GdprActionModel $actions,
        private GdprExportModel $exports,
        private UserModel $users
    ) {
        $companyModel = new UserCompanyModel();
        $historyModel = new UserCompanyHistoryModel();
        $this->profiles = new ProfileService(
            $this->users,
            new UserCompanyService($companyModel, $historyModel, $this->users),
            new SkillService(new SkillModel(), new UserSkillModel()),
            new QualificationService(new QualificationModel(), new UserQualificationModel()),
            new ProfileGdprRequestModel()
        );
    }

    public function listRequests(array $filters = []): array
    {
        return $this->requests->listRequests($filters);
    }

    public function findRequest(int $requestId): ?array
    {
        return $this->requests->findRequest($requestId);
    }

    public function acceptRequest(int $requestId, int $adminId, string $ip, string $response): bool
    {
        $request = $this->requests->findRequest($requestId);
        if (!$request) {
            return false;
        }
        $this->requests->updateStatus($requestId, 'accepted', $adminId, $response);
        $this->actions->record($requestId, (int) $request['grq_user_id'], 'request_accepted', $adminId, $ip, ['response' => $response]);
        return true;
    }

    public function rejectRequest(int $requestId, int $adminId, string $ip, string $reason): bool
    {
        $request = $this->requests->findRequest($requestId);
        if (!$request) {
            return false;
        }
        $this->requests->updateStatus($requestId, 'rejected', $adminId, null, $reason);
        $this->actions->record($requestId, (int) $request['grq_user_id'], 'request_rejected', $adminId, $ip, ['reason' => $reason]);
        return true;
    }

    public function exportUserData(int $userId, ?int $requestId, int $adminId, string $ip): array
    {
        $payload = $this->profiles->exportUserData($userId);
        $fileName = 'gdpr-export-user-' . $userId . '-' . date('YmdHis') . '.json';
        $this->exports->record($userId, $requestId, $fileName, $adminId, $ip);
        $this->actions->record($requestId, $userId, 'user_data_exported', $adminId, $ip, ['file_name' => $fileName]);
        return $payload;
    }

    public function anonymizeUser(int $userId, int $adminId, string $ip, string $reason): bool
    {
        $anonymousEmail = 'anon-' . $userId . '-' . time() . '@anonymized.local';
        $db = Database::getInstance();
        $db->transaction(function () use ($userId, $anonymousEmail, $adminId, $reason): void {
            $db = Database::getInstance();
            $db->execute(
                "UPDATE sav_users
                 SET use_email = :email,
                     use_firstname = 'Utilisateur',
                     use_lastname = 'Anonymise',
                     use_phone = NULL,
                     use_mobile = NULL,
                     use_photo_url = NULL,
                     use_employee_number = NULL,
                     use_department = NULL,
                     use_job_title = NULL,
                     use_is_active = 0,
                     use_gdpr_anonymized = 1,
                     use_deleted_at = NOW(),
                     use_deleted_by = :admin_id,
                     use_deleted_reason = :reason,
                     use_updated_by = :admin_id,
                     use_updated_at = NOW()
                 WHERE use_id = :user_id",
                ['email' => $anonymousEmail, 'admin_id' => $adminId, 'reason' => $reason, 'user_id' => $userId]
            );
        });

        $this->actions->record(null, $userId, 'user_anonymized', $adminId, $ip, ['reason' => $reason]);
        return true;
    }

    public function latestActions(): array
    {
        return $this->actions->latest();
    }
}

<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\GDPR\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Modules\GDPR\Models\GdprActionModel;
use Nenad\Autosav\Modules\GDPR\Models\GdprExportModel;
use Nenad\Autosav\Modules\GDPR\Models\GdprRequestModel;
use Nenad\Autosav\Modules\Notifications\Services\ActionNotifier;
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

class GdprService implements ServiceInterface{
    private ProfileService $profiles;
    private ActionNotifier $notifier;

    public function __construct(
        private GdprRequestModel $requests,
        private GdprActionModel $actions,
        private GdprExportModel $exports,
        private UserModel $users,
        ?ActionNotifier $notifier = null
    ) {
        $this->notifier = $notifier ?? new ActionNotifier();
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

    /**
     * CORRECTIF 2.4 (notifications in-app, 2026-06-27) : la personne ayant
     * fait une demande RGPD n'était jamais informée de la décision —
     * problématique au-delà de l'UX : le RGPD lui-même impose d'informer
     * la personne concernée du traitement de sa demande.
     */
    public function acceptRequest(int $requestId, int $adminId, string $ip, string $response): bool
    {
        $request = $this->requests->findRequest($requestId);
        if (!$request) {
            return false;
        }
        $this->requests->updateStatus($requestId, 'accepted', $adminId, $response);
        $userId = (int) ($request['grq_user_id'] ?? 0);
        $this->actions->record($requestId, $userId, 'request_accepted', $adminId, $ip, ['response' => $response]);
        if ($userId > 0) {
            $this->notifier->notifierUtilisateur(
                $userId,
                'gdpr.demande_acceptee',
                'Votre demande RGPD a été acceptée',
                trim('Votre demande a été acceptée. ' . $response),
                ['request_id' => $requestId],
                null,
                $adminId
            );
        }
        return true;
    }

    public function rejectRequest(int $requestId, int $adminId, string $ip, string $reason): bool
    {
        $request = $this->requests->findRequest($requestId);
        if (!$request) {
            return false;
        }
        $this->requests->updateStatus($requestId, 'rejected', $adminId, null, $reason);
        $userId = (int) ($request['grq_user_id'] ?? 0);
        $this->actions->record($requestId, $userId, 'request_rejected', $adminId, $ip, ['reason' => $reason]);
        if ($userId > 0) {
            $this->notifier->notifierUtilisateur(
                $userId,
                'gdpr.demande_rejetee',
                'Votre demande RGPD a été rejetée',
                trim('Votre demande a été rejetée. Motif : ' . $reason),
                ['request_id' => $requestId],
                null,
                $adminId
            );
        }
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
                "UPDATE sav_utilisateurs
                 SET uti_email = :email,
                     uti_email_normalise = LOWER(:email),
                     uti_anonymise_le = NOW(),
                     uti_anonymise_par_utilisateur_id = :admin_id,
                     uti_motif_anonymisation = :reason,
                     uti_supprime_le = NOW(),
                     uti_supprime_par_utilisateur_id = :admin_id,
                     uti_modifie_par_utilisateur_id = :admin_id,
                     uti_modifie_le = NOW()
                 WHERE uti_id = :user_id",
                ['email' => $anonymousEmail, 'admin_id' => $adminId, 'reason' => $reason, 'user_id' => $userId]
            );

            $db->execute(
                "UPDATE sav_profils_utilisateurs
                    SET pui_prenom = 'Utilisateur',
                        pui_nom = 'Anonymise',
                        pui_telephone = NULL,
                        pui_mobile = NULL,
                        pui_photo_fichier_id = NULL,
                        pui_modifie_le = NOW()
                  WHERE pui_utilisateur_id = :user_id",
                ['user_id' => $userId]
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

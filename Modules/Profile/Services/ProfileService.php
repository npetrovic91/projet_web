<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Profile\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Modules\Notifications\Services\ActionNotifier;
use Nenad\Autosav\Modules\Profile\Models\GdprRequestModel;
use Nenad\Autosav\Modules\Qualifications\Services\QualificationService;
use Nenad\Autosav\Modules\Skills\Services\SkillService;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use Nenad\Autosav\Modules\Users\Services\UserCompanyService;

class ProfileService implements ServiceInterface{
    private ActionNotifier $notifier;

    public function __construct(
        private UserModel $users,
        private UserCompanyService $companies,
        private SkillService $skills,
        private QualificationService $qualifications,
        private GdprRequestModel $gdprRequests,
        ?ActionNotifier $notifier = null
    ) {
        $this->notifier = $notifier ?? new ActionNotifier();
    }

    public function getProfile(int $userId): ?array
    {
        $fiche = $this->users->ficheComplete($userId);
        if (!$fiche) {
            return null;
        }

        $user = $fiche['user'];
        $societes = $fiche['societes'] ?? $this->companies->getUserCompanies($userId);
        $activeCompanyId = (int) ($user['uti_societe_active_id'] ?? $user['use_active_company_id'] ?? 0);
        foreach ($societes as $societe) {
            if ($activeCompanyId > 0 && (int) ($societe['soc_id'] ?? $societe['aus_societe_id'] ?? 0) === $activeCompanyId) {
                $user['active_company_name'] = $societe['soc_nom'] ?? null;
                break;
            }
        }

        $user['companies'] = $societes;
        $user['societes'] = $societes;
        $user['history'] = $this->companies->getUserHistory($userId);
        $user['roles'] = $fiche['roles'] ?? [];
        $user['functions'] = $fiche['fonctions'] ?? [];
        $user['fonctions'] = $fiche['fonctions'] ?? [];
        $user['departments'] = $fiche['departements'] ?? [];
        $user['departements'] = $fiche['departements'] ?? [];
        $user['services'] = $fiche['services'] ?? [];
        $user['teams'] = $fiche['equipes'] ?? [];
        $user['equipes'] = $fiche['equipes'] ?? [];
        $user['skills'] = $fiche['competences'] ?? $this->skills->getUserSkills($userId);
        $user['competences'] = $user['skills'];
        $user['qualifications'] = $fiche['certifications'] ?? $this->qualifications->getUserQualifications($userId);
        $user['certifications'] = $user['qualifications'];

        // ── B-01 : propagation de la hiérarchie ──────────────────────────
        // ficheComplete() remonte managers et subordinates mais ne les expose
        // pas sur $user. Sans cela la vue profil ne peut pas les afficher.
        $user['managers']     = $fiche['managers']     ?? [];
        $user['subordinates'] = $fiche['subordinates'] ?? [];

        return $user;
    }

    public function validateProfileUpdate(array $data, int $userId): array
    {
        $errors = [];
        $firstname = trim((string) ($data['firstname'] ?? $data['pui_prenom'] ?? ''));
        $lastname = trim((string) ($data['lastname'] ?? $data['pui_nom'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? $data['uti_email'] ?? '')));

        if ($firstname === '') {
            $errors['firstname'] = 'Le prenom est obligatoire.';
        }
        if ($lastname === '') {
            $errors['lastname'] = 'Le nom est obligatoire.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Adresse email invalide.';
        } elseif ($this->users->existsByEmail($email, $userId)) {
            $errors['email'] = 'Cette adresse email est deja utilisee.';
        }

        return $errors;
    }

    public function updatePersonalData(int $userId, array $data): array
    {
        $errors = $this->validateProfileUpdate($data, $userId);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Donnees invalides.'];
        }

        $this->users->updateUser($userId, [
            'use_email' => strtolower(trim((string) ($data['email'] ?? $data['uti_email'] ?? ''))),
            'use_civility' => trim((string) ($data['civility'] ?? $data['pui_civilite'] ?? '')) ?: null,
            'use_lastname' => strtoupper(trim((string) ($data['lastname'] ?? $data['pui_nom'] ?? ''))),
            'use_firstname' => ucfirst(strtolower(trim((string) ($data['firstname'] ?? $data['pui_prenom'] ?? '')))),
            'use_phone' => trim((string) ($data['phone'] ?? $data['pui_telephone'] ?? '')) ?: null,
            'use_mobile' => trim((string) ($data['mobile'] ?? $data['pui_mobile'] ?? '')) ?: null,
            'use_locale' => $data['locale'] ?? $data['uti_langue'] ?? 'fr',
            'use_timezone' => $data['timezone'] ?? 'Europe/Paris',
        ], $userId);

        logger('audit')->info('profile_updated_by_user', ['user_id' => $userId]);
        return ['success' => true, 'errors' => [], 'message' => 'Profil mis a jour.'];
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirm): array
    {
        $user = $this->users->findById($userId);
        if (!$user || !password_verify($currentPassword, (string) $user['use_password_hash'])) {
            return ['success' => false, 'errors' => ['current_password' => 'Mot de passe actuel invalide.']];
        }
        if ($newPassword !== $confirm || strlen($newPassword) < (defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 10)) {
            return ['success' => false, 'errors' => ['password' => 'Nouveau mot de passe invalide ou confirmation differente.']];
        }

        $this->users->updatePassword($userId, password_hash($newPassword, defined('HASH_ALGO') ? HASH_ALGO : PASSWORD_ARGON2ID));
        logger('security')->info('profile_password_changed', ['user_id' => $userId]);

        $email = (string) ($user['use_email'] ?? '');
        $this->notifier->notifierUtilisateurAvecEmail(
            $userId,
            $email,
            'securite.mot_de_passe_modifie',
            'Mot de passe modifié',
            'Votre mot de passe AutoSAV vient d\'être modifié. Si vous n\'êtes pas à l\'origine de ce changement, contactez immédiatement un administrateur.',
            createdBy: $userId
        );

        return ['success' => true, 'errors' => []];
    }

    public function createGdprRequest(int $userId, string $type, string $message, string $ip): int
    {
        return $this->gdprRequests->createRequest($userId, $type, $message, $ip);
    }

    public function getGdprRequests(int $userId): array
    {
        return $this->gdprRequests->getForUser($userId);
    }

    public function exportUserData(int $userId): array
    {
        return $this->getProfile($userId) ?? [];
    }
}

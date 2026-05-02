<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Profile\Services;

use Nenad\Autosav\Modules\Profile\Models\GdprRequestModel;
use Nenad\Autosav\Modules\Qualifications\Services\QualificationService;
use Nenad\Autosav\Modules\Skills\Services\SkillService;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use Nenad\Autosav\Modules\Users\Services\UserCompanyService;

class ProfileService
{
    public function __construct(
        private UserModel $users,
        private UserCompanyService $companies,
        private SkillService $skills,
        private QualificationService $qualifications,
        private GdprRequestModel $gdprRequests
    ) {}

    public function getProfile(int $userId): ?array
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            return null;
        }

        $user['companies'] = $this->companies->getUserCompanies($userId);
        $user['history'] = $this->companies->getUserHistory($userId);
        $user['skills'] = $this->skills->getUserSkills($userId);
        $user['qualifications'] = $this->qualifications->getUserQualifications($userId);
        return $user;
    }

    public function validateProfileUpdate(array $data, int $userId): array
    {
        $errors = [];
        $firstname = trim((string) ($data['firstname'] ?? ''));
        $lastname = trim((string) ($data['lastname'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));

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
            'use_email' => strtolower(trim((string) $data['email'])),
            'use_civility' => trim((string) ($data['civility'] ?? '')) ?: null,
            'use_lastname' => strtoupper(trim((string) $data['lastname'])),
            'use_firstname' => ucfirst(strtolower(trim((string) $data['firstname']))),
            'use_phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'use_mobile' => trim((string) ($data['mobile'] ?? '')) ?: null,
            'use_locale' => $data['locale'] ?? 'fr',
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

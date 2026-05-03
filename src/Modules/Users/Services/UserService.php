<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Services;

use Nenad\Autosav\Modules\Users\Models\UserCompanyHistoryModel;
use Nenad\Autosav\Modules\Users\Models\UserCompanyModel;
use Nenad\Autosav\Modules\Users\Models\UserModel;
use Nenad\Autosav\Modules\Users\Models\UserRoleModel;
use Nenad\Autosav\Core\Database\Database;

class UserService
{
    public function __construct(
        private UserModel $users,
        private UserRoleModel $roles,
        private UserCompanyModel $companies,
        private UserCompanyHistoryModel $history
    ) {}

    public function canCreateRole(int $creatorId, string $targetRoleCode): bool
    {
        $creatorRole = $this->roles->getPrimaryRole($creatorId);
        if (!$creatorRole) {
            return false;
        }

        if ($creatorRole['rol_code'] === 'SUPERADMIN') {
            return true;
        }

        $allowed = json_decode((string) ($creatorRole['rol_can_create_roles'] ?? '[]'), true);
        return is_array($allowed) && in_array(strtoupper($targetRoleCode), $allowed, true);
    }

    public function canManageUser(int $operatorId, int $targetId): bool
    {
        if ($operatorId === $targetId) {
            return true;
        }

        $operatorRole = $this->roles->getPrimaryRole($operatorId);
        $targetRole = $this->roles->getPrimaryRole($targetId);

        if (!$operatorRole || !$targetRole) {
            return false;
        }

        if ($operatorRole['rol_code'] === 'SUPERADMIN') {
            return true;
        }

        if ((int) $operatorRole['rol_level'] >= (int) $targetRole['rol_level']) {
            return false;
        }

        $operatorCompanyIds = array_map(
            'intval',
            array_column($this->companies->getUserCompanies($operatorId), 'ucm_company_id')
        );
        $targetCompanyIds = array_map(
            'intval',
            array_column($this->companies->getUserCompanies($targetId), 'ucm_company_id')
        );

        return array_intersect($operatorCompanyIds, $targetCompanyIds) !== [];
    }

    public function getCreatableRoles(int $creatorId): array
    {
        $creatorRole = $this->roles->getPrimaryRole($creatorId);
        if (!$creatorRole) {
            return [];
        }

        $allRoles = $this->roles->getActiveRoles();
        if ($creatorRole['rol_code'] === 'SUPERADMIN') {
            return $allRoles;
        }

        $allowed = json_decode((string) ($creatorRole['rol_can_create_roles'] ?? '[]'), true);
        if (!is_array($allowed) || $allowed === []) {
            return [];
        }

        return array_values(array_filter(
            $allRoles,
            static fn(array $role): bool => in_array($role['rol_code'], $allowed, true)
        ));
    }

    public function getManageableCompanies(int $operatorId): array
    {
        $role = $this->roles->getPrimaryRole($operatorId);
        if ($role && $role['rol_code'] === 'SUPERADMIN') {
            return Database::getInstance()->fetchAll(
                "SELECT c.com_id, c.com_name, c.com_city, ct.cty_label AS type_label
                 FROM sav_companies c
                 INNER JOIN sav_company_types ct ON ct.cty_id = c.com_type_id
                 WHERE c.com_deleted_at IS NULL AND c.com_is_active = 1
                 ORDER BY c.com_name ASC"
            );
        }

        return $this->companies->getUserCompanies($operatorId);
    }

    public function validateCreationData(array $data, int $creatorId): array
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
        } elseif ($this->users->existsByEmail($email)) {
            $errors['email'] = 'Cette adresse email est deja utilisee.';
        }

        $role = null;
        if (empty($data['role_id'])) {
            $errors['role_id'] = 'Le role est obligatoire.';
        } else {
            $role = $this->roles->findRoleById((int) $data['role_id']);
            if (!$role || !$this->canCreateRole($creatorId, (string) $role['rol_code'])) {
                $errors['role_id'] = 'Vous ne pouvez pas creer ce type de profil.';
            }
        }

        if (empty($data['company_id'])) {
            $errors['company_id'] = 'L entreprise principale est obligatoire.';
        }

        if (($data['password'] ?? '') !== '') {
            $errors = array_merge($errors, $this->validatePassword(
                (string) $data['password'],
                (string) ($data['password_confirm'] ?? '')
            ));
        }

        return $errors;
    }

    public function validateEditData(array $data, int $userId): array
    {
        $errors = [];
        if (trim((string) ($data['firstname'] ?? '')) === '') {
            $errors['firstname'] = 'Le prenom est obligatoire.';
        }
        if (trim((string) ($data['lastname'] ?? '')) === '') {
            $errors['lastname'] = 'Le nom est obligatoire.';
        }

        $email = strtolower(trim((string) ($data['email'] ?? '')));
        if ($email !== '') {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Adresse email invalide.';
            } elseif ($this->users->existsByEmail($email, $userId)) {
                $errors['email'] = 'Cette adresse email est deja utilisee.';
            }
        }

        if (($data['password'] ?? '') !== '') {
            $errors = array_merge($errors, $this->validatePassword(
                (string) $data['password'],
                (string) ($data['password_confirm'] ?? '')
            ));
        }

        return $errors;
    }

    public function validatePassword(string $password, string $confirm): array
    {
        $errors = [];
        $min = defined('PASSWORD_MIN_LENGTH') ? PASSWORD_MIN_LENGTH : 10;
        $max = defined('PASSWORD_MAX_LENGTH') ? PASSWORD_MAX_LENGTH : 128;

        if (strlen($password) < $min) {
            $errors['password'] = "Le mot de passe doit contenir au moins {$min} caracteres.";
        }
        if (strlen($password) > $max) {
            $errors['password_max'] = "Le mot de passe ne doit pas depasser {$max} caracteres.";
        }
        if (defined('PASSWORD_REQUIRE_UPPERCASE') && PASSWORD_REQUIRE_UPPERCASE && !preg_match('/[A-Z]/', $password)) {
            $errors['password_upper'] = 'Le mot de passe doit contenir une majuscule.';
        }
        if (defined('PASSWORD_REQUIRE_LOWERCASE') && PASSWORD_REQUIRE_LOWERCASE && !preg_match('/[a-z]/', $password)) {
            $errors['password_lower'] = 'Le mot de passe doit contenir une minuscule.';
        }
        if (defined('PASSWORD_REQUIRE_NUMBER') && PASSWORD_REQUIRE_NUMBER && !preg_match('/\d/', $password)) {
            $errors['password_number'] = 'Le mot de passe doit contenir un chiffre.';
        }
        if (defined('PASSWORD_REQUIRE_SPECIAL') && PASSWORD_REQUIRE_SPECIAL && !preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors['password_special'] = 'Le mot de passe doit contenir un caractere special.';
        }
        if ($password !== $confirm) {
            $errors['password_confirm'] = 'Les mots de passe ne correspondent pas.';
        }

        return $errors;
    }

    public function createUser(array $data, int $creatorId): array
    {
        $errors = $this->validateCreationData($data, $creatorId);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Donnees invalides.'];
        }

        $password = !empty($data['password'])
            ? (string) $data['password']
            : $this->generateTemporaryPassword();
        $verificationToken = bin2hex(random_bytes(32));

        try {
            $result = Database::getInstance()->transaction(function () use ($data, $creatorId, $password, $verificationToken): int {
                $userId = $this->users->create([
                    'uuid' => $this->uuid(),
                    'email' => strtolower(trim((string) $data['email'])),
                    'password_hash' => password_hash($password, defined('HASH_ALGO') ? HASH_ALGO : PASSWORD_ARGON2ID),
                    'civility' => trim((string) ($data['civility'] ?? '')) ?: null,
                    'lastname' => strtoupper(trim((string) $data['lastname'])),
                    'firstname' => ucfirst(strtolower(trim((string) $data['firstname']))),
                    'phone' => trim((string) ($data['phone'] ?? '')) ?: null,
                    'mobile' => trim((string) ($data['mobile'] ?? '')) ?: null,
                    'employee_number' => trim((string) ($data['employee_number'] ?? '')) ?: null,
                    'department' => trim((string) ($data['department'] ?? '')) ?: null,
                    'job_title' => trim((string) ($data['job_title'] ?? '')) ?: null,
                    'locale' => $data['locale'] ?? 'fr',
                    'timezone' => $data['timezone'] ?? 'Europe/Paris',
                    'verification_token' => $verificationToken,
                    'must_change_password' => (int) ($data['must_change_password'] ?? 1),
                    'created_by' => $creatorId,
                ]);

                $roleId = (int) $data['role_id'];
                $companyId = (int) $data['company_id'];
                $joinedAt = (string) ($data['joined_at'] ?? date('Y-m-d'));

                $this->roles->assignRole($userId, $roleId, $creatorId, true);
                $this->companies->attach($userId, $companyId, true, $joinedAt, $creatorId);
                $this->history->addEntry([
                    'user_id' => $userId,
                    'company_id' => $companyId,
                    'job_title' => trim((string) ($data['job_title'] ?? '')) ?: null,
                    'started_at' => $joinedAt,
                    'created_by' => $creatorId,
                ]);
                $this->users->updateActiveContext($userId, $companyId, null);

                return $userId;
            });

            logger('audit')->info('user_created', [
                'user_id' => $result,
                'created_by' => $creatorId,
                'email' => strtolower(trim((string) $data['email'])),
            ]);

            return [
                'success' => true,
                'user_id' => $result,
                'errors' => [],
                'message' => 'Utilisateur cree. La validation email reste obligatoire avant usage complet.',
                'temporary_password' => empty($data['password']) ? $password : null,
            ];
        } catch (\Throwable $e) {
            logger('error')->error('user_create_failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'errors' => ['general' => 'Erreur interne.'], 'message' => 'Creation impossible.'];
        }
    }

    public function updateUser(int $userId, array $data, int $operatorId): array
    {
        if (!$this->canManageUser($operatorId, $userId)) {
            return ['success' => false, 'errors' => [], 'message' => 'Acces refuse.'];
        }

        $errors = $this->validateEditData($data, $userId);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'message' => 'Donnees invalides.'];
        }

        $update = [
            'use_email' => strtolower(trim((string) ($data['email'] ?? ''))),
            'use_civility' => trim((string) ($data['civility'] ?? '')) ?: null,
            'use_lastname' => strtoupper(trim((string) ($data['lastname'] ?? ''))),
            'use_firstname' => ucfirst(strtolower(trim((string) ($data['firstname'] ?? '')))),
            'use_phone' => trim((string) ($data['phone'] ?? '')) ?: null,
            'use_mobile' => trim((string) ($data['mobile'] ?? '')) ?: null,
            'use_employee_number' => trim((string) ($data['employee_number'] ?? '')) ?: null,
            'use_department' => trim((string) ($data['department'] ?? '')) ?: null,
            'use_job_title' => trim((string) ($data['job_title'] ?? '')) ?: null,
            'use_locale' => $data['locale'] ?? 'fr',
            'use_timezone' => $data['timezone'] ?? 'Europe/Paris',
        ];

        $this->users->updateUser($userId, $update, $operatorId);

        if (($data['password'] ?? '') !== '') {
            $this->users->updatePassword(
                $userId,
                password_hash((string) $data['password'], defined('HASH_ALGO') ? HASH_ALGO : PASSWORD_ARGON2ID)
            );
        }

        logger('audit')->info('user_updated', ['user_id' => $userId, 'updated_by' => $operatorId]);
        return ['success' => true, 'errors' => [], 'message' => 'Utilisateur mis a jour.'];
    }

    public function deactivateUser(int $userId, int $operatorId): array
    {
        if (!$this->canManageUser($operatorId, $userId)) {
            return ['success' => false, 'message' => 'Acces refuse.'];
        }
        $this->users->setActiveState($userId, false, $operatorId);
        logger('audit')->info('user_deactivated', ['user_id' => $userId, 'operator_id' => $operatorId]);
        return ['success' => true, 'message' => 'Utilisateur desactive.'];
    }

    public function reactivateUser(int $userId, int $operatorId): array
    {
        if (!$this->canManageUser($operatorId, $userId)) {
            return ['success' => false, 'message' => 'Acces refuse.'];
        }
        $this->users->setActiveState($userId, true, $operatorId);
        logger('audit')->info('user_reactivated', ['user_id' => $userId, 'operator_id' => $operatorId]);
        return ['success' => true, 'message' => 'Utilisateur reactive.'];
    }

    public function getFullProfile(int $userId): ?array
    {
        $user = $this->users->findById($userId);
        if (!$user) {
            return null;
        }

        $user['roles'] = $this->roles->getUserRoles($userId);
        $user['companies'] = $this->companies->getUserCompanies($userId);
        return $user;
    }

    public function getFilteredUsers(array $filters, int $operatorId, int $page, int $perPage): array
    {
        $role = $this->roles->getPrimaryRole($operatorId);
        if (!$role) {
            return ['users' => [], 'total' => 0, 'pages' => 1, 'page' => 1];
        }

        if ($role['rol_code'] !== 'SUPERADMIN') {
            $filters['scope_company_ids'] = array_column($this->companies->getUserCompanies($operatorId), 'ucm_company_id');
        }

        return $this->users->getFiltered($filters, $page, $perPage);
    }

    private function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function generateTemporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        $password = '';
        for ($i = 0; $i < 16; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        return $password;
    }
}

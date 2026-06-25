<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Users\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;

use Nenad\Autosav\Core\Security\Class\PasswordManager;
use Nenad\Autosav\Core\Security\Class\RoleResolver;
use Nenad\Autosav\Modules\Users\Models\UserModel;

/**
 * Service Users aligné sur le modèle SQL français.
 */
class UserService implements ServiceInterface{
    public function __construct(private ?UserModel $model = null)
    {
        $this->model ??= new UserModel();
    }

    public function lister(array $filtres, int $utilisateurCourantId, int $page = 1, int $parPage = 25): array
    {
        $societes = $this->societesGerables($utilisateurCourantId);
        return $this->model->paginer($filtres, $page, $parPage, $this->estSuperAdminSession() ? null : $societes);
    }

    /** Compatibilité avec l'ancien contrôleur AJAX. */
    public function getFilteredUsers(array $filters, int $currentUserId, int $page = 1, int $perPage = 25): array
    {
        return $this->lister($filters, $currentUserId, $page, $perPage);
    }

    public function fiche(int $id): ?array
    {
        return $this->model->ficheComplete($id);
    }

    public function preparerFormulaire(?int $id = null, int $utilisateurCourantId = 0): array
    {
        $societesGerables = $this->estSuperAdminSession() ? null : $this->societesGerables($utilisateurCourantId);
        return [
            'fiche' => $id ? $this->model->ficheComplete($id) : null,
            'societes' => $this->filtrerSocietes($this->model->societesActives(), $societesGerables),
            'roles' => $this->rolesAttribuables($utilisateurCourantId),
            'fonctions' => $this->model->fonctionsActives($societesGerables),
            'departements' => $this->model->departementsActifs($societesGerables),
            'services' => $this->model->servicesActifs($societesGerables),
            'equipes' => $this->model->equipesActives($societesGerables),
            'competences' => $this->model->competencesActives($societesGerables),
            'niveaux_competences' => $this->model->niveauxCompetences(),
            'certifications' => $this->model->certificationsActives($societesGerables),
            'managers' => $this->model->utilisateursActifs($societesGerables, $id),
        ];
    }

    public function enregistrer(?int $id, array $input, int $utilisateurAction): array
    {
        $normalise = $this->normaliser($input, $id);
        $erreurs = $this->valider($normalise, $id);
        if ($erreurs !== []) {
            return ['success' => false, 'id' => $id, 'errors' => $erreurs, 'erreurs' => $erreurs];
        }

        $relations = $normalise['relations'];
        $societesGerables = $this->estSuperAdminSession() ? null : $this->societesGerables($utilisateurAction);
        $rolesAttribuables = array_map('intval', array_column($this->rolesAttribuables($utilisateurAction), 'rol_id'));
        $rolesDemandes = array_values(array_unique(array_map('intval', $relations['role_ids'] ?? [])));
        $rolesRefuses = array_diff($rolesDemandes, $rolesAttribuables);
        if ($rolesRefuses !== []) {
            $erreurs = ['roles' => 'Un ou plusieurs roles demandes ne peuvent pas etre attribues par cet utilisateur.'];
            return ['success' => false, 'id' => $id, 'errors' => $erreurs, 'erreurs' => $erreurs];
        }
        $relations['role_ids'] = array_values(array_intersect($rolesDemandes, $rolesAttribuables));
        if ($societesGerables !== null) {
            $relations['societe_ids'] = array_values(array_intersect($relations['societe_ids'], $societesGerables));
            if (!in_array((int) $relations['societe_principale_id'], $relations['societe_ids'], true)) {
                $relations['societe_principale_id'] = $relations['societe_ids'][0] ?? 0;
            }
            $relations['fonction_ids'] = $this->filtrerIdsParCatalogue($relations['fonction_ids'] ?? [], $this->model->fonctionsActives($societesGerables), 'fon_id');
            $relations['departement_ids'] = $this->filtrerIdsParCatalogue($relations['departement_ids'] ?? [], $this->model->departementsActifs($societesGerables), 'dep_id');
            $departementsGerables = $this->departementsGerables($utilisateurAction);
            if ($departementsGerables !== null) {
                // ACC-004 : un chef_de_departement ne peut creer/affecter un
                // utilisateur que dans son propre departement, pas n'importe
                // quel departement de la societe qu'il gere par ailleurs.
                $relations['departement_ids'] = array_values(array_intersect($relations['departement_ids'], $departementsGerables));
            }
            $relations['service_ids'] = $this->filtrerIdsParCatalogue($relations['service_ids'] ?? [], $this->model->servicesActifs($societesGerables), 'srv_id');
            $relations['equipe_ids'] = $this->filtrerIdsParCatalogue($relations['equipe_ids'] ?? [], $this->model->equipesActives($societesGerables), 'equ_id');
            $relations['competence_assignments'] = $this->filtrerAssignmentsParCatalogue($relations['competence_assignments'] ?? [], $this->model->competencesActives($societesGerables), 'cmp_id');
            $relations['certification_assignments'] = $this->filtrerAssignmentsParCatalogue($relations['certification_assignments'] ?? [], $this->model->certificationsActives($societesGerables), 'cer_id');
        }
        $relations['utilisateur_action_id'] = $utilisateurAction;

        if ($id === null) {
            $normalise['compte']['uti_cree_par_utilisateur_id'] = $utilisateurAction ?: null;
            $newId = $this->model->creer($normalise['compte'], $normalise['profil'], $normalise['donnees_sensibles'], $relations);
            return [
                'success' => true,
                'id' => $newId,
                'errors' => [],
                'erreurs' => [],
                'temporary_password' => $normalise['mot_de_passe_temporaire'],
            ];
        }

        $normalise['compte']['uti_modifie_par_utilisateur_id'] = $utilisateurAction ?: null;
        if (($normalise['compte']['uti_mot_de_passe_hash'] ?? null) === null) {
            unset($normalise['compte']['uti_mot_de_passe_hash'], $normalise['compte']['uti_doit_changer_mot_de_passe']);
        }
        $this->model->modifier($id, $normalise['compte'], $normalise['profil'], $normalise['donnees_sensibles'], $relations);
        return ['success' => true, 'id' => $id, 'errors' => [], 'erreurs' => []];
    }

    public function desactiver(int $id, int $utilisateurAction): bool
    {
        return $this->model->changerStatut($id, 'inactif', $utilisateurAction);
    }

    public function reactiver(int $id, int $utilisateurAction): bool
    {
        return $this->model->changerStatut($id, 'actif', $utilisateurAction);
    }

    public function supprimer(int $id, int $utilisateurAction): bool
    {
        return $this->model->supprimerLogiquement($id, $utilisateurAction);
    }

    public function canManageUser(int $currentUserId, int $targetUserId): bool
    {
        if ($this->estSuperAdminSession()) {
            return true;
        }
        if (!$this->peutAdministrerUtilisateurs()) {
            return false;
        }
        $currentCompanies = $this->societesGerables($currentUserId);
        if ($currentCompanies === []) {
            return false;
        }
        $targetCompanies = $this->model->idsSocietesUtilisateur($targetUserId);
        if (!array_intersect($currentCompanies, $targetCompanies)) {
            return false;
        }

        // Protection hiérarchique : un non-super-admin ne peut pas modifier
        // un compte de niveau égal ou supérieur, même dans la même société.
        $currentLevel = RoleResolver::applicationLevel((array) ($_SESSION['user']['role_codes'] ?? $_SESSION['user_roles'] ?? $_SESSION['user']['roles'] ?? []));
        $targetLevel = RoleResolver::applicationLevel(RoleResolver::extractCodes($this->model->rolesUtilisateur($targetUserId)));

        return $targetLevel < $currentLevel;
    }

    public function getCreatableRoles(int $currentUserId): array
    {
        return $this->rolesAttribuables($currentUserId);
    }

    public function canViewUser(int $currentUserId, int $targetUserId): bool
    {
        if ($currentUserId === $targetUserId || $this->estSuperAdminSession()) {
            return true;
        }
        if (!$this->peutConsulterUtilisateurs()) {
            return false;
        }
        return (bool) array_intersect(
            $this->societesGerables($currentUserId),
            $this->model->idsSocietesUtilisateur($targetUserId)
        );
    }

    public function getManageableCompanies(int $currentUserId): array
    {
        $ids = $this->estSuperAdminSession() ? null : $this->societesGerables($currentUserId);
        return $this->filtrerSocietes($this->model->societesActives(), $ids);
    }

    public function societesUtilisateur(int $id): array { return $this->model->societesUtilisateur($id); }
    public function managersUtilisateur(int $id): array { return $this->model->managersUtilisateur($id); }
    public function subordonnesUtilisateur(int $id, ?int $societeId = null): array { return $this->model->subordonnesUtilisateur($id, $societeId); }
    public function rechercher(string $query, int $currentUserId, int $limit = 10): array
    {
        $societes = $this->estSuperAdminSession() ? null : $this->societesGerables($currentUserId);
        return $this->model->rechercher($query, $societes, $limit);
    }

    private function normaliser(array $input, ?int $id): array
    {
        $email = trim((string) ($input['uti_email'] ?? $input['use_email'] ?? $input['email'] ?? ''));
        $emailNormalise = mb_strtolower($email);
        $identifiant = trim((string) ($input['uti_identifiant'] ?? $input['use_username'] ?? $input['username'] ?? $emailNormalise));
        $motDePasse = (string) ($input['password'] ?? $input['use_password'] ?? $input['uti_mot_de_passe'] ?? '');
        $motDePasseTemporaire = null;

        if ($id === null && $motDePasse === '') {
            $motDePasse = $this->genererMotDePasseTemporaire();
            $motDePasseTemporaire = $motDePasse;
        }

        $statutCode = trim((string) ($input['statut'] ?? $input['status'] ?? $input['uti_statut_code'] ?? 'actif')) ?: 'actif';
        $statutCode = match ($statutCode) {
            'active' => 'actif',
            'inactive' => 'inactif',
            'suspended' => 'suspendu',
            'security_blocked' => 'bloque_securite',
            default => $statutCode,
        };

        $societeIds = $this->idsDepuisInput($input, ['societe_ids', 'company_ids', 'companies']);
        $societePrincipale = (int) ($input['societe_principale_id'] ?? $input['uti_societe_active_id'] ?? $input['use_active_company_id'] ?? $input['company_id'] ?? ($societeIds[0] ?? 0));
        if ($societePrincipale > 0 && !in_array($societePrincipale, $societeIds, true)) {
            $societeIds[] = $societePrincipale;
        }

        $compte = [
            'uti_identifiant' => $identifiant ?: $emailNormalise,
            'uti_email' => $email,
            'uti_email_normalise' => $emailNormalise,
            'uti_statut_id' => $this->model->statutId('utilisateur', $statutCode, 16),
            'uti_est_verrouille' => in_array($statutCode, ['suspendu', 'bloque_securite'], true) ? 1 : 0,
            'uti_societe_active_id' => $societePrincipale > 0 ? $societePrincipale : null,
            'uti_marque_active_id' => $this->intOuNull($input['uti_marque_active_id'] ?? $input['use_active_brand_id'] ?? null),
            'uti_langue' => trim((string) ($input['uti_langue'] ?? $input['use_locale'] ?? 'fr')) ?: 'fr',
        ];

        if ($id === null) {
            $compte['uti_uuid'] = $this->uuidV4();
            $compte['uti_mot_de_passe_hash'] = PasswordManager::hash($motDePasse);
            $compte['uti_doit_changer_mot_de_passe'] = $motDePasseTemporaire !== null ? 1 : (!empty($input['must_change_password']) ? 1 : 0);
        } elseif ($motDePasse !== '') {
            $compte['uti_mot_de_passe_hash'] = PasswordManager::hash($motDePasse);
            $compte['uti_doit_changer_mot_de_passe'] = !empty($input['must_change_password']) ? 1 : 0;
            $compte['uti_mot_de_passe_modifie_le'] = date('Y-m-d H:i:s');
        }

        $profil = [
            'pui_nom' => trim((string) ($input['pui_nom'] ?? $input['use_lastname'] ?? $input['lastname'] ?? '')) ?: null,
            'pui_prenom' => trim((string) ($input['pui_prenom'] ?? $input['use_firstname'] ?? $input['firstname'] ?? '')) ?: null,
            'pui_civilite' => trim((string) ($input['pui_civilite'] ?? $input['use_civility'] ?? '')) ?: null,
            'pui_telephone' => trim((string) ($input['pui_telephone'] ?? $input['use_phone'] ?? '')) ?: null,
            'pui_mobile' => trim((string) ($input['pui_mobile'] ?? $input['use_mobile'] ?? '')) ?: null,
            'pui_adresse_rue' => trim((string) ($input['pui_adresse_rue'] ?? $input['use_address_street'] ?? '')) ?: null,
            'pui_adresse_ville' => trim((string) ($input['pui_adresse_ville'] ?? $input['use_address_city'] ?? '')) ?: null,
            'pui_adresse_code_postal' => trim((string) ($input['pui_adresse_code_postal'] ?? $input['use_address_zipcode'] ?? '')) ?: null,
            'pui_pays_id' => $this->intOuNull($input['pui_pays_id'] ?? null),
            'pui_date_naissance' => $this->dateOuNull($input['pui_date_naissance'] ?? $input['use_birthdate'] ?? null),
            'pui_lieu_naissance' => trim((string) ($input['pui_lieu_naissance'] ?? $input['use_birthplace'] ?? '')) ?: null,
        ];

        $donneesSensibles = array_filter([
            'dsu_numero_employe' => trim((string) ($input['dsu_numero_employe'] ?? $input['use_employee_number'] ?? '')) ?: null,
            'dsu_date_embauche' => $this->dateOuNull($input['dsu_date_embauche'] ?? $input['use_hire_date'] ?? null),
            'dsu_date_anciennete' => $this->dateOuNull($input['dsu_date_anciennete'] ?? $input['use_seniority_date'] ?? null),
            'dsu_type_contrat' => trim((string) ($input['dsu_type_contrat'] ?? $input['use_contract_type'] ?? '')) ?: null,
        ], static fn($v) => $v !== null && $v !== '');

        return [
            'compte' => $compte,
            'profil' => $profil,
            'donnees_sensibles' => $donneesSensibles,
            'relations' => [
                'societe_ids' => $societeIds,
                'societe_principale_id' => $societePrincipale,
                'role_ids' => $this->idsDepuisInput($input, ['role_ids', 'roles']),
                'fonction_ids' => $this->idsDepuisInput($input, ['fonction_ids', 'function_ids', 'functions']),
                'departement_ids' => $this->idsDepuisInput($input, ['departement_ids', 'department_ids']),
                'service_ids' => $this->idsDepuisInput($input, ['service_ids']),
                'equipe_ids' => $this->idsDepuisInput($input, ['equipe_ids', 'team_ids']),
                'competence_assignments' => $this->competenceAssignmentsDepuisInput($input),
                'certification_assignments' => $this->certificationAssignmentsDepuisInput($input),
                'manager_user_id' => (int) ($input['manager_user_id'] ?? $input['use_manager_id'] ?? 0),
            ],
            'mot_de_passe' => $motDePasse,
            'mot_de_passe_temporaire' => $motDePasseTemporaire,
        ];
    }

    private function valider(array $normalise, ?int $id): array
    {
        $errors = [];
        $email = $normalise['compte']['uti_email'] ?? '';
        $emailNormalise = $normalise['compte']['uti_email_normalise'] ?? '';

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email obligatoire et valide.';
        } elseif ($this->model->emailExiste($emailNormalise, $id)) {
            $errors['email'] = 'Un utilisateur existe déjà avec cet email.';
        }

        if (($normalise['profil']['pui_nom'] ?? '') === '') {
            $errors['lastname'] = 'Le nom est obligatoire.';
        }
        if (($normalise['profil']['pui_prenom'] ?? '') === '') {
            $errors['firstname'] = 'Le prénom est obligatoire.';
        }
        if (($normalise['relations']['societe_principale_id'] ?? 0) <= 0) {
            $errors['company'] = 'La société principale est obligatoire.';
        }

        $password = $normalise['mot_de_passe'] ?? '';
        if ($password !== '') {
            $passwordErrors = PasswordManager::validate($password);
            if ($passwordErrors !== []) {
                $errors['password'] = implode(' ', $passwordErrors);
            }
        }

        return $errors;
    }

    private function societesGerables(int $utilisateurId): array
    {
        return $this->model->idsSocietesUtilisateur($utilisateurId);
    }

    /**
     * Restreint les departements affectables aux seuls departements de
     * l'acteur lorsqu'il est chef_de_departement (ACC-004 : "Un
     * chef_departement ne peut creer qu'un utilisateur de role inferieur
     * dans son propre departement."). Retourne null si l'acteur n'est pas
     * chef_de_departement (pas de restriction supplementaire au-dela de
     * societesGerables, deja appliquee ailleurs).
     */
    private function departementsGerables(int $utilisateurId): ?array
    {
        if ($this->estSuperAdminSession()) {
            return null;
        }

        $roleCodes = array_map(
            static fn($r): string => mb_strtolower(trim((string) $r)),
            (array) ($_SESSION['user']['role_codes'] ?? $_SESSION['user_roles'] ?? $_SESSION['user']['roles'] ?? [])
        );

        if (!in_array('chef_de_departement', $roleCodes, true)) {
            return null;
        }

        return array_map('intval', array_column($this->model->departementsUtilisateur($utilisateurId), 'udp_departement_id'));
    }

    private function rolesAttribuables(int $utilisateurId): array
    {
        $roles = $this->model->rolesActifs($this->estSuperAdminSession() ? null : $this->societesGerables($utilisateurId));
        if ($this->estSuperAdminSession()) {
            return $roles;
        }
        $currentRoleCodes = (array) ($_SESSION['user']['role_codes'] ?? $_SESSION['user_roles'] ?? $_SESSION['user']['roles'] ?? []);
        $currentLevel = RoleResolver::applicationLevel($currentRoleCodes);

        return array_values(array_filter($roles, static function (array $role) use ($currentLevel): bool {
            $code = mb_strtolower(trim((string) ($role['rol_code'] ?? '')));

            // Règle métier : seul super_admin peut créer/attribuer un PDG ou un rôle plateforme global.
            if (in_array($code, ['super_administrateur', 'super_admin', 'superadmin', 'pdg'], true)) {
                return false;
            }

            // Les autres comptes ne peuvent attribuer que des rôles strictement inférieurs.
            return RoleResolver::applicationLevel([$code]) < $currentLevel;
        }));
    }

    private function peutConsulterUtilisateurs(): bool
    {
        return function_exists('has_permission') && has_permission('users.read');
    }

    private function peutAdministrerUtilisateurs(): bool
    {
        return function_exists('has_permission') && (
            has_permission('utilisateur.creer')
            || has_permission('utilisateur.modifier')
            || has_permission('utilisateur.bloquer')
        );
    }

    private function filtrerSocietes(array $societes, ?array $idsAutorises): array
    {
        if ($idsAutorises === null) {
            return $societes;
        }
        $ids = array_flip(array_map('intval', $idsAutorises));
        return array_values(array_filter($societes, static fn(array $s) => isset($ids[(int) ($s['soc_id'] ?? 0)])));
    }


    private function filtrerIdsParCatalogue(array $ids, array $catalogue, string $idKey): array
    {
        $autorises = array_map('intval', array_column($catalogue, $idKey));
        return array_values(array_intersect(array_values(array_unique(array_map('intval', $ids))), $autorises));
    }

    private function filtrerAssignmentsParCatalogue(array $assignments, array $catalogue, string $idKey): array
    {
        $autorises = array_flip(array_map('intval', array_column($catalogue, $idKey)));
        return array_filter(
            $assignments,
            static fn(int|string $id): bool => isset($autorises[(int) $id]),
            ARRAY_FILTER_USE_KEY
        );
    }

    private function competenceAssignmentsDepuisInput(array $input): array
    {
        $ids = $this->idsDepuisInput($input, ['competence_ids', 'skill_ids']);
        $levels = is_array($input['competence_level_ids'] ?? null)
            ? $input['competence_level_ids']
            : (is_array($input['skill_level_ids'] ?? null) ? $input['skill_level_ids'] : []);
        $assignments = [];
        foreach ($ids as $id) {
            $assignments[$id] = [
                'level_id' => (int) ($levels[$id] ?? 0),
            ];
        }
        return $assignments;
    }

    private function certificationAssignmentsDepuisInput(array $input): array
    {
        $ids = $this->idsDepuisInput($input, ['certification_ids', 'qualification_ids']);
        $issued = is_array($input['certification_issued_at'] ?? null) ? $input['certification_issued_at'] : [];
        $expires = is_array($input['certification_expires_at'] ?? null) ? $input['certification_expires_at'] : [];
        $assignments = [];
        foreach ($ids as $id) {
            $assignments[$id] = [
                'issued_at' => $this->dateOuNull($issued[$id] ?? null),
                'expires_at' => $this->dateOuNull($expires[$id] ?? null),
            ];
        }
        return $assignments;
    }

    private function idsDepuisInput(array $input, array $keys): array
    {
        foreach ($keys as $key) {
            if (!isset($input[$key])) {
                continue;
            }
            $value = $input[$key];
            if (is_string($value)) {
                $value = preg_split('/[,;]+/', $value) ?: [];
            }
            if (!is_array($value)) {
                $value = [$value];
            }
            return array_values(array_unique(array_filter(array_map('intval', $value))));
        }
        return [];
    }

    private function intOuNull(mixed $value): ?int
    {
        $int = (int) $value;
        return $int > 0 ? $int : null;
    }

    private function dateOuNull(mixed $value): ?string
    {
        $value = trim((string) $value);
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) ? $value : null;
    }

    private function genererMotDePasseTemporaire(): string
    {
        return 'Auto!' . bin2hex(random_bytes(8)) . 'Aa1';
    }

    private function uuidV4(): string
    {
        if (function_exists('generate_uuid')) {
            return (string) generate_uuid();
        }
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function estSuperAdminSession(): bool
    {
        $roles = (array) ($_SESSION['user']['role_codes'] ?? $_SESSION['user_roles'] ?? $_SESSION['user']['roles'] ?? []);
        return RoleResolver::isSuperAdmin($roles);
    }
}

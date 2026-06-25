<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Services;

use Nenad\Autosav\Core\Services\Contracts\ServiceInterface;
use Nenad\Autosav\Core\Security\Class\PasswordManager;
use Nenad\Autosav\Core\Security\Class\RoleResolver;
use Nenad\Autosav\Core\Security\Class\SessionHandler;
use Nenad\Autosav\Core\Security\Class\SuperAdminAccessGuard;
use Nenad\Autosav\Modules\Auth\Models\AuthModel;

/**
 * AUTOSAV — Service d'authentification
 *
 * CORRECTIFS v4.3.1 :
 *   MAJ-1 CRLF : Fichier resauvegardé en UTF-8 LF uniquement.
 *   MAJ-1 SIGN : hydraterSession() accepte maintenant le bloc RoleResolver
 *                déjà construit dans authentifier() (évite un double appel
 *                à buildSessionBlock() — cohérence de données garantie).
 */
class AuthService implements ServiceInterface
{
    private AuthModel $model;

    public function __construct(?AuthModel $model = null)
    {
        $this->model = $model ?? new AuthModel();
    }

    public function authentifier(string $identifiant, string $motDePasse): array
    {
        $identifiant = trim($identifiant);
        $motDePasse  = (string) $motDePasse;

        if ($identifiant === '' || $motDePasse === '') {
            $this->model->enregistrerTentativeConnexion(null, $identifiant, false, 'identifiants_manquants');
            return $this->echec('Veuillez saisir votre email/identifiant et votre mot de passe.');
        }

        $utilisateur = $this->model->trouverUtilisateurParIdentifiant($identifiant);
        if (!$utilisateur) {
            $this->model->enregistrerTentativeConnexion(null, $identifiant, false, 'utilisateur_introuvable');
            $this->pauseEchec();
            return $this->echec('Identifiants invalides.');
        }

        $id = (int) $utilisateur['uti_id'];

        if (!$this->compteAutorise($utilisateur)) {
            $this->model->enregistrerTentativeConnexion($id, $identifiant, false, 'compte_inactif_ou_verrouille');
            return $this->echec('Compte inactif, verrouillé ou supprimé.');
        }

        if (!PasswordManager::verify($motDePasse, (string) $utilisateur['uti_mot_de_passe_hash'])) {
            $this->model->enregistrerTentativeConnexion($id, $identifiant, false, 'mot_de_passe_incorrect');
            $this->model->enregistrerEchecUtilisateur($id, 'mot_de_passe_incorrect');
            $this->pauseEchec();
            return $this->echec('Identifiants invalides.');
        }

        $societes = $this->model->listerSocietesAccessibles($id);
        if ($societes === [] && empty($utilisateur['uti_est_systeme'])) {
            $this->model->enregistrerTentativeConnexion($id, $identifiant, false, 'aucune_societe_accessible');
            return $this->echec("Aucune société accessible n'est rattachée à ce compte.");
        }

        $contexte    = $this->resoudreContexteInitial($utilisateur, $societes);
        $roles       = $this->model->listerRoles($id, $contexte['societe_id'], null, $contexte['marque_id']);
        $permissions = $this->model->listerPermissions($id, $contexte['societe_id'], null, $contexte['marque_id']);

        // Un seul appel à buildSessionBlock — le bloc est ensuite passé à hydraterSession
        // pour éviter toute divergence entre les droits chargés et ceux stockés en session.
        $block = RoleResolver::buildSessionBlock($roles, $permissions);

        if (!empty($utilisateur['uti_mot_de_passe_hash'])
            && PasswordManager::needsRehash((string) $utilisateur['uti_mot_de_passe_hash'])) {
            // Réhash transparent : à planifier dans un lot sécurité dédié.
        }

        SessionHandler::regenerate();
        $sessionId              = $this->model->creerSessionPersistante($id, $contexte);
        $contexte['session_id'] = $sessionId;

        $this->hydraterSession($utilisateur, $societes, $roles, $block, $contexte);
        $this->model->enregistrerSuccesUtilisateur($id, $contexte['societe_id'], $contexte['marque_id']);
        $this->model->enregistrerTentativeConnexion($id, $identifiant, true, null);
        $this->model->journaliserContexte($id, $contexte, 'login');

        return [
            'success'                  => true,
            'user'                     => $_SESSION['user'],
            'redirect'                 => $_SESSION['redirect_after_login'] ?? '/dashboard',
            'requires_password_change' => (bool) ($utilisateur['uti_doit_changer_mot_de_passe'] ?? false),
            'requires_2fa'             => false,
        ];
    }

    public function deconnecter(string $motif = 'logout'): void
    {
        $sessionId = (int) ($_SESSION['security']['session_db_id'] ?? $_SESSION['auth_session_id'] ?? 0);
        if ($sessionId > 0) {
            $this->model->revoquerSessionPersistante($sessionId, $motif);
        }

        if (!empty($_SESSION['user']['id'])) {
            $this->model->journaliserContexte((int) $_SESSION['user']['id'], [
                'session_id'    => $sessionId ?: null,
                'societe_id'    => $_SESSION['user']['actual_society_id']   ?? $_SESSION['active_company_id']   ?? null,
                'concession_id' => $_SESSION['user']['actual_concession_id'] ?? null,
                'marque_id'     => $_SESSION['user']['actual_brand']          ?? $_SESSION['active_brand_id']     ?? null,
                'service_id'    => $_SESSION['user']['actual_service_id']     ?? null,
                'equipe_id'     => $_SESSION['user']['actual_team_id']        ?? null,
            ], $motif);
        }

        SuperAdminAccessGuard::cloturerTout();
        SessionHandler::destroy();
    }

    public function actualiserSession(): bool
    {
        if (!$this->estAuthentifie()) {
            return false;
        }

        $this->model->actualiserSessionPersistante(
            (int) ($_SESSION['security']['session_db_id'] ?? 0),
            [
                'societe_id'    => $_SESSION['user']['actual_society_id']    ?? $_SESSION['active_company_id']   ?? null,
                'concession_id' => $_SESSION['user']['actual_concession_id'] ?? null,
                'marque_id'     => $_SESSION['user']['actual_brand']          ?? $_SESSION['active_brand_id']     ?? null,
                'service_id'    => $_SESSION['user']['actual_service_id']     ?? null,
                'equipe_id'     => $_SESSION['user']['actual_team_id']        ?? null,
            ]
        );

        return true;
    }

    public function estAuthentifie(): bool
    {
        return !empty($_SESSION['user']['id']) || !empty($_SESSION['user_id']);
    }

    public function genererTokenSecurite(int $utilisateurId): string
    {
        $token = bin2hex(random_bytes(32));
        $this->model->stockerTokenSecurite($utilisateurId, $token);
        return $token;
    }

    public function verifierEmail(string $token): bool
    {
        $row = $this->model->trouverTokenSecurite($token);
        if (!$row) {
            return false;
        }
        $this->model->marquerEmailVerifie((int) $row['uti_id']);
        return true;
    }

    public function demanderReinitialisation(string $email): array
    {
        $user = $this->model->trouverUtilisateurParIdentifiant($email);
        if (!$user) {
            // Réponse volontairement neutre contre l'énumération d'emails.
            return ['success' => true, 'token' => null];
        }
        $token = $this->genererTokenSecurite((int) $user['uti_id']);
        return ['success' => true, 'token' => $token, 'email' => $user['uti_email'] ?? $email];
    }

    public function reinitialiserMotDePasse(string $token, string $password, string $confirmation): array
    {
        if ($token === '') {
            return $this->echec('Token invalide.');
        }
        if ($password !== $confirmation) {
            return $this->echec('Les deux mots de passe ne correspondent pas.');
        }
        $errors = PasswordManager::validate($password);
        if ($errors !== []) {
            return ['success' => false, 'errors' => $errors, 'message' => implode(' ', $errors)];
        }
        $row = $this->model->trouverTokenSecurite($token);
        if (!$row) {
            return $this->echec('Token expiré ou invalide.');
        }
        $this->model->mettreAJourMotDePasse((int) $row['uti_id'], PasswordManager::hash($password));
        return ['success' => true];
    }

    public function accepterDernieresConditions(?int $utilisateurId): bool
    {
        if (!$utilisateurId) {
            return false;
        }
        $document = $this->model->trouverDernierDocumentJuridique('cgu')
            ?? $this->model->trouverDernierDocumentJuridique('conditions_generales')
            ?? $this->model->trouverDernierDocumentJuridique('terms');
        if (!$document) {
            $_SESSION['security']['terms_accepted'] = true;
            return true;
        }
        $documentId = (int) $document['dju_id'];
        $version    = (string) ($document['dju_version'] ?? '1');
        if (!$this->model->aAccepteDocument($utilisateurId, $documentId, $version)) {
            $this->model->enregistrerAcceptationDocument($utilisateurId, $documentId, $version);
        }
        $_SESSION['security']['terms_accepted']    = true;
        $_SESSION['security']['terms_document_id'] = $documentId;
        $_SESSION['security']['terms_version']     = $version;
        return true;
    }

    // ----------------------------------------------------------------
    // MÉTHODES PRIVÉES
    // ----------------------------------------------------------------

    private function compteAutorise(array $utilisateur): bool
    {
        if (!empty($utilisateur['uti_supprime_le']) || !empty($utilisateur['uti_anonymise_le'])) {
            return false;
        }
        if (!empty($utilisateur['uti_est_verrouille'])) {
            $jusqua = (string) ($utilisateur['uti_verrouille_jusqua'] ?? '');
            if ($jusqua === '' || strtotime($jusqua) > time()) {
                return false;
            }
        }
        $statut = mb_strtolower((string) ($utilisateur['statut_code'] ?? ''));
        if ($statut !== '' && in_array($statut, [
            'inactive', 'inactif', 'suspended', 'suspendu',
            'blocked', 'bloque', 'deleted', 'supprime',
        ], true)) {
            return false;
        }
        return true;
    }

    private function resoudreContexteInitial(array $utilisateur, array $societes): array
    {
        $societeActive = !empty($utilisateur['uti_societe_active_id'])
            ? (int) $utilisateur['uti_societe_active_id']
            : null;
        $societeIds = array_map(static fn(array $s): int => (int) $s['soc_id'], $societes);
        if (!$societeActive || !in_array($societeActive, $societeIds, true)) {
            $societeActive = $societeIds[0] ?? null;
        }

        $marques      = $this->model->listerMarquesRepresentees($societeActive);
        $marqueActive = !empty($utilisateur['uti_marque_active_id'])
            ? (int) $utilisateur['uti_marque_active_id']
            : null;
        $marqueIds = array_map(static fn(array $m): int => (int) $m['soc_id'], $marques);
        if ($marqueActive && !in_array($marqueActive, $marqueIds, true)) {
            $marqueActive = null;
        }
        if (!$marqueActive && count($marqueIds) === 1) {
            $marqueActive = $marqueIds[0];
        }

        // PROJET.md §6 : la concession active est initialisée sur la société
        // à la connexion ; elle sera affinée depuis la sidebar.
        return [
            'societe_id'    => $societeActive,
            'concession_id' => $societeActive,
            'marque_id'     => $marqueActive,
            'service_id'    => null,
            'equipe_id'     => null,
            'marques'       => $marques,
        ];
    }

    /**
     * Hydrate la session après authentification réussie.
     *
     * Signature modifiée v4.3.1 : reçoit le $block RoleResolver déjà
     * construit dans authentifier() pour éviter un double buildSessionBlock().
     *
     * @param array $block  Résultat de RoleResolver::buildSessionBlock()
     *                      ['role_codes','role_names','permissions','level','is_super_admin']
     */
    private function hydraterSession(
        array $utilisateur,
        array $societes,
        array $roles,
        array $block,
        array $contexte
    ): void {
        $roleCodes       = $block['role_codes'];
        $societeActive   = $contexte['societe_id']    ? $this->model->trouverSociete((int) $contexte['societe_id'])    : null;
        $concessionActive = $contexte['concession_id'] ? $this->model->trouverSociete((int) $contexte['concession_id']) : null;
        $marqueActive    = $contexte['marque_id']     ? $this->model->trouverSociete((int) $contexte['marque_id'])     : null;

        $nomComplet = trim((string) ($utilisateur['pui_prenom'] ?? '') . ' ' . (string) ($utilisateur['pui_nom'] ?? ''));

        $_SESSION['user'] = [
            'id'                   => (int) $utilisateur['uti_id'],
            'uuid'                 => (string) ($utilisateur['uti_uuid']             ?? ''),
            'email'                => (string) ($utilisateur['uti_email']            ?? ''),
            'email_normalise'      => (string) ($utilisateur['uti_email_normalise']  ?? ''),
            'username'             => (string) ($utilisateur['uti_identifiant']      ?? ''),
            'firstname'            => (string) ($utilisateur['pui_prenom']           ?? ''),
            'lastname'             => (string) ($utilisateur['pui_nom']              ?? ''),
            'name'                 => $nomComplet !== '' ? $nomComplet : (string) ($utilisateur['uti_email'] ?? ''),
            'roles'                => $roleCodes,
            'role_codes'           => $roleCodes,
            'level'                => $block['level'],
            'role_names'           => $block['role_names'],
            'permissions'          => $block['permissions'],
            'actual_society_id'    => $contexte['societe_id'],
            'actual_concession_id' => $contexte['concession_id'],
            'actual_brand'         => $contexte['marque_id'],
            'actual_service_id'    => $contexte['service_id'],
            'actual_team_id'       => $contexte['equipe_id'],
        ];

        $_SESSION['society']    = $societeActive    ?: [];
        $_SESSION['concession'] = $concessionActive ?: ($societeActive ?: []);
        $_SESSION['marque']     = $marqueActive     ?: [];
        $_SESSION['permissions'] = $block['permissions'];
        $_SESSION['security']   = [
            'session_db_id'         => $contexte['session_id'] ?? null,
            'roles_loaded_at'       => date('c'),
            'permissions_loaded_at' => date('c'),
            'acl_version'           => (int) ($utilisateur['uti_acl_version'] ?? 0),
            '2fa_enabled'           => (bool) ($utilisateur['psu_2fa_active']            ?? false),
            '2fa_method'            => $utilisateur['psu_2fa_methode']                   ?? null,
            'must_change_password'  => (bool) ($utilisateur['uti_doit_changer_mot_de_passe'] ?? false),
        ];

        // Compatibilité avec les clés de session historiques
        $_SESSION['user_id']             = (int) $utilisateur['uti_id'];
        $_SESSION['user_email']          = (string) ($utilisateur['uti_email']   ?? '');
        $_SESSION['user_firstname']      = (string) ($utilisateur['pui_prenom']  ?? '');
        $_SESSION['user_lastname']       = (string) ($utilisateur['pui_nom']     ?? '');
        $_SESSION['user_roles']          = $roleCodes;
        $_SESSION['user_level']          = $block['level'];
        $_SESSION['user_permissions']    = $block['permissions'];
        $_SESSION['active_company_id']   = $contexte['societe_id'];
        $_SESSION['active_concession_id']= $contexte['concession_id'];
        $_SESSION['active_brand_id']     = $contexte['marque_id'];
        $_SESSION['auth_session_id']     = $contexte['session_id'] ?? null;
        $_SESSION['available_companies'] = $societes;
        $_SESSION['available_brands']    = $contexte['marques'] ?? [];
    }

    private function pauseEchec(): void
    {
        $seconds = defined('AUTH_FAILURE_DELAY_SECONDS') ? (int) AUTH_FAILURE_DELAY_SECONDS : 1;
        if ($seconds > 0 && $seconds <= 5) {
            sleep($seconds);
        }
    }

    private function echec(string $message): array
    {
        return ['success' => false, 'message' => $message, 'errors' => [$message]];
    }
}
<?php
declare(strict_types=1);
namespace Nenad\Autosav\Core\Middleware;

require_once dirname(__DIR__) . '/Helpers/AuthHelper.php';

use Nenad\Autosav\Core\Database\Database;
use Nenad\Autosav\Core\Security\Class\RoleResolver;
use Nenad\Autosav\Core\Security\Class\SessionHandler;
use Nenad\Autosav\Modules\Auth\Models\AuthModel;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

/**
 * AUTOSAV — Middleware d'authentification
 * Vérifie que l'utilisateur est bien connecté.
 */
class AuthMiddleware
{
    private const SESSION_TOUCH_INTERVAL = 60;
    private const ACL_CHECK_INTERVAL = 60;

    /**
     * Routes accessibles même lorsque le changement de mot de passe est obligatoire.
     * Correspondance exacte sur le chemin (sans query string).
     */
    private const PASSWORD_CHANGE_EXEMPT_PATHS = [
        '/profile',
        '/profile/password',
        '/logout',
        '/auth/logout',
    ];

    /**
     * Préfixes de routes toujours autorisés (widgets de la coquille applicative :
     * notifications, sélecteurs de contexte, etc.) pour ne pas casser l'affichage
     * de la page /profile pendant un changement de mot de passe forcé.
     */
    private const PASSWORD_CHANGE_EXEMPT_PREFIXES = [
        '/ajax/',
    ];

    public static function check(): void
    {
        if (!is_authenticated()) {
            // Sauvegarder l'URL demandée pour redirect post-login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/dashboard';

            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(401);
                echo json_encode([
                    'success' => false, 'code' => 401,
                    'message' => 'Session expirée. Veuillez vous reconnecter.',
                    'data' => null, 'errors' => null,
                ]);
                exit;
            }
            header('Location: ' . url('/auth/login'), true, 302);
            exit;
        }

        self::enforcePasswordChange();

        // Session applicative persistante : actualisation légère et tolérante.
        // Ne bloque jamais l'utilisateur si la BDD de session est indisponible.
        $lastTouch = (int) ($_SESSION['security']['last_session_touch'] ?? 0);
        if (time() - $lastTouch > self::SESSION_TOUCH_INTERVAL) {
            try {
                (new AuthService())->actualiserSession();
                $_SESSION['security']['last_session_touch'] = time();
            } catch (\Throwable) {
                // L'authentification PHP reste la source immédiate pendant la requête.
            }
        }
        self::refreshAclIfNeeded();
    }

    /**
     * AUDIT 2026-06-21 — correctif point 4.2 :
     * Bloque l'accès à l'application tant que le mot de passe temporaire
     * (compte créé par un administrateur, compte de démonstration, etc.)
     * n'a pas été changé. Le flag est positionné en session lors de la
     * connexion (AuthService::hydraterSession -> $_SESSION['security']['must_change_password'])
     * à partir de sav_utilisateurs.uti_doit_changer_mot_de_passe, et remis à
     * zéro par AuthModel::mettreAJourMotDePasse() lors d'un changement de
     * mot de passe réussi.
     */
    private static function enforcePasswordChange(): void
    {
        $mustChange = (bool) ($_SESSION['security']['must_change_password'] ?? false);
        if (!$mustChange) {
            return;
        }

        $path = strtok((string) ($_SERVER['REQUEST_URI'] ?? '/'), '?') ?: '/';
        $path = '/' . trim($path, '/');
        if ($path === '') {
            $path = '/';
        }

        if (in_array($path, self::PASSWORD_CHANGE_EXEMPT_PATHS, true)) {
            return;
        }
        foreach (self::PASSWORD_CHANGE_EXEMPT_PREFIXES as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return;
            }
        }

        if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
            header('Content-Type: application/json; charset=UTF-8');
            http_response_code(403);
            echo json_encode([
                'success' => false, 'code' => 403,
                'message' => 'Changement de mot de passe obligatoire avant de continuer.',
                'data' => null, 'errors' => null,
            ]);
            exit;
        }

        $_SESSION['flash']['warning'][] = 'Pour des raisons de sécurité, vous devez changer votre mot de passe avant de continuer.';
        header('Location: ' . url('/profile') . '#security', true, 302);
        exit;
    }

    private static function refreshAclIfNeeded(): void
    {
        $userId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);
        if ($userId <= 0) {
            return;
        }

        $lastCheck = (int) ($_SESSION['security']['acl_checked_at'] ?? 0);
        if (time() - $lastCheck <= self::ACL_CHECK_INTERVAL) {
            return;
        }
        $_SESSION['security']['acl_checked_at'] = time();

        try {
            $etat = Database::getInstance()->fetch(
                'SELECT uti_acl_version, uti_supprime_le, uti_est_verrouille, uti_verrouille_jusqua
                   FROM sav_utilisateurs WHERE uti_id = :id LIMIT 1',
                ['id' => $userId]
            );
        } catch (\Throwable) {
            return;
        }

        if ($etat === null) {
            return;
        }

        // AUDIT 2026-06-21 — correctif point 4.6 :
        // Un compte bloqué/désactivé après l'ouverture de session ne devait
        // jusqu'ici JAMAIS être déconnecté de force : la session PHP restait
        // valide jusqu'à expiration naturelle. On profite du même sondage
        // périodique pour couper la session dès détection (sous 60s).
        $estBloque = !empty($etat['uti_supprime_le'])
            || !empty($etat['uti_est_verrouille'])
            || (!empty($etat['uti_verrouille_jusqua']) && strtotime((string) $etat['uti_verrouille_jusqua']) > time());

        if ($estBloque) {
            SessionHandler::destroy();
            if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest') {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(401);
                echo json_encode([
                    'success' => false, 'code' => 401,
                    'message' => 'Votre compte a été suspendu. Veuillez contacter un administrateur.',
                    'data' => null, 'errors' => null,
                ]);
                exit;
            }
            header('Location: ' . url('/auth/login?suspended=1'), true, 302);
            exit;
        }

        $dbVersion = (int) ($etat['uti_acl_version'] ?? 0);
        if ($dbVersion <= (int) ($_SESSION['security']['acl_version'] ?? 0)) {
            return;
        }

        try {
            $companyId = (int) ($_SESSION['active_company_id'] ?? $_SESSION['user']['active_company_id'] ?? $_SESSION['user']['actual_society_id'] ?? 0);
            $brandId = (int) ($_SESSION['active_brand_id'] ?? $_SESSION['user']['active_brand_id'] ?? $_SESSION['user']['actual_brand'] ?? 0);
            $model = new AuthModel();
            $roles = $model->listerRoles($userId, $companyId ?: null, null, $brandId ?: null);
            $permissions = $model->listerPermissions($userId, $companyId ?: null, null, $brandId ?: null);
            $block = RoleResolver::buildSessionBlock($roles, $permissions);

            $_SESSION['user']['roles'] = $block['role_codes'];
            $_SESSION['user']['role_codes'] = $block['role_codes'];
            $_SESSION['user']['role_names'] = $block['role_names'];
            $_SESSION['user']['permissions'] = $block['permissions'];
            $_SESSION['user']['level'] = $block['level'];
            $_SESSION['permissions'] = $block['permissions'];
            $_SESSION['user_roles'] = $block['role_codes'];
            $_SESSION['user_permissions'] = $block['permissions'];
            $_SESSION['user_level'] = $block['level'];
            $_SESSION['security']['acl_version'] = $dbVersion;
            $_SESSION['security']['roles_loaded_at'] = date('c');
            $_SESSION['security']['permissions_loaded_at'] = date('c');
        } catch (\Throwable) {
            return;
        }
    }
}
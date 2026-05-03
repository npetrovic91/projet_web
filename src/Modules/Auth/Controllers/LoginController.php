<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Security\SecurityManager;
use Nenad\Autosav\Modules\Auth\Models\IpBlacklistModel;
use Nenad\Autosav\Modules\Auth\Models\EmailBlacklistModel;
use Nenad\Autosav\Modules\Auth\Models\LoginAttemptModel;
use Nenad\Autosav\Modules\Auth\Models\UnblockHistoryModel;
use Nenad\Autosav\Modules\Auth\Services\AuthService;
use Nenad\Autosav\Modules\Auth\Services\BlockingService;
use Nenad\Autosav\Modules\Auth\Services\TermsService;
use Nenad\Autosav\Modules\Auth\Models\TermsVersionModel;
use Nenad\Autosav\Modules\Auth\Models\TermsAcceptanceModel;

/**
 * ContrÃ´leur de connexion.
 *
 * RÃˆGLE ABSOLUE (R45) : Aucun AJAX sur cette page.
 * Tous les flux sont en HTTP classique avec rechargement de page.
 */
class LoginController extends BaseController
{
    private AuthService    $authService;
    private TermsService   $termsService;
    private SecurityManager $security;

    public function __construct()
    {
        parent::__construct();

        $logger   = LogManager::getInstance();
        $security = SecurityManager::getInstance();

        $ipModel      = new IpBlacklistModel();
        $emailModel   = new EmailBlacklistModel();
        $attemptModel = new LoginAttemptModel();
        $unblockModel = new UnblockHistoryModel();

        $blockingService = new BlockingService(
            $ipModel,
            $emailModel,
            $attemptModel,
            $unblockModel,
            $logger
        );

        $this->authService = new AuthService(
            $blockingService,
            $attemptModel,
            $security,
            $logger
        );

        $this->termsService = new TermsService(
            new TermsVersionModel(),
            new TermsAcceptanceModel(),
            $logger
        );

        $this->security = $security;
    }

    /**
     * Affiche le formulaire de connexion.
     * Redirige vers le dashboard si dÃ©jÃ  authentifiÃ©.
     *
     * @return void
     */
    public function showLogin(): void
    {
        // Rediriger si dÃ©jÃ  connectÃ©
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('Auth/Views/login', [
            'pageTitle'    => 'Connexion â€” Autosav',
            'csrfToken'    => $this->csrfToken(),
            'flash'        => $this->flash()->all(),
            'emailPrefill' => '', // Pas de prÃ©-remplissage de l'email (sÃ©curitÃ©)
        ], 'none');
    }

    /**
     * Traite la soumission du formulaire de connexion.
     *
     * @return void
     */
    public function processLogin(): void
    {
        // VÃ©rifier que c'est bien une requÃªte POST
        if (!$this->getRequest()->isPost()) {
            $this->redirect('/');
            return;
        }

        // Valider le token CSRF (protection contre CSRF)
        if (!$this->verifyCsrf($this->getRequest()->post(CSRF_FORM_FIELD))) {
            $this->flash()->error('Erreur de sÃ©curitÃ©. Veuillez rÃ©essayer.');
            $this->redirect('/');
            return;
        }

        // RÃ©cupÃ©rer et nettoyer les inputs
        $rawEmail    = $this->getRequest()->post('email', '');
        $rawPassword = $this->getRequest()->post('password', '');
        $ip          = AuthService::detectClientIp();
        $userAgent   = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Validation basique de format
        if (empty($rawEmail) || empty($rawPassword)) {
            $this->flash()->error('L\'email et le mot de passe sont obligatoires.');
            $this->redirect('/');
            return;
        }

        if (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
            $this->flash()->error('Format d\'email invalide.');
            $this->redirect('/');
            return;
        }

        // Traiter la tentative de connexion via AuthService
        $result = $this->authService->processLogin($rawEmail, $rawPassword, $ip, $userAgent);

        // --- Gestion des cas d'Ã©chec ---
        if ($result['result'] !== AuthService::RESULT_SUCCESS) {
            $this->flash()->error($result['message']);
            $this->redirect('/');
            return;
        }

        // --- Connexion rÃ©ussie ---
        $user = $result['user'];

        // RÃ©gÃ©nÃ©rer l'identifiant de session (prÃ©venir la fixation de session)
        $this->security->regenerateSession();

        // Initialiser la session utilisateur
        $this->initUserSession($user);

        // Enregistrer la session dans sav_sessions (audit)
        $this->recordSession($user['use_id'], session_id(), $ip, $userAgent);

        // VÃ©rifier si les CGU doivent Ãªtre acceptÃ©es (R31)
        if ($this->termsService->isTermsPendingForUser((int) $user['use_id'])) {
            $currentVersion = $this->termsService->getCurrentVersion();
            if ($currentVersion) {
                $_SESSION['terms_pending']    = true;
                $_SESSION['terms_version_id'] = (int) $currentVersion['trv_id'];
            }
        }

        // Rediriger vers le dashboard
        $this->redirect('/dashboard');
    }

    /**
     * Initialise les variables de session aprÃ¨s une connexion rÃ©ussie.
     *
     * @param array $user DonnÃ©es utilisateur (sans hash MDP)
     */
    private function initUserSession(array $user): void
    {
        $_SESSION['authenticated']     = true;
        $_SESSION['user_id']           = (int) $user['use_id'];
        $_SESSION['user_uuid']         = $user['use_uuid'];
        $_SESSION['user_email']        = $user['use_email'];
        $_SESSION['user_firstname']    = $user['use_firstname'];
        $_SESSION['user_lastname']     = $user['use_lastname'];
        $_SESSION['user_civility']     = $user['use_civility'] ?? '';
        $_SESSION['active_company_id'] = $user['use_active_company_id'];
        $_SESSION['active_brand_id']   = $user['use_active_brand_id'];
        $_SESSION['user_roles']        = $user['roles'] ?? [$user['primary_role_code'] ?? ROLE_USER];
        $_SESSION['user_permissions']  = $user['permissions'] ?? [];
        $_SESSION['locale']            = $user['use_locale'] ?? 'fr';
        $_SESSION['timezone']          = $user['use_timezone'] ?? 'Europe/Paris';
        $_SESSION['login_at']          = time();
        $_SESSION['last_activity']     = time();
        $_SESSION['terms_pending']     = false;
    }

    /**
     * Enregistre la session dans sav_sessions pour l'audit.
     *
     * @param int    $userId
     * @param string $sessionId
     * @param string $ip
     * @param string $userAgent
     */
    private function recordSession(int $userId, string $sessionId, string $ip, string $userAgent): void
    {
        try {
            $db = \Nenad\Autosav\Core\Database\Database::getInstance();
            $db->execute(
                "INSERT INTO sav_sessions
                     (ses_session_id, ses_user_id, ses_ip, ses_user_agent, ses_started_at, ses_last_activity)
                 VALUES
                     (:session_id, :user_id, :ip, :ua, NOW(), NOW())",
                [
                    ':session_id' => $sessionId,
                    ':user_id'    => $userId,
                    ':ip'         => $ip,
                    ':ua'         => mb_substr($userAgent, 0, 500),
                ]
            );
        } catch (\Throwable $e) {
            // Ne pas bloquer la connexion si l'enregistrement session Ã©choue
            LogManager::getInstance()->channel('application')->error('session_record_failed', [
                'user_id' => $userId,
                'error'   => $e->getMessage(),
            ]);
        }
    }
}

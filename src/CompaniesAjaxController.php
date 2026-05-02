<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Security\SecurityManager;
use Nenad\Autosav\Modules\Auth\Models\TermsVersionModel;
use Nenad\Autosav\Modules\Auth\Models\TermsAcceptanceModel;
use Nenad\Autosav\Modules\Auth\Services\TermsService;
use Nenad\Autosav\Modules\Auth\Services\AuthService;

/**
 * ContrÃ´leur de gestion des CGU post-connexion.
 *
 * Flux acceptation (R33) :
 *   POST /auth/terms/accept â†’ enregistre â†’ unset session flag â†’ redirect dashboard
 *
 * Flux refus (R34) :
 *   POST /auth/terms/refuse â†’ enregistre â†’ dÃ©truit session â†’ redirect login
 */
class TermsController extends BaseController
{
    private TermsService    $termsService;
    private SecurityManager $security;

    public function __construct()
    {
        parent::__construct();

        $logger = LogManager::getInstance();

        $this->termsService = new TermsService(
            new TermsVersionModel(),
            new TermsAcceptanceModel(),
            $logger
        );

        $this->security = SecurityManager::getInstance();
    }

    /**
     * Traite l'acceptation des CGU.
     * L'utilisateur doit Ãªtre authentifiÃ© et avoir terms_pending = true.
     *
     * @return void
     */
    public function accept(): void
    {
        // VÃ©rifications de sÃ©curitÃ©
        if (!$this->isAuthenticated()) {
            $this->redirect('/');
            return;
        }

        if (!$this->getRequest()->isPost()) {
            $this->redirect('/dashboard');
            return;
        }

        if (!$this->verifyCsrf($this->getRequest()->post(CSRF_FORM_FIELD))) {
            $this->flash()->error('Erreur de sÃ©curitÃ©. Veuillez rÃ©essayer.');
            $this->redirect('/dashboard');
            return;
        }

        // VÃ©rifier que le modal Ã©tait bien en attente
        if (empty($_SESSION['terms_pending']) || empty($_SESSION['terms_version_id'])) {
            // Le modal n'Ã©tait pas attendu â†’ aller au dashboard simplement
            $this->redirect('/dashboard');
            return;
        }

        $userId    = (int) $_SESSION['user_id'];
        $versionId = (int) $_SESSION['terms_version_id'];
        $ip        = AuthService::detectClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        try {
            $this->termsService->recordAcceptance($userId, $versionId, $ip, $userAgent);

            // Lever le flag d'attente (R33)
            unset($_SESSION['terms_pending'], $_SESSION['terms_version_id']);

            $this->redirect('/dashboard');

        } catch (\Throwable $e) {
            LogManager::getInstance()->channel('application')->error('terms_accept_failed', [
                'user_id'    => $userId,
                'version_id' => $versionId,
                'error'      => $e->getMessage(),
            ]);

            $this->flash()->error('Une erreur est survenue. Veuillez rÃ©essayer.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Traite le refus des CGU.
     * Enregistre le refus, dÃ©truit la session et redirige vers login (R34).
     *
     * @return void
     */
    public function refuse(): void
    {
        if (!$this->isAuthenticated()) {
            $this->redirect('/');
            return;
        }

        if (!$this->getRequest()->isPost()) {
            $this->redirect('/dashboard');
            return;
        }

        if (!$this->verifyCsrf($this->getRequest()->post(CSRF_FORM_FIELD))) {
            $this->flash()->error('Erreur de sÃ©curitÃ©.');
            $this->redirect('/dashboard');
            return;
        }

        $userId    = (int) $_SESSION['user_id'];
        $versionId = (int) ($_SESSION['terms_version_id'] ?? 0);
        $ip        = AuthService::detectClientIp();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        // Enregistrer le refus AVANT de dÃ©truire la session (R35)
        if ($versionId > 0) {
            try {
                $this->termsService->recordRefusal($userId, $versionId, $ip, $userAgent);
            } catch (\Throwable $e) {
                LogManager::getInstance()->channel('application')->error('terms_refuse_record_failed', [
                    'user_id' => $userId,
                    'error'   => $e->getMessage(),
                ]);
            }
        }

        // Mettre Ã  jour sav_sessions (R34 : session dÃ©truite)
        try {
            \Nenad\Autosav\Core\Database\Database::getInstance()->execute(
                "UPDATE sav_sessions
                 SET ses_ended_at     = NOW(),
                     ses_ended_reason = 'destroyed'
                 WHERE ses_session_id = :session_id
                   AND ses_ended_at IS NULL",
                [':session_id' => session_id()]
            );
        } catch (\Throwable $e) {
            // Non-bloquant
        }

        // Destruction complÃ¨te de la session (R34)
        $this->security->destroySession();

        // Flash message aprÃ¨s redirection login
        session_start();
        $this->flash()->warning(TERMS_REFUSE_FLASH_MSG);

        $this->redirect(TERMS_REFUSE_REDIRECT_URL);
    }
}



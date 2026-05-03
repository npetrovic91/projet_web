<?php
declare(strict_types=1);

namespace Nenad\Autosav\Modules\Auth\Controllers;

use Nenad\Autosav\Core\Controller\BaseController;
use Nenad\Autosav\Core\Logger\LogManager;
use Nenad\Autosav\Core\Security\SecurityManager;
use Nenad\Autosav\Modules\Auth\Models\EmailTokenModel;
use Nenad\Autosav\Modules\Auth\Models\PasswordResetTokenModel;
use Nenad\Autosav\Modules\Auth\Services\AuthService;
use Nenad\Autosav\Modules\Auth\Services\EmailVerificationService;
use Nenad\Autosav\Modules\Auth\Services\PasswordResetService;

/**
 * ContrÃ´leur de gestion des mots de passe.
 * Flux forgot password et reset password sans AJAX.
 */
class PasswordController extends BaseController
{
    private PasswordResetService $resetService;

    public function __construct()
    {
        parent::__construct();

        $logger   = LogManager::getInstance();
        $security = SecurityManager::getInstance();

        $emailVerifService = new EmailVerificationService(
            new EmailTokenModel(),
            $logger
        );

        $this->resetService = new PasswordResetService(
            new PasswordResetTokenModel(),
            $emailVerifService,
            $security,
            $logger
        );
    }

    /**
     * Affiche le formulaire "Mot de passe oubliÃ©".
     *
     * @return void
     */
    public function showForgot(): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }

        $this->render('Auth/Views/forgot_password', [
            'pageTitle' => 'Mot de passe oubliÃ© â€” Autosav',
            'csrfToken' => $this->csrfToken(),
            'flash'     => $this->flash()->all(),
        ], 'none');
    }

    /**
     * Traite la demande de rÃ©initialisation de mot de passe.
     *
     * @return void
     */
    public function processForgot(): void
    {
        if (!$this->getRequest()->isPost()) {
            $this->redirect('/auth/forgot-password');
            return;
        }

        if (!$this->verifyCsrf($this->getRequest()->post(CSRF_FORM_FIELD))) {
            $this->flash()->error('Erreur de sÃ©curitÃ©. Veuillez rÃ©essayer.');
            $this->redirect('/auth/forgot-password');
            return;
        }

        $email = $this->getRequest()->post('email', '');
        $ip    = AuthService::detectClientIp();

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash()->error('Veuillez saisir une adresse email valide.');
            $this->redirect('/auth/forgot-password');
            return;
        }

        // Traitement â€” rÃ©ponse toujours identique (anti-Ã©numÃ©ration)
        $this->resetService->generateAndSendToken($email, $ip);

        $this->flash()->success(
            'Si cette adresse email est associÃ©e Ã  un compte, vous recevrez un email de rÃ©initialisation dans quelques instants.'
        );

        $this->redirect('/auth/forgot-password');
    }

    /**
     * Affiche le formulaire de saisie du nouveau mot de passe.
     *
     * @param string $token Token brut depuis l'URL
     * @return void
     */
    public function showReset(string $token): void
    {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
            return;
        }

        $rawToken  = trim($token);
        $tokenData = $this->resetService->validateToken($rawToken);

        if ($tokenData === null) {
            $this->render('Auth/Views/reset_password', [
                'pageTitle' => 'RÃ©initialisation â€” Autosav',
                'csrfToken' => $this->csrfToken(),
                'flash'     => $this->flash()->all(),
                'tokenValid'=> false,
                'token'     => '',
            ], 'none');
            return;
        }

        $this->render('Auth/Views/reset_password', [
            'pageTitle'  => 'Nouveau mot de passe â€” Autosav',
            'csrfToken'  => $this->csrfToken(),
            'flash'      => $this->flash()->all(),
            'tokenValid' => true,
            'token'      => htmlspecialchars($rawToken, ENT_QUOTES, 'UTF-8'),
        ], 'none');
    }

    /**
     * Traite la soumission du nouveau mot de passe.
     *
     * @return void
     */
    public function processReset(): void
    {
        if (!$this->getRequest()->isPost()) {
            $this->redirect('/');
            return;
        }

        if (!$this->verifyCsrf($this->getRequest()->post(CSRF_FORM_FIELD))) {
            $this->flash()->error('Erreur de sÃ©curitÃ©. Veuillez rÃ©essayer.');
            $this->redirect('/auth/forgot-password');
            return;
        }

        $rawToken   = trim($this->getRequest()->post('token', ''));
        $password   = $this->getRequest()->post('password', '');
        $passwordConfirm = $this->getRequest()->post('password_confirm', '');
        $ip         = AuthService::detectClientIp();

        // VÃ©rifications basiques
        if (empty($rawToken)) {
            $this->flash()->error('Token manquant. Veuillez utiliser le lien reÃ§u par email.');
            $this->redirect('/auth/forgot-password');
            return;
        }

        if ($password !== $passwordConfirm) {
            $this->flash()->error('Les mots de passe ne correspondent pas.');
            $this->redirect('/auth/reset-password/' . urlencode($rawToken));
            return;
        }

        if (strlen($password) < PASSWORD_MIN_LENGTH) {
            $this->flash()->error(sprintf('Le mot de passe doit contenir au minimum %d caractÃ¨res.', PASSWORD_MIN_LENGTH));
            $this->redirect('/auth/reset-password/' . urlencode($rawToken));
            return;
        }

        // Traitement via le service
        $result = $this->resetService->resetPassword($rawToken, $password, $ip);

        if (!$result['success']) {
            $this->flash()->error($result['message']);
            $this->redirect('/auth/reset-password/' . urlencode($rawToken));
            return;
        }

        $this->flash()->success($result['message'] . ' Vous pouvez maintenant vous connecter.');
        $this->redirect('/');
    }
}
